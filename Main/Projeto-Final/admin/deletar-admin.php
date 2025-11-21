<?php
require_once __DIR__ . '/auth.php';

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: listar-admins.php");
    exit;
}

// Buscar admin
$sql = "SELECT * FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    header("Location: listar-admins.php");
    exit;
}

$deletado = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "DELETE FROM admin WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $deletado = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Deletar Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        h1 { font-size: 24px; margin-bottom: 20px; }
        .btn { background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%); color: #fff; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 700; margin-top: 20px; }
        .btn:hover { background: #ff5252; }
        .alert { background: #ffefef; color: #c33; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .success { background: #efe; color: #3c3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Deletar Admin</h1>
        <?php if ($deletado): ?>
            <div class="alert success">Admin deletado com sucesso!</div>
            <a href="listar-admins.php" style="display:block;margin-top:20px;">← Voltar para lista</a>
        <?php else: ?>
            <form method="post">
                <p>Tem certeza que deseja deletar o admin <strong><?php echo htmlspecialchars($admin['nome']); ?></strong>?</p>
                <button type="submit" class="btn">Deletar</button>
            </form>
            <a href="listar-admins.php" style="display:block;margin-top:20px;">← Voltar para lista</a>
        <?php endif; ?>
    </div>
</body>
</html>
