<?php
// Centraliza a verificação de autenticação de admins
// Inclua este arquivo no topo de todas as páginas em /admin (exceto login-admin.php)
require_once __DIR__ . '/../config.php';

// Garante que a sessão esteja iniciada (config.php normalmente já faz isso)
if (session_status() === PHP_SESSION_NONE) session_start();

// Se não houver admin logado, redireciona para a tela de login de admins
if (!isset($_SESSION['admin_id'])) {
    // Redireciona sempre para o formulário de login do admin
    header('Location: login-admin.php');
    exit;
}

// Se chegou aqui, há um admin autenticado
?>
