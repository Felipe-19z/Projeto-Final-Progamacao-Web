<?php
require_once __DIR__ . '/auth.php';

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: listar-usuarios.php");
    exit;
}

// Buscar usuário
$usuario = obter_usuario($id);
if (!$usuario) {
    header("Location: listar-usuarios.php");
    exit;
}

$atualizado = false;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizar($_POST['nome'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $renda = floatval($_POST['renda'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (!$nome || !$email || !$renda) {
        $erro = 'Todos os campos são obrigatórios';
    } elseif (!validar_email($email)) {
        $erro = 'Email inválido';
    } else {
        $sql = "UPDATE usuarios SET nome = ?, email = ?, renda_mensal = ?, ativo = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdii", $nome, $email, $renda, $ativo, $id);
        $stmt->execute();
        $stmt->close();
        $atualizado = true;
        // Atualiza dados do usuário
        $usuario = obter_usuario($id);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        h1 { font-size: 24px; margin-bottom: 20px; }
        label { display: block; margin-top: 15px; font-weight: 600; }
        input[type="text"], input[type="email"], input[type="number"] { width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; margin-top: 5px; }
        .form-group { margin-bottom: 15px; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 700; margin-top: 20px; }
        .btn:hover { background: #764ba2; }
        .alert { background: #ffefef; color: #c33; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .success { background: #efe; color: #3c3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Usuário</h1>
        <?php if ($erro): ?>
            <div class="alert"><?php echo $erro; ?></div>
        <?php endif; ?>
        <?php if ($atualizado): ?>
            <div class="alert success">Usuário atualizado com sucesso!</div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="renda">Renda Mensal</label>
                <input type="number" name="renda" id="renda" step="0.01" value="<?php echo htmlspecialchars($usuario['renda_mensal']); ?>" required>
            </div>
            <div class="form-group">
                <label for="ativo">Ativo</label>
                <input type="checkbox" name="ativo" id="ativo" <?php echo $usuario['ativo'] ? 'checked' : ''; ?>>
            </div>
            <button type="submit" class="btn">Salvar Alterações</button>
        </form>
        <a href="listar-usuarios.php" style="display:block;margin-top:20px;">← Voltar para lista</a>
    </div>
</body>
</html>
