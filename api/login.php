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

// Primeiro verificar se usuário existe (independente de estar ativo)
$sql_check = "SELECT id, nome, email, senha, ativo, is_admin FROM usuarios WHERE email = ?";
$stmt_check = $conn->prepare($sql_check);

if (!$stmt_check) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados']);
    exit;
}

$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos']);
    $stmt_check->close();
    exit;
}

$usuario_check = $result_check->fetch_assoc();
$stmt_check->close();

// Verificar senha
if (!verificar_senha($senha, $usuario_check['senha'])) {
    echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos']);
    exit;
}

// Verificar se conta está ativa
if (!$usuario_check['ativo']) {
    echo json_encode(['success' => false, 'message' => 'Sua conta está aguardando aprovação do administrador.']);
    exit;
}

// Agora pegar dados do usuário ativo
$sql = "SELECT id, nome, email, senha, ativo, is_admin FROM usuarios WHERE email = ? AND ativo = TRUE";
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

// Não precisa verificar senha novamente, já foi verificado acima

// Definir sessão
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];
$_SESSION['usuario_email'] = $usuario['email'];
// Definir flag is_admin na sessão. Se a coluna não existir, fallback para id==1
if (isset($usuario['is_admin'])) {
    $_SESSION['is_admin'] = (bool)$usuario['is_admin'];
} else {
    $_SESSION['is_admin'] = ($usuario['id'] == 1);
}

// Regenerar id da sessão após login para prevenir session fixation
if (function_exists('session_regenerate_id')) {
    session_regenerate_id(true);
}

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
