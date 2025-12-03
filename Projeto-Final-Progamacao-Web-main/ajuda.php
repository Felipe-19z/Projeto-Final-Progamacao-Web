<?php
require_once 'config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];
$usuario = obter_usuario($usuario_id);
$configuracoes = obter_configuracoes($usuario_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizar($_POST['nome'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $descricao = sanitizar($_POST['descricao'] ?? '');

    if (!$nome || !$email || !$descricao) {
        $erro = 'Por favor, preencha todos os campos';
    } elseif (!validar_email($email)) {
        $erro = 'Email inválido';
    } else {
        $sql = "INSERT INTO mensagens_ajuda (usuario_id, nome, email, descricao) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $usuario_id, $nome, $email, $descricao);
        $stmt->execute();
        $stmt->close();
        $sucesso = 'Mensagem enviada com sucesso! A equipe de suporte responderá em breve.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajuda - Controle de Gastos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: <?php echo $configuracoes['cor_fundo'] ?? '#f5f5f5'; ?>;
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
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 30px;
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
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-subtitle {
            font-size: 14px;
            color: #999;
            margin-bottom: 30px;
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

        .form-input, .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 150px;
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

        .alert-success {
            background: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
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

        .info-box {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-top: 30px;
        }

        .info-title {
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-text {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }

        .help-topics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }

        .help-topic {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .help-topic:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .help-topic-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .help-topic-title {
            font-weight: 700;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .card {
                padding: 20px;
            }

            .card-title {
                font-size: 24px;
            }

            .help-topics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">❓ Ajuda e Suporte</div>
        <div>
            <a href="index.php" class="nav-link">← Voltar</a>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="container">
        <div class="card">
            <h1 class="card-title">❓ Formulário de Suporte</h1>
            <p class="card-subtitle">Envie uma mensagem para nossa equipe de suporte</p>

            <?php if (isset($sucesso)): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($sucesso); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($erro)): ?>
            <div class="alert alert-error">
                ✕ <?php echo htmlspecialchars($erro); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-input" placeholder="Seu nome" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" placeholder="seu@email.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Descrição do Problema</label>
                    <textarea name="descricao" class="form-textarea" placeholder="Descreva seu problema em detalhes..." required></textarea>
                </div>

                <button type="submit" class="form-button">Enviar Mensagem</button>
            </form>

            <!-- Tópicos de Ajuda -->
            <div class="help-topics">
                <div class="help-topic">
                    <div class="help-topic-icon">💰</div>
                    <div class="help-topic-title">Como Adicionar Gastos</div>
                </div>
                <div class="help-topic">
                    <div class="help-topic-icon">📊</div>
                    <div class="help-topic-title">Entender Gráficos</div>
                </div>
                <div class="help-topic">
                    <div class="help-topic-icon">🎨</div>
                    <div class="help-topic-title">Personalizar Tema</div>
                </div>
                <div class="help-topic">
                    <div class="help-topic-icon">🔐</div>
                    <div class="help-topic-title">Segurança da Conta</div>
                </div>
            </div>

            <!-- Informações Adicionais -->
            <div class="info-box">
                <div class="info-title">📌 Informações Úteis</div>
                <div class="info-text">
                    <strong>Tempo de Resposta:</strong> Nossa equipe responde em até 24 horas.<br><br>
                    <strong>Horário de Funcionamento:</strong> De segunda a sexta, das 9h às 18h.<br><br>
                    <strong>Email de Suporte:</strong> suporte@controldegastos.com.br
                </div>
            </div>
        </div>
    </div>
</body>
</html>
