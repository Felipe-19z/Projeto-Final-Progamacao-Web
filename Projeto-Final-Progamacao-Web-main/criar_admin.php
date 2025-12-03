<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Dados da conta admin
        $nome = 'Admin';
        $email = 'usuarioadmin@hotmail.com';
        $senha = '123456';
        $renda = 0.00;
        
        // Gerar hash seguro da senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        // Verificar se email já existe
        $checkEmail = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $resEmail = $checkEmail->get_result();
        
        if ($resEmail->num_rows > 0) {
            $msg_erro = "Email já existe! Deletando...";
            // Deletar usuário anterior e criar novo
            $conn->query("DELETE FROM usuarios WHERE email = 'usuarioadmin@hotmail.com'");
        }
        $checkEmail->close();
        
        // Inserir conta admin
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, renda_mensal, is_admin, ativo, data_criacao) VALUES (?, ?, ?, ?, TRUE, TRUE, NOW())");
        if (!$stmt) {
            throw new Exception("Erro ao preparar statement: " . $conn->error);
        }
        
        $stmt->bind_param("sssd", $nome, $email, $senha_hash, $renda);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao criar conta: " . $stmt->error);
        }
        
        $stmt->close();
        
        echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; font-family: Arial;'>";
        echo "<h2>✓ Conta Admin Criada com Sucesso!</h2>";
        echo "<p><strong>Email:</strong> usuarioadmin@hotmail.com</p>";
        echo "<p><strong>Senha:</strong> 123456</p>";
        echo "<p><strong>Hash (para referência):</strong> " . htmlspecialchars($senha_hash) . "</p>";
        echo "<p><a href='login.php' style='color: #155724; font-weight: bold;'>Ir para Login</a></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; font-family: Arial;'>";
        echo "<h2>✗ Erro ao criar conta</h2>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
} else {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .info-box {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            color: #004085;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .info-box p {
            margin: 5px 0;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Criar Conta Admin</h1>
        <div class="info-box">
            <p><strong>Será criada uma conta de administrador com:</strong></p>
            <p><strong>Email:</strong> usuarioadmin@hotmail.com</p>
            <p><strong>Senha:</strong> 123456</p>
        </div>
        <form method="POST">
            <button type="submit">Criar Conta Admin</button>
        </form>
    </div>
</body>
</html>
<?php } ?>
