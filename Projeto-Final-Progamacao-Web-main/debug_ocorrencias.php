<?php
require_once 'config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];

// Simular o que api/categorias.php faz para 2026
$filtro = 'ano';
$yearParam = 2026;
$data_inicio = sprintf('%04d-01-01', $yearParam);
$data_fim = sprintf('%04d-12-31', $yearParam);

echo "<h2>Filtro: $filtro, Ano: $yearParam</h2>";
echo "<p>Data início: $data_inicio</p>";
echo "<p>Data fim: $data_fim</p>";

// 1. Buscar gastos regulares no período
echo "<h3>1. Gastos Regulares em $yearParam</h3>";
$sql = "SELECT g.id, g.descricao, g.valor, g.data_gasto, c.nome as categoria FROM gastos g
        JOIN categorias c ON g.categoria_id = c.id
        WHERE g.usuario_id = ? AND g.data_gasto BETWEEN ? AND ?
        ORDER BY g.data_gasto";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $usuario_id, $data_inicio, $data_fim);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "<p>Nenhum gasto regular encontrado em $yearParam.</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Descrição</th><th>Valor</th><th>Data</th><th>Categoria</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['descricao']}</td>";
        echo "<td>R$ {$row['valor']}</td>";
        echo "<td>{$row['data_gasto']}</td>";
        echo "<td>{$row['categoria']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
$stmt->close();

// 2. Buscar gastos fixos
echo "<h3>2. Gastos Fixos do Usuário</h3>";
$sql = "SELECT id, descricao, valor, start_date, periodicidade, categoria_id, active FROM gastos_fixos WHERE usuario_id = ? AND active = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "<p>Nenhum gasto fixo encontrado.</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Descrição</th><th>Valor</th><th>Start Date</th><th>Periodicidade</th><th>Categoria ID</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['descricao']}</td>";
        echo "<td>R$ {$row['valor']}</td>";
        echo "<td>{$row['start_date']}</td>";
        echo "<td>{$row['periodicidade']}</td>";
        echo "<td>{$row['categoria_id']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
$stmt->close();

// 3. Mostrar as ocorrências geradas para cada gasto fixo em 2026
echo "<h3>3. Ocorrências de Gastos Fixos em $yearParam</h3>";
$sql = "SELECT id, descricao, valor, start_date, periodicidade, categoria_id FROM gastos_fixos WHERE usuario_id = ? AND active = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

while ($fx = $res->fetch_assoc()) {
    echo "<h4>{$fx['descricao']} (ID: {$fx['id']}, Categoria: {$fx['categoria_id']})</h4>";
    
    $fixStart = new DateTime($fx['start_date']);
    $periodStart = new DateTime($data_inicio);
    $periodEnd = new DateTime($data_fim);
    
    $occurrences = [];
    
    if ($fx['periodicidade'] === 'mes') {
        $day = intval($fixStart->format('j'));
        $cursor = clone $fixStart;
        if ($cursor < $periodStart) {
            $cursor->setDate((int)$periodStart->format('Y'), (int)$periodStart->format('m'), min($day, (int)$periodStart->format('t')));
        }
        while ($cursor <= $periodEnd) {
            $d = $cursor->format('Y-m-d');
            if ($d >= $data_inicio && $d <= $data_fim) {
                $occurrences[] = $d;
            }
            $cursor->modify('+1 month');
            $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), min($day, (int)$cursor->format('t')));
        }
    }
    
    echo "<p>Ocorrências: " . implode(", ", $occurrences) . "</p>";
}
$stmt->close();
?>
