<?php
require_once __DIR__ . '/auth.php';

$criado = false;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizar($_POST['nome'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!$nome || !$email || !$senha) {
        $erro = 'Todos os campos são obrigatórios';
    } elseif (!validar_email($email)) {
        $erro = 'Email inválido';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres';
    } else {
        // Verificar duplicata
        $sql = "SELECT id FROM admin WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erro = 'Este email já está registrado';
            $stmt->close();
        } else {
            $stmt->close();
            // Criar admin
            $senha_hash = gerar_hash_senha($senha);
            $sql = "INSERT INTO admin (nome, email, senha) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $nome, $email, $senha_hash);
            $stmt->execute();
            $stmt->close();
            $criado = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Criar Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        h1 { font-size: 24px; margin-bottom: 20px; }
        label { display: block; margin-top: 15px; font-weight: 600; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; margin-top: 5px; }
        .form-group { margin-bottom: 15px; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 700; margin-top: 20px; }
        .btn:hover { background: #764ba2; }
        .alert { background: #ffefef; color: #c33; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .success { background: #efe; color: #3c3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Criar Admin</h1>
        <?php if ($erro): ?>
            <div class="alert"><?php echo $erro; ?></div>
        <?php endif; ?>
        <?php if ($criado): ?>
            <div class="alert success">Admin criado com sucesso!</div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" name="nome" id="nome" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" name="senha" id="senha" required>
            </div>
            <button type="submit" class="btn">Criar Admin</button>
        </form>
        <a href="listar-admins.php" style="display:block;margin-top:20px;">← Voltar para lista</a>
    </div>
</body>
</html>
