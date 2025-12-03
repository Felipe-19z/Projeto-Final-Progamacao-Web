<?php
require_once 'config.php';
verificar_login();
if (!isset($_SESSION['is_premium']) || !$_SESSION['is_premium']) {
    header('Location: index.php');
    exit();
}
$usuario_id = $_SESSION['usuario_id'];
$usuario = obter_usuario($usuario_id);

// Buscar gastos agrupados por ano e por categoria
$sql = "SELECT YEAR(data_gasto) as ano, SUM(valor) as total FROM gastos WHERE usuario_id = ? GROUP BY YEAR(data_gasto) ORDER BY ano ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$porAno = [];
while($r = $res->fetch_assoc()){
    $porAno[intval($r['ano'])] = floatval($r['total']);
}
$stmt->close();

// Gastos por categoria (somente opcionais) para recomendações
$sql2 = "SELECT c.nome, SUM(g.valor) as total FROM gastos g JOIN categorias c ON g.categoria_id = c.id WHERE g.usuario_id = ? AND g.tipo_gasto = 'opcional' GROUP BY g.categoria_id ORDER BY total DESC LIMIT 5";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param('i', $usuario_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$recomendacoes = [];
while($r = $res2->fetch_assoc()){
    $recomendacoes[] = $r;
}
$stmt2->close();

// Incluir gastos fixos no array porAno (permite anos futuros)
$checkFixAll = $conn->query("SHOW TABLES LIKE 'gastos_fixos'");
if ($checkFixAll && $checkFixAll->num_rows > 0) {
    $sqlFixAll = "SELECT * FROM gastos_fixos WHERE usuario_id = ? AND active = 1";
    $stmtFixAll = $conn->prepare($sqlFixAll);
    if ($stmtFixAll) {
        $stmtFixAll->bind_param('i', $usuario_id);
        $stmtFixAll->execute();
        $resFixAll = $stmtFixAll->get_result();
        $yearLimitEnd = date('Y') + 5; // include a few future years
        $endLimit = new DateTime($yearLimitEnd . '-12-31');
        while ($fx = $resFixAll->fetch_assoc()) {
            $start = new DateTime($fx['start_date']);
            $cursor = clone $start;
            if ($fx['periodicidade'] === 'mes') {
                $day = intval($start->format('j'));
                while ($cursor <= $endLimit) {
                    $d = $cursor->format('Y-m-d');
                    // verificar se existe gasto real nesse dia
                    $chk = $conn->prepare("SELECT id FROM gastos WHERE usuario_id = ? AND categoria_id = ? AND data_gasto = ? LIMIT 1");
                    if ($chk) {
                        $chk->bind_param('iis', $usuario_id, $fx['categoria_id'], $d);
                        $chk->execute();
                        $rchk = $chk->get_result();
                        if ($rchk && $rchk->num_rows === 0) {
                            $y = intval(substr($d,0,4));
                            if (!isset($porAno[$y])) $porAno[$y] = 0.0;
                            $porAno[$y] += floatval($fx['valor']);
                        }
                        $chk->close();
                    }
                    $cursor->modify('+1 month');
                    $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), min($day, (int)$cursor->format('t')));
                }
            } else if ($fx['periodicidade'] === 'semana') {
                $startWeekday = intval($start->format('w'));
                $cursor = clone $start;
                while ((int)$cursor->format('w') !== $startWeekday && $cursor <= $endLimit) $cursor->modify('+1 day');
                while ($cursor <= $endLimit) {
                    $d = $cursor->format('Y-m-d');
                    $chk = $conn->prepare("SELECT id FROM gastos WHERE usuario_id = ? AND categoria_id = ? AND data_gasto = ? LIMIT 1");
                    if ($chk) {
                        $chk->bind_param('iis', $usuario_id, $fx['categoria_id'], $d);
                        $chk->execute();
                        $rchk = $chk->get_result();
                        if ($rchk && $rchk->num_rows === 0) {
                            $y = intval(substr($d,0,4));
                            if (!isset($porAno[$y])) $porAno[$y] = 0.0;
                            $porAno[$y] += floatval($fx['valor']);
                        }
                        $chk->close();
                    }
                    $cursor->modify('+7 day');
                }
            } else if ($fx['periodicidade'] === 'ano') {
                $parts = explode('-', $start->format('m-d'));
                $m = intval($parts[0]); $d = intval($parts[1]);
                $cursor = clone $start;
                $cursor->setDate((int)$cursor->format('Y'), $m, $d);
                if ($cursor < new DateTime()) $cursor->modify('+1 year');
                while ($cursor <= $endLimit) {
                    $dd = $cursor->format('Y-m-d');
                    $chk = $conn->prepare("SELECT id FROM gastos WHERE usuario_id = ? AND categoria_id = ? AND data_gasto = ? LIMIT 1");
                    if ($chk) {
                        $chk->bind_param('iis', $usuario_id, $fx['categoria_id'], $dd);
                        $chk->execute();
                        $rchk = $chk->get_result();
                        if ($rchk && $rchk->num_rows === 0) {
                            $y = intval(substr($dd,0,4));
                            if (!isset($porAno[$y])) $porAno[$y] = 0.0;
                            $porAno[$y] += floatval($fx['valor']);
                        }
                        $chk->close();
                    }
                    $cursor->modify('+1 year');
                }
            }
        }
        $stmtFixAll->close();
    }
}

