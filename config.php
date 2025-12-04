<?php
// =====================================================
// CONFIGURAÇÃO DO BANCO DE DADOS
// =====================================================

// Definir o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'controle_gastos');

// Tentar conectar ao banco de dados
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verificar conexão
    if ($conn->connect_error) {
        throw new Exception("Erro de Conexão: " . $conn->connect_error);
    }
    
    // Configurar charset para UTF-8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// =====================================================
// CONFIGURAÇÕES GERAIS
// =====================================================
define('SITE_NAME', 'Controle de Gastos');
define('SITE_URL', 'http://localhost/Projeto-Final/');
define('ADMIN_URL', SITE_URL . 'admin/');

// Sessão
ini_set('session.cookie_lifetime', 3600); // 1 hora
session_start();

// =====================================================
// FUNÇÕES AUXILIARES
// =====================================================

// Função utilitária para retornar JSON limpo ao cliente.
// Remove qualquer conteúdo de buffers de saída que possa ter sido gerado
// (avisos, warnings, HTML) e envia um JSON válido com charset.
function api_json($data) {
    // limpar todos os buffers de saída
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}


/**
 * Registra log de acesso do usuário
 */
function registrar_log_acesso($usuario_id) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $sql = "INSERT INTO logs_acesso (usuario_id, ip_address) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $usuario_id, $ip);
    $stmt->execute();
    $stmt->close();
}

/**
 * Valida se usuário está logado
 */
function verificar_login() {
    if (!isset($_SESSION['usuario_id'])) {
        // Detectar requisições de API/ajax para retornar JSON em vez de redirecionar.
        $isApiRequest = false;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (stripos($uri, '/api/') !== false) $isApiRequest = true;
        if (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) $isApiRequest = true;
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') $isApiRequest = true;

        if ($isApiRequest) {
            api_json(['success' => false, 'message' => 'Usuário não autenticado', 'code' => 'unauthenticated']);
        }

        header("Location: " . SITE_URL . "login.php");
        exit();
    }
}

/**
 * Valida se usuário é admin
 */
function verificar_admin() {
    verificar_login();
    // Verifica flag is_admin na sessão (fallback para id==1)
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        // Se for chamada via API, retornar JSON de erro em vez de redirecionar
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiRequest = (stripos($uri, '/api/') !== false) || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        if ($isApiRequest) {
            api_json(['success' => false, 'message' => 'Ação restrita a administradores', 'code' => 'forbidden']);
        }
        header("Location: " . SITE_URL . "index.php");
        exit();
    }
}

/**
 * Sanitiza entrada do usuário
 */
function sanitizar($input) {
    // Garantir que sempre trabalhamos com string (evita warnings ao chamar trim(null))
    if (is_null($input)) $input = '';
    if (!is_string($input)) $input = (string)$input;
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida email
 */
function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Gera hash de senha
 */
function gerar_hash_senha($senha) {
    return password_hash($senha, PASSWORD_BCRYPT);
}

/**
 * Verifica hash de senha
 */
function verificar_senha($senha, $hash) {
    return password_verify($senha, $hash);
}

/**
 * Busca informações do usuário
 */
function obter_usuario($usuario_id) {
    global $conn;
    $sql = "SELECT * FROM usuarios WHERE id = ? AND ativo = TRUE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    return $usuario;
}

/**
 * Busca configurações do usuário
 */
function obter_configuracoes($usuario_id) {
    global $conn;
    $sql = "SELECT * FROM configuracoes_usuario WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $config = $result->fetch_assoc();
    $stmt->close();
    return $config;
}

/**
 * Formata valor em moeda BRL
 */
function formatar_moeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}

/**
 * Formata data para formato brasileiro
 */
function formatar_data($data) {
    return date('d/m/Y', strtotime($data));
}

/**
 * Formata hora
 */
function formatar_hora($hora) {
    return date('H:i', strtotime($hora));
}

// =====================================================
// FIM DO ARQUIVO
// =====================================================
