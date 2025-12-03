<?php
require_once 'config.php';
verificar_login();
if (!isset($_SESSION['is_premium']) || !$_SESSION['is_premium']) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Processando Estatísticas</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;margin:0;padding:40px}
.center{max-width:800px;margin:0 auto;text-align:center}
.loader{width:80px;height:80px;border-radius:50%;border:8px solid #eee;border-top-color:#667eea;margin:40px auto;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>
    <div class="center">
        <h2>Aguarde...</h2>
        <div class="loader"></div>
        <p>Estamos processando seus gastos...</p>
    </div>
<script>
setTimeout(function(){ window.location.href = 'stats.php'; }, 5000);
</script>
</body>
</html>