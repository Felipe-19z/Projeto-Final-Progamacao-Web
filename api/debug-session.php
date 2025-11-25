<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

// Apenas usuário logado pode ver (não expor em produção)
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['logged_in' => false]);
    exit;
}

$response = [];
$response['logged_in'] = true;
$response['session'] = $_SESSION;

// Buscar valor atual no banco para este usuário
$usuario_id = (int)$_SESSION['usuario_id'];
$stmt = $conn->prepare('SELECT id, email, is_admin, ativo FROM usuarios WHERE id = ?');
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

$response['db_user'] = $row ?: null;

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
