<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validar dados
if (!isset($data['nome']) || !isset($data['email']) || !isset($data['renda']) || !isset($data['senha'])) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
    exit;
}

$nome = sanitizar($data['nome']);
$email = sanitizar($data['email']);
$renda = floatval($data['renda']);
$senha = $data['senha'];

// Validar email
if (!validar_email($email)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

// Validar senha
if (strlen($senha) < 6) {
    echo json_encode(['success' => false, 'message' => 'A senha deve ter no mínimo 6 caracteres']);
    exit;
}

// Verificar se email já existe
$sql = "SELECT id FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Este email já está registrado']);
    $stmt->close();
    exit;
}
$stmt->close();

// Gerar hash da senha
$senha_hash = gerar_hash_senha($senha);

// Inserir novo usuário
$sql = "INSERT INTO usuarios (nome, email, senha, renda_mensal) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar ao banco de dados']);
    exit;
}

$stmt->bind_param("sssd", $nome, $email, $senha_hash, $renda);
$stmt->execute();
$usuario_id = $stmt->insert_id;
$stmt->close();

if ($usuario_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Erro ao criar conta']);
    exit;
}

// Criar configurações padrão para o usuário
$sql = "INSERT INTO configuracoes_usuario (usuario_id) VALUES (?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$stmt->close();

// Definir sessão
$_SESSION['usuario_id'] = $usuario_id;
$_SESSION['usuario_nome'] = $nome;
$_SESSION['usuario_email'] = $email;

// Registrar primeiro acesso
registrar_log_acesso($usuario_id);

echo json_encode([
    'success' => true,
    'message' => 'Conta criada com sucesso',
    'usuario' => [
        'id' => $usuario_id,
        'nome' => $nome,
        'email' => $email
    ]
]);
?>