// Agregar para dia/semana/mes/ano atual
function soma_periodo($conn, $usuario_id, $periodo){
    switch($periodo){
        case 'dia': $start = date('Y-m-d'); $end = date('Y-m-d'); break;
        case 'semana': $start = date('Y-m-d', strtotime('-6 days')); $end = date('Y-m-d'); break;
        case 'mes': $start = date('Y-m-01'); $end = date('Y-m-d', strtotime('last day of this month')); break;
        case 'ano': $start = date('Y-01-01'); $end = date('Y-12-31'); break;
        default: $start = date('Y-m-01'); $end = date('Y-m-d');
    }
    $sql = "SELECT SUM(valor) as total FROM gastos WHERE usuario_id = ? AND data_gasto BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $usuario_id, $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    $total = floatval($row['total'] ?? 0);

    // Incluir contribuições de gastos_fixos no período (se existir a tabela)
    $checkFix = $conn->query("SHOW TABLES LIKE 'gastos_fixos'");
    if ($checkFix && $checkFix->num_rows > 0) {
        $sqlFix = "SELECT * FROM gastos_fixos WHERE usuario_id = ? AND active = 1";
        $stmtFix = $conn->prepare($sqlFix);
        if ($stmtFix) {
            $stmtFix->bind_param('i', $usuario_id);
            $stmtFix->execute();
            $resFix = $stmtFix->get_result();
            while ($fx = $resFix->fetch_assoc()) {
                // Data de início do gasto fixo
                $fixStart = new DateTime($fx['start_date']);
                // Período solicitado (convertendo as strings $start/$end para DateTime)
                $periodStart = new DateTime($start);
                $periodEnd = new DateTime($end);
                // Cursor inicial para gerar ocorrências a partir do start do fixo
                $cursor = clone $fixStart;
                $pStart = clone $fixStart;
                $pEnd = clone $periodEnd;

                $occurrences = [];
                if ($fx['periodicidade'] === 'mes') {
                    $day = intval((new DateTime($fx['start_date']))->format('j'));
                    $cursor = clone $pStart;
                    $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), min($day, (int)$cursor->format('t')));
                    while ($cursor <= $pEnd) {
                        if ($cursor >= $pStart && $cursor >= $pStart && $cursor <= $pEnd) $occurrences[] = $cursor->format('Y-m-d');
                        $cursor->modify('+1 month');
                        $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), min($day, (int)$cursor->format('t')));
                    }
                } else if ($fx['periodicidade'] === 'semana') {
                    $startWeekday = (int)(new DateTime($fx['start_date']))->format('w');
                    $cursor = clone $pStart;
                    while ((int)$cursor->format('w') !== $startWeekday && $cursor <= $pEnd) $cursor->modify('+1 day');
                    while ($cursor <= $pEnd) {
                        if ($cursor >= $pStart) $occurrences[] = $cursor->format('Y-m-d');
                        $cursor->modify('+7 day');
                    }
                } else if ($fx['periodicidade'] === 'ano') {
                    $parts = explode('-', (new DateTime($fx['start_date']))->format('m-d'));
                    $m = intval($parts[0]); $d = intval($parts[1]);
                    $cursor = clone $pStart;
                    $cursor->setDate((int)$cursor->format('Y'), $m, $d);
                    if ($cursor < $pStart) $cursor->modify('+1 year');
                    while ($cursor <= $pEnd) {
                        if ($cursor >= $pStart) $occurrences[] = $cursor->format('Y-m-d');
                        $cursor->modify('+1 year');
                    }
                }

                // For each occurrence, ensure there's no real gasto on that date (avoid double count)
                foreach ($occurrences as $d) {
                    $chk = $conn->prepare("SELECT id FROM gastos WHERE usuario_id = ? AND categoria_id = ? AND data_gasto = ? LIMIT 1");
                    if ($chk) {
                        $chk->bind_param('iis', $usuario_id, $fx['categoria_id'], $d);
                        $chk->execute();
                        $rchk = $chk->get_result();
                        if ($rchk && $rchk->num_rows === 0) {
                            $total += floatval($fx['valor']);
                        }
                        $chk->close();
                    }
                }
            }
            $stmtFix->close();
        }
    }

    return $total;
}

