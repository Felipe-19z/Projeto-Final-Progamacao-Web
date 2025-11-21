<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

// Apenas aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Receber dados JSON
$data = json_decode(file_get_contents('php://input'), true);

// Validar dados
if (!isset($data['email']) || !isset($data['senha'])) {
    echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios']);
    exit;
}

$email = sanitizar($data['email']);
$senha = $data['senha'];

// Verificar se email existe
$sql = "SELECT id, nome, email, senha, ativo FROM usuarios WHERE email = ? AND ativo = TRUE";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados']);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos']);
    $stmt->close();
    exit;
}

$usuario = $result->fetch_assoc();
$stmt->close();

// Verificar senha
if (!verificar_senha($senha, $usuario['senha'])) {
    echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos']);
    exit;
}

// Definir sessão
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];
$_SESSION['usuario_email'] = $usuario['email'];

// Registrar log de acesso
registrar_log_acesso($usuario['id']);

echo json_encode([
    'success' => true,
    'message' => 'Login realizado com sucesso',
    'usuario' => [
        'id' => $usuario['id'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email']
    ]
]);
?>
