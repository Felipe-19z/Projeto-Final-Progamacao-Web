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
define('SITE_URL', 'http://localhost/Projeto-Final-Progamacao-Web-Parte-Vinicius/Main/Projeto-Final/');
define('ADMIN_URL', SITE_URL . 'admin/');

// Sessão
ini_set('session.cookie_lifetime', 3600); // 1 hora
session_start();

// =====================================================
// FUNÇÕES AUXILIARES
// =====================================================

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
        header("Location: " . SITE_URL . "login.php");
        exit();
    }
}

/**
 * Valida se usuário é admin
 */
function verificar_admin() {
    verificar_login();
    if ($_SESSION['user_role'] !== 'admin') {
        header("Location: " . SITE_URL . "index.php");
        exit();
    }
}

/**
 * Sanitiza entrada do usuário
 */
function sanitizar($input) {
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
?>
