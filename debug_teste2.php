<?php
require_once 'config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];

echo "<h2>Debug: Gastos Fixos do Usuário</h2>";
$sql = "SELECT id, descricao, valor, start_date, periodicidade, categoria_id, active FROM gastos_fixos WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "<p>Nenhum gasto fixo encontrado.</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Descrição</th><th>Valor</th><th>Start Date</th><th>Periodicidade</th><th>Categoria ID</th><th>Active</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['descricao']}</td>";
        echo "<td>{$row['valor']}</td>";
        echo "<td>{$row['start_date']}</td>";
        echo "<td>{$row['periodicidade']}</td>";
        echo "<td>{$row['categoria_id']}</td>";
        echo "<td>{$row['active']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
$stmt->close();

echo "<h2>Debug: Categorias do Usuário</h2>";
$sql = "SELECT id, nome FROM categorias WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nome</th></tr>";
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['nome']}</td>";
    echo "</tr>";
}
echo "</table>";
$stmt->close();

echo "<h2>Debug: Testando api/categorias.php?filtro=ano&year=2026</h2>";
echo "<iframe src='api/categorias.php?filtro=ano&year=2026' style='width:100%; height:500px; border:1px solid #ccc;'></iframe>";
?>
