<?php
require_once '../config.php';
verificar_login();

if (!isset($_SESSION['is_premium']) || !$_SESSION['is_premium']) {
    api_json(false, 'Acesso negado', 403);
}

$usuario_id = $_SESSION['usuario_id'];
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : intval(date('Y'));

// Função auxiliar para calcular totais num período
function soma_periodo_ano($conn, $usuario_id, $periodo, $ano) {
    switch($periodo) {
        case 'dia':
            $start = date('Y-m-d');
            $end = date('Y-m-d');
            break;
        case 'semana':
            $start = date('Y-m-d', strtotime('-6 days'));
            $end = date('Y-m-d');
            break;
        case 'mes':
            $start = date('Y-m-01');
            $end = date('Y-m-d', strtotime('last day of this month'));
            break;
        case 'ano':
            $start = sprintf('%04d-01-01', $ano);
            $end = sprintf('%04d-12-31', $ano);
            break;
        default:
            $start = date('Y-m-01');
            $end = date('Y-m-d');
    }

    $sql = "SELECT COALESCE(SUM(valor), 0) as total FROM gastos WHERE usuario_id = ? AND data_gasto BETWEEN ? AND ?";
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
                $fixStart = new DateTime($fx['start_date']);
                $periodStart = new DateTime($start);
                $periodEnd = new DateTime($end);

                if ($fx['periodicidade'] === 'mes') {
                    $day = intval($fixStart->format('j'));
                    $cursor = clone $fixStart;
                    // Ajustar cursor ao primeiro período relevante
                    if ($cursor < $periodStart) {
                        $cursor->setDate((int)$periodStart->format('Y'), (int)$periodStart->format('m'), min($day, (int)$periodStart->format('t')));
                    }
                    while ($cursor <= $periodEnd) {
                        $d = $cursor->format('Y-m-d');
                        if ($d >= $start && $d <= $end) {
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
                        $cursor->modify('+1 month');
                        $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), min($day, (int)$cursor->format('t')));
                    }
                } else if ($fx['periodicidade'] === 'semana') {
                    $startWeekday = intval($fixStart->format('w'));
                    $cursor = clone $fixStart;
                    if ($cursor < $periodStart) {
                        $cursor = clone $periodStart;
                        while ((int)$cursor->format('w') !== $startWeekday && $cursor <= $periodEnd) {
                            $cursor->modify('+1 day');
                        }
                    } else {
                        while ((int)$cursor->format('w') !== $startWeekday && $cursor <= $periodEnd) {
                            $cursor->modify('+1 day');
                        }
                    }
                    while ($cursor <= $periodEnd) {
                        $d = $cursor->format('Y-m-d');
                        if ($d >= $start && $d <= $end) {
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
                        $cursor->modify('+7 days');
                    }
                } else if ($fx['periodicidade'] === 'ano') {
                    $parts = explode('-', $fixStart->format('m-d'));
                    $m = intval($parts[0]);
                    $d = intval($parts[1]);
                    $cursor = clone $fixStart;
                    $cursor->setDate((int)$cursor->format('Y'), $m, $d);
                    if ($cursor < $periodStart) {
                        $cursor->setDate((int)$periodStart->format('Y'), $m, $d);
                    }
                    while ($cursor <= $periodEnd) {
                        $dd = $cursor->format('Y-m-d');
                        if ($dd >= $start && $dd <= $end) {
                            $chk = $conn->prepare("SELECT id FROM gastos WHERE usuario_id = ? AND categoria_id = ? AND data_gasto = ? LIMIT 1");
                            if ($chk) {
                                $chk->bind_param('iis', $usuario_id, $fx['categoria_id'], $dd);
                                $chk->execute();
                                $rchk = $chk->get_result();
                                if ($rchk && $rchk->num_rows === 0) {
                                    $total += floatval($fx['valor']);
                                }
                                $chk->close();
                            }
                        }
                        $cursor->modify('+1 year');
                    }
                }
            }
            $stmtFix->close();
        }
    }

    return $total;
}

// Calcular totais para cada período do ano selecionado
$totDia = soma_periodo_ano($conn, $usuario_id, 'dia', $ano);
$totSemana = soma_periodo_ano($conn, $usuario_id, 'semana', $ano);
$totMes = soma_periodo_ano($conn, $usuario_id, 'mes', $ano);
$totAno = soma_periodo_ano($conn, $usuario_id, 'ano', $ano);

// Obter renda do usuário e calcular renda por período
$usuario = obter_usuario($usuario_id);
$renda_mensal = floatval($usuario['renda_mensal'] ?? 0);
$renda = [
    'dia' => $renda_mensal / 30,
    'semana' => $renda_mensal / 4.33,
    'mes' => $renda_mensal,
    'ano' => $renda_mensal * 12
];

$saldo = [
    'dia' => $renda['dia'] - $totDia,
    'semana' => $renda['semana'] - $totSemana,
    'mes' => $renda['mes'] - $totMes,
    'ano' => $renda['ano'] - $totAno
];

api_json([
    'success' => true,
    'gastos' => [ 'dia' => $totDia, 'semana' => $totSemana, 'mes' => $totMes, 'ano' => $totAno ],
    'renda' => $renda,
    'saldo' => $saldo
]);
?>
