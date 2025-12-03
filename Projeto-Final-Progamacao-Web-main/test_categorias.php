<?php
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    echo "Não está logado";
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Test 1: Get categories for 2026
echo "<h2>Teste 1: GET api/categorias.php?filtro=ano&year=2026</h2>";
$url = 'http://localhost/Projeto-Final-Progamacao-Web-main/api/categorias.php?filtro=ano&year=2026';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
$response = curl_exec($ch);
curl_close($ch);
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test 2: Check what gastos_fixos exist
echo "<h2>Teste 2: Gastos Fixos do Usuário</h2>";
$sql = "SELECT id, descricao, valor, start_date, periodicidade, categoria_id FROM gastos_fixos WHERE usuario_id = ? AND active = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    echo "<p>ID: {$row['id']}, Desc: {$row['descricao']}, Valor: {$row['valor']}, Start: {$row['start_date']}, Periodo: {$row['periodicidade']}, Cat: {$row['categoria_id']}</p>";
}
$stmt->close();

// Test 3: Check categories
echo "<h2>Teste 3: Categorias do Usuário</h2>";
$sql = "SELECT id, nome FROM categorias WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    echo "<p>ID: {$row['id']}, Nome: {$row['nome']}</p>";
}
$stmt->close();
?>