$totDia = soma_periodo($conn, $usuario_id, 'dia');
$totSemana = soma_periodo($conn, $usuario_id, 'semana');
$totMes = soma_periodo($conn, $usuario_id, 'mes');
$totAno = soma_periodo($conn, $usuario_id, 'ano');
// Calcular média diária (simples) para o mês e por dia
$mediaDia = $totMes / max(1,intval(date('t')));

// Renda do usuário e saldo por período (usar mesma lógica que api/stats-periodo)
$renda_mensal = floatval($usuario['renda_mensal'] ?? 0);
$rendaDia = $renda_mensal / 30;
$rendaSemana = $renda_mensal / 4.33;
$rendaMes = $renda_mensal;
$rendaAno = $renda_mensal * 12;

$saldoDia = $rendaDia - $totDia;
$saldoSemana = $rendaSemana - $totSemana;
$saldoMes = $rendaMes - $totMes;
$saldoAno = $rendaAno - $totAno;

// Happiness bar: calcular sobra anual = renda_mensal*12 - totAno
$rendaAnual = $rendaAno;
$sobraAnual = $rendaAnual - $totAno;
$nivel = '';
$nivelCor = '#dddddd';
if ($sobraAnual <= 50) { $nivel = '😧 Muito Pouco dinheiro'; $nivelCor = '#e74c3c'; }
else if ($sobraAnual <= 150) { $nivel = '😕 Pouco dinheiro'; $nivelCor = '#f39c12'; }
else if ($sobraAnual <= 400) { $nivel = '😐 Normal'; $nivelCor = '#f1c40f'; }
else if ($sobraAnual <= 800) { $nivel = '🙂 Tem dinheiro de sobra'; $nivelCor = '#9acd32'; }
else { $nivel = '😁 Tem Muito Dinheiro de sobra'; $nivelCor = '#2ecc71'; }

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Estatísticas - Premium</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;margin:0;padding:20px}
.container{max-width:1100px;margin:0 auto}
.card{background:#fff;padding:18px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.06);margin-bottom:18px}
.grid{display:flex;gap:12px;flex-wrap:wrap}
.box{flex:1;min-width:200px}
.happiness{height:20px;border-radius:10px;background:#eee;overflow:hidden}
.happiness-bar{height:100%;border-radius:10px}
.recommend{font-size:14px;color:#333}
</style>
</head>
<body>
<div class="container" style="position:relative;">
    <a href="index.php" style="position:absolute; top:18px; right:18px; background:#667eea; color:#fff; padding:8px 18px; border-radius:8px; text-decoration:none; font-weight:600; box-shadow:0 2px 8px rgba(0,0,0,0.08); transition:background 0.2s;">← Voltar para o Menu</a>
    <h1 style="margin-right:160px;">📈 Estatísticas Avançadas</h1>
    <div class="card">
        <h3>Gastos por Ano</h3>
        <canvas id="chartAno" style="height:220px"></canvas>
    </div>

    <div class="grid">
        <div class="card box" data-period="dia">
            <h4>Dia</h4>
            <div style="font-size:20px;font-weight:700" data-value>R$ <?php echo number_format($saldoDia,2,',','.'); ?></div>
            <div style="font-size:12px;color:#666">Média diária no mês: R$ <?php echo number_format($mediaDia,2,',','.'); ?></div>
            <p class="recommend">Recomendação: <?php echo ($totDia>0) ? 'Rever gastos opcionais do dia' : 'Nenhuma ação necessária'; ?></p>
        </div>
        <div class="card box" data-period="semana">
            <h4>Semana</h4>
            <div style="font-size:20px;font-weight:700" data-value>R$ <?php echo number_format($saldoSemana,2,',','.'); ?></div>
            <p class="recommend">Recomendação: Priorize redução dos maiores gastos opcionais.</p>
        </div>
        <div class="card box" data-period="mes">
            <h4>Mês</h4>
            <div style="font-size:20px;font-weight:700" data-value>R$ <?php echo number_format($saldoMes,2,',','.'); ?></div>
            <p class="recommend">Recomendação: Verifique categorias opcionais com maior gasto.</p>
        </div>
        <div class="card box" data-period="ano">
            <h4>Ano</h4>
            <div style="font-size:20px;font-weight:700" data-value>R$ <?php echo number_format($saldoAno,2,',','.'); ?></div>
            <p class="recommend">Recomendação: Ajuste despesas opcionais para economizar no próximo ano.</p>
        </div>
    </div>

    <div class="card">
        <h3>Recomendações (gastos opcionais mais caros)</h3>
        <?php if (count($recomendacoes)===0): ?>
            <p>Nenhum gasto opcional encontrado.</p>
        <?php else: ?>
            <ul>
                <?php foreach($recomendacoes as $r): ?>
                    <li><?php echo htmlspecialchars($r['nome']); ?> — R$ <?php echo number_format($r['total'],2,',','.'); ?> — <strong>Considere reduzir ou eliminar</strong></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Barra de Felicidade</h3>
        <div style="display:flex;align-items:center;gap:12px">
            <div style="flex:1">
                <div class="happiness"><div class="happiness-bar" style="width:<?php echo min(100,max(0,($sobraAnual/1000)*100)); ?>%;background:<?php echo $nivelCor; ?>"></div></div>
            </div>
            <div style="min-width:200px;text-align:right">
                <div style="font-weight:700"><?php echo $nivel; ?></div>
                <div style="font-size:13px;color:#666">Sobra anual estimada: R$ <?php echo number_format($sobraAnual,2,',','.'); ?></div>
            </div>
        </div>
    </div>

</div>
<script>
const anos = <?php echo json_encode(array_keys($porAno)); ?>;
const valores = <?php echo json_encode(array_values($porAno)); ?>;
let anoSelecionado = null;

const ctx = document.getElementById('chartAno').getContext('2d');
const chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: anos,
        datasets: [{ 
            label: 'Gastos por Ano', 
            data: valores, 
            backgroundColor: '#667eea',
            borderColor: '#333',
            borderWidth: 0
        }]
    },
    options: { 
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: true }
        }
    },
    plugins: [{
        id: 'customCanvasBackgroundColor',
        afterDatasetsDraw(chart) {
            // Nada especial, só permitir cliques
        }
    }]
});

