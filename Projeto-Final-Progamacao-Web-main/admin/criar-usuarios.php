<?php
require_once '../config.php';
verificar_login();
// Apenas admin
verificar_admin();
$usuario_id = $_SESSION['usuario_id'];

$criado = false;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizar($_POST['nome'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $renda = floatval($_POST['renda'] ?? 0);
    $senha = $_POST['senha'] ?? '';

    if (!$nome || !$email || !$renda || !$senha) {
        $erro = 'Todos os campos são obrigatórios';
    } elseif (!validar_email($email)) {
        $erro = 'Email inválido';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres';
    } else {
        // Verificar duplicata
        $sql = "SELECT id FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erro = 'Este email já está registrado';
            $stmt->close();
        } else {
            $stmt->close();

            // Criar usuário
            $senha_hash = gerar_hash_senha($senha);
            $sql = "INSERT INTO usuarios (nome, email, senha, renda_mensal) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssd", $nome, $email, $senha_hash, $renda);
            $stmt->execute();
            $novo_usuario_id = $stmt->insert_id;
            $stmt->close();

            // Criar configurações
            $sql = "INSERT INTO configuracoes_usuario (usuario_id) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $novo_usuario_id);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Usuário - Painel Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
        }

        .navbar-right {
            display: flex;
            gap: 20px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 600px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            animation: slideInUp 0.5s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .success-icon {
            font-size: 64px;
            text-align: center;
            margin-bottom: 20px;
            animation: pulse 0.6s ease-out;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.5);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-title {
            font-size: 24px;
            font-weight: 700;
            color: #3c3;
            text-align: center;
            margin-bottom: 10px;
        }

        .success-text {
            font-size: 14px;
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInDown 0.3s ease-out;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
        }

        .back-link:hover {
            color: #764ba2;
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-voltar {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-voltar:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        @media (max-width: 600px) {
            .container {
                margin: 20px auto;
            }

            .card {
                padding: 25px;
            }

            .card-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">➕ Criar Novo Usuário</div>
        <div class="navbar-right">
            <a href="listar-usuarios.php" class="nav-link">← Voltar</a>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="container">
        <?php if ($criado): ?>
        <!-- Sucesso -->
        <div class="card">
            <div class="success-icon">✓</div>
            <div class="success-title">Usuário Criado com Sucesso</div>
            <div class="success-text">
                O usuário foi criado e já pode fazer login no sistema.
            </div>
            <div class="button-group">
                <a href="criar-usuarios.php" class="btn-voltar">Criar Outro</a>
                <a href="listar-usuarios.php" class="btn-voltar">Ver Usuários</a>
            </div>
        </div>
        <?php else: ?>
        <!-- Formulário -->
        <div class="card">
            <h1 class="card-title">➕ Novo Usuário</h1>

            <?php if ($erro): ?>
            <div class="alert alert-error">
                ✕ <?php echo htmlspecialchars($erro); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" name="nome" class="form-input" placeholder="Ex: João Silva" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" placeholder="joao@email.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Renda Mensal (R$)</label>
                    <input type="number" name="renda" class="form-input" placeholder="0.00" step="0.01" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Senha</label>
                    <input type="password" name="senha" class="form-input" placeholder="••••••••" minlength="6" required>
                </div>

                <button type="submit" class="form-button">Criar Usuário</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
