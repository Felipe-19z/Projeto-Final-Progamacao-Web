<?php
require_once '../config.php';
// Apenas admin
verificar_admin();

header('Content-Type: application/json; charset=utf-8');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['user_id']) || !isset($data['make_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

$user_id = (int)$data['user_id'];
$make_admin = (bool)$data['make_admin'];

if ($user_id === $_SESSION['usuario_id']) {
    echo json_encode(['success' => false, 'message' => 'Você não pode alterar seu próprio status de administrador.']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE usuarios SET is_admin = ? WHERE id = ?");
    $flag = $make_admin ? 1 : 0;
    $stmt->bind_param('ii', $flag, $user_id);
    $stmt->execute();
    if ($stmt->affected_rows >= 0) {
        echo json_encode(['success' => true, 'message' => 'Atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma alteração feita']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