// Adicionar listener de clique ao canvas
document.getElementById('chartAno').addEventListener('click', function(event) {
    const canvasPosition = Chart.helpers.getRelativePosition(event, chart);
    const dataX = chart.scales.x.getValueForPixel(canvasPosition.x);
    const dataY = chart.scales.y.getValueForPixel(canvasPosition.y);
    
    // Encontrar qual barra foi clicada
    const canvases = Chart.instances;
    let clickedIndex = -1;
    
    // Abordagem mais simples: iterar sobre as barras
    for (let i = 0; i < anos.length; i++) {
        const meta = chart.getDatasetMeta(0);
        const bar = meta.data[i];
        if (bar && event.offsetX >= bar.x - bar.width/2 && event.offsetX <= bar.x + bar.width/2) {
            clickedIndex = i;
            break;
        }
    }
    
    if (clickedIndex >= 0) {
        const anoClicked = anos[clickedIndex];
        console.log('Ano selecionado:', anoClicked);
        // Marcar barra como selecionada
        const newColors = valores.map((v, i) => i === clickedIndex ? '#4CAF50' : '#667eea');
        chart.data.datasets[0].backgroundColor = newColors;
        chart.update();
        anoSelecionado = anoClicked;
        // Buscar e atualizar os períodos para este ano
        atualizarPeriodosPorAno(anoClicked);
    }
});

function atualizarPeriodosPorAno(ano) {
    console.log('Chamando API para ano:', ano);
    // Fazer requisição para buscar totais de dia/semana/mês/ano para esse ano específico
    fetch(`api/stats-periodo.php?ano=${ano}`, { credentials: 'same-origin' })
        .then(r => {
            console.log('Response status:', r.status);
            return r.json();
        })
        .then(data => {
            console.log('Response data completo:', JSON.stringify(data));
            if (data && data.success === true) {
                // Atualizar cards
                const cardDia = document.querySelector('[data-period="dia"]');
                const cardSemana = document.querySelector('[data-period="semana"]');
                const cardMes = document.querySelector('[data-period="mes"]');
                const cardAno = document.querySelector('[data-period="ano"]');
                
                const formatBR = (val) => {
                    const str = parseFloat(val).toFixed(2);
                    const parts = str.split('.');
                    const intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    return 'R$ ' + intPart + ',' + parts[1];
                };
                
                console.log('Atualizando cards com saldo:', data.saldo);
                if (cardDia) cardDia.querySelector('[data-value]').textContent = formatBR(data.saldo.dia);
                if (cardSemana) cardSemana.querySelector('[data-value]').textContent = formatBR(data.saldo.semana);
                if (cardMes) cardMes.querySelector('[data-value]').textContent = formatBR(data.saldo.mes);
                if (cardAno) cardAno.querySelector('[data-value]').textContent = formatBR(data.saldo.ano);
            } else {
                console.error('Erro ao buscar períodos - data:', data);
            }
        })
        .catch(err => {
            console.error('Erro na requisição:', err);
        });
}
</script>
</body>
</html>