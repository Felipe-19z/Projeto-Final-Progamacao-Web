<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];
$filtro = $_GET['filtro'] ?? 'mes';
$yearParam = isset($_GET['year']) ? intval($_GET['year']) : null;

/**
 * Converte o valor de um gasto fixo para a periodicidade desejada.
 * 
 * Exemplo:
 *   - Gasto fixo: 400/mês (periodicidade='mes')
 *   - Filtro: 'semana'
 *   - Resultado: 400 / 4.33 ≈ 92.31 (por semana)
 *   
 *   - Gasto fixo: 400/mês (periodicidade='mes')
 *   - Filtro: 'ano'
 *   - Resultado: 400 * 12 = 4800 (por ano)
 */
function converterPeriodicidade($valor, $periodicidade_original, $filtro_destino) {
    // Conversões: quantas vezes a periodicidade original ocorre em cada período
    $ocorrencias_por_periodo = [
        'dia' => ['dia' => 1, 'semana' => 1/7, 'mes' => 1/30, 'ano' => 1/365],
        'semana' => ['dia' => 7, 'semana' => 1, 'mes' => 1/4.33, 'ano' => 1/52],
        'mes' => ['dia' => 30, 'semana' => 4.33, 'mes' => 1, 'ano' => 1/12],
        'ano' => ['dia' => 365, 'semana' => 52, 'mes' => 12, 'ano' => 1]
    ];
    
    // Obter fator de conversão
    if (isset($ocorrencias_por_periodo[$filtro_destino][$periodicidade_original])) {
        $fator = $ocorrencias_por_periodo[$filtro_destino][$periodicidade_original];
        return $valor * $fator;
    }
    
    // Fallback: retornar valor original se periodicidade desconhecida
    return $valor;
}

// Definir período
$data_inicio = date('Y-m-d');
$data_fim = date('Y-m-d');

switch ($filtro) {
    case 'dia':
        $data_inicio = $data_fim = date('Y-m-d');
        break;
    case 'semana':
        $data_inicio = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'mes':
        $data_inicio = date('Y-m-01');
        $data_fim = date('Y-m-d', strtotime('last day of this month'));
        break;
    case 'ano':
        $yearToUse = $yearParam ?? intval(date('Y'));
        $data_inicio = sprintf('%04d-01-01', $yearToUse);
        $data_fim = sprintf('%04d-12-31', $yearToUse);
        break;
}

// Obter usuário para renda
$usuario = obter_usuario($usuario_id);

// Calcular renda proporcional
$renda_mensal = $usuario['renda_mensal'];

if ($filtro === 'dia') {
    $renda = $renda_mensal / 30; // Renda média por dia
} else if ($filtro === 'semana') {
    $renda = ($renda_mensal / 30) * 7; // Renda média por semana
} else if ($filtro === 'ano') {
    $renda = $renda_mensal * 12; // Renda anual
} else {
    $renda = $renda_mensal; // Renda mensal
}

// Somar gastos no período
$sql = "SELECT COALESCE(SUM(valor), 0) as total FROM gastos 
        WHERE usuario_id = ? AND data_gasto BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$gastos_total = floatval($row['total']);
$stmt->close();

// Calcular saldo
$saldo = $renda - $gastos_total;

// Obter detalhes por categoria (mapeados por id)
$sql = "SELECT c.id, c.nome, c.cor_hex, COALESCE(SUM(g.valor), 0) as total
        FROM categorias c
        LEFT JOIN gastos g ON c.id = g.categoria_id AND g.usuario_id = ? AND g.data_gasto BETWEEN ? AND ?
        WHERE c.usuario_id = ?
        GROUP BY c.id
        ORDER BY total DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issi", $usuario_id, $data_inicio, $data_fim, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$categorias_map = [];
while ($row = $result->fetch_assoc()) {
    $row['total'] = floatval($row['total']);
    $categorias_map[intval($row['id'])] = $row;
}
$stmt->close();

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
            $start = new DateTime($fx['start_date']);
            $periodStart = new DateTime($data_inicio);
            $periodEnd = new DateTime($data_fim);

            $occurrences = [];
            if ($fx['periodicidade'] === 'mes') {
                $day = intval((new DateTime($fx['start_date']))->format('j'));
                $cursor = clone $periodStart;
                $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), min($day, (int)$cursor->format('t')));
                while ($cursor <= $periodEnd) {
                    if ($cursor >= $start && $cursor >= $periodStart && $cursor <= $periodEnd) {
                        $occurrences[] = $cursor->format('Y-m-d');
                    }
                    $cursor->modify('+1 month');
                    $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), min($day, (int)$cursor->format('t')));
                }
            } else if ($fx['periodicidade'] === 'semana') {
                $startWeekday = (int)(new DateTime($fx['start_date']))->format('w');
                $cursor = clone $periodStart;
                while ((int)$cursor->format('w') !== $startWeekday && $cursor <= $periodEnd) {
                    $cursor->modify('+1 day');
                }
                while ($cursor <= $periodEnd) {
                    if ($cursor >= $start) $occurrences[] = $cursor->format('Y-m-d');
                    $cursor->modify('+7 day');
                }
            } else if ($fx['periodicidade'] === 'ano') {
                $parts = explode('-', (new DateTime($fx['start_date']))->format('m-d'));
                $m = intval($parts[0]); $d = intval($parts[1]);
                $cursor = clone $periodStart;
                $cursor->setDate((int)$cursor->format('Y'), $m, $d);
                if ($cursor < $periodStart) $cursor->modify('+1 year');
                while ($cursor <= $periodEnd) {
                    if ($cursor >= $start) $occurrences[] = $cursor->format('Y-m-d');
                    $cursor->modify('+1 year');
                }
            }

            // Para cada ocorrência, verificar se já existe um gasto real na mesma data/categoria
            $validDates = [];
            foreach ($occurrences as $d) {
                $chk = $conn->prepare("SELECT id FROM gastos WHERE usuario_id = ? AND categoria_id = ? AND data_gasto = ? LIMIT 1");
                if ($chk) {
                    $chk->bind_param('iis', $usuario_id, $fx['categoria_id'], $d);
                    $chk->execute();
                    $rchk = $chk->get_result();
                    if ($rchk && $rchk->num_rows === 0) {
                        $validDates[] = $d;
                    }
                    $chk->close();
                }
            }

            // Converter o valor do gasto fixo para a periodicidade do filtro atual
            $valor_convertido = converterPeriodicidade(
                floatval($fx['valor']),
                $fx['periodicidade'],
                $filtro
            );

            // Somar contribuição deste gasto fixo (valor já convertido, sem multiplicar por count)
            // pois a conversão já leva em conta a periodicidade
            $fixSum = $valor_convertido;
            $gastos_total += $fixSum;
            $cid = intval($fx['categoria_id']);
            if (isset($categorias_map[$cid])) {
                $categorias_map[$cid]['total'] += $fixSum;
            } else {
                $q = $conn->prepare("SELECT id, nome, cor_hex FROM categorias WHERE id = ? AND usuario_id = ? LIMIT 1");
                if ($q) {
                    $q->bind_param('ii', $cid, $usuario_id);
                    $q->execute();
                    $r = $q->get_result();
                    if ($r && $rRow = $r->fetch_assoc()) {
                        $rRow['total'] = $fixSum;
                        $categorias_map[$cid] = $rRow;
                    }
                    $q->close();
                }
            }
        }
        $stmtFix->close();
    }
}

// Montar array final de categorias (somente as com total > 0)
$categorias = [];
foreach ($categorias_map as $cid => $c) {
    if (floatval($c['total']) > 0) $categorias[] = $c;
}

// Recalcular saldo após adicionar gastos fixos convertidos
$saldo = $renda - $gastos_total;

api_json([
    'success' => true,
    'renda' => round($renda, 2),
    'gastos_total' => round($gastos_total, 2),
    'saldo' => round($saldo, 2),
    'categorias' => $categorias,
    'periodo' => $filtro
]);

