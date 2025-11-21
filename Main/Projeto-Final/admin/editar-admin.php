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

$atualizado = false;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizar($_POST['nome'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (!$nome || !$email) {
        $erro = 'Todos os campos são obrigatórios';
    } elseif (!validar_email($email)) {
        $erro = 'Email inválido';
    } else {
        $sql = "UPDATE admin SET nome = ?, email = ?, ativo = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $nome, $email, $ativo, $id);
        $stmt->execute();
        $stmt->close();
        $atualizado = true;
        // Atualiza dados do admin
        $sql = "SELECT * FROM admin WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        h1 { font-size: 24px; margin-bottom: 20px; }
        label { display: block; margin-top: 15px; font-weight: 600; }
        input[type="text"], input[type="email"] { width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; margin-top: 5px; }
        .form-group { margin-bottom: 15px; }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            width: 100%;
            height: 48px;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            box-sizing: border-box;
            transition: background 0.2s;
            padding: 0;
        }
        .btn:hover { background: #764ba2; }
        .btn.cancelar {
            background: #aaa !important;
            color: #fff !important;
            text-decoration: none;
        }
        .alert { background: #ffefef; color: #c33; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .success { background: #efe; color: #3c3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Admin</h1>
        <?php if ($erro): ?>
            <div class="alert"><?php echo $erro; ?></div>
        <?php endif; ?>
        <?php if ($atualizado): ?>
            <div class="alert success">Admin atualizado com sucesso!</div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($admin['nome']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="ativo">Ativo</label>
                <input type="checkbox" name="ativo" id="ativo" <?php echo $admin['ativo'] ? 'checked' : ''; ?>>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 20px; align-items: stretch;">
                <button type="submit" class="btn" style="flex:1;">Salvar Alterações</button>
                <a href="listar-admins.php" class="btn cancelar" style="flex:1;">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
