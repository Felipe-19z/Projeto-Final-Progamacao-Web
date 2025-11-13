<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];
$filtro = $_GET['filtro'] ?? 'mes';

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
        $data_inicio = date('Y-01-01');
        $data_fim = date('Y-12-31');
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

// Obter detalhes por categoria
$sql = "SELECT c.nome, c.cor_hex, COALESCE(SUM(g.valor), 0) as total
        FROM categorias c
        LEFT JOIN gastos g ON c.id = g.categoria_id AND g.usuario_id = ? AND g.data_gasto BETWEEN ? AND ?
        WHERE c.usuario_id = ?
        GROUP BY c.id
        ORDER BY total DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issi", $usuario_id, $data_inicio, $data_fim, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$categorias = [];
while ($row = $result->fetch_assoc()) {
    if (floatval($row['total']) > 0) {
        $categorias[] = $row;
    }
}
$stmt->close();

echo json_encode([
    'success' => true,
    'renda' => round($renda, 2),
    'gastos_total' => round($gastos_total, 2),
    'saldo' => round($saldo, 2),
    'categorias' => $categorias,
    'periodo' => $filtro
]);
?>
