<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (empty($data) || !isset($data['id']) || !isset($data['resposta'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$mensagem_id = intval($data['id']);
$resposta = trim($data['resposta']);

if ($mensagem_id <= 0 || $resposta === '') {
    echo json_encode(['success' => false, 'message' => 'ID ou resposta inválidos']);
    exit;
}

// Verificar sessão de admin: painel usa `admin_id` na sessão
// Aceitamos qualquer admin autenticado (não apenas id==1)
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissão negada']);
    exit;
}

// Atualizar mensagem
$sql = "UPDATE mensagens_ajuda SET resposta = ?, status = 'respondido', data_resposta = NOW() WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados']);
    exit;
}
$stmt->bind_param('si', $resposta, $mensagem_id);
if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Falha ao salvar resposta']);
    exit;
}
$stmt->close();

// Opcional: você pode adicionar envio de email aqui para notificar o usuário.

echo json_encode(['success' => true, 'message' => 'Resposta enviada com sucesso']);
exit;
?>
