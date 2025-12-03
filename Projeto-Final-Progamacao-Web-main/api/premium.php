<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    api_json(['success' => false, 'message' => 'Método não permitido']);
}

verificar_login();
$usuario_id = $_SESSION['usuario_id'];

$data = json_decode(file_get_contents('php://input'), true);
$plan = $data['plan'] ?? null;
$validPlans = ['mensal','anual','vitalicio'];
if (!$plan || !in_array($plan, $validPlans)) {
    api_json(['success' => false, 'message' => 'Plano inválido']);
}

try {
    $stmt = $conn->prepare("UPDATE usuarios SET is_premium = TRUE, premium_plan = ?, premium_since = NOW() WHERE id = ?");
    if (!$stmt) throw new Exception('Erro ao preparar statement: ' . $conn->error);
    $stmt->bind_param('si', $plan, $usuario_id);
    $stmt->execute();
    $stmt->close();

    // Atualizar sessão
    $_SESSION['is_premium'] = true;
    $_SESSION['premium_plan'] = $plan;

    api_json(['success' => true, 'message' => 'Usuário marcado como premium', 'plan' => $plan]);
} catch (Exception $e) {
    api_json(['success' => false, 'message' => $e->getMessage()]);
}
?>