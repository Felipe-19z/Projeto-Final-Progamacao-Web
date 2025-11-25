<?php
require_once '../config.php';
// Limpar variáveis de sessão
$_SESSION = [];

// Remover cookie de sessão
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params['path'], $params['domain'], $params['secure'], $params['httponly']
	);
}

// Destruir sessão no servidor
session_destroy();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Logout realizado']);
exit;
?>
