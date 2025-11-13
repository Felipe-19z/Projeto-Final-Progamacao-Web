<?php
/**
 * ARQUIVO DE SETUP - Execute esta página APENAS UMA VEZ para inicializar o sistema
 * Após a execução, DELETE este arquivo por questões de segurança!
 * 
 * URL: http://localhost/Projeto-Final/setup.php
 */

// Prevenir execução múltipla
if (file_exists(__DIR__ . '/setup.lock')) {
    die('<h1>✗ Setup já foi executado</h1><p>Este arquivo não pode ser executado novamente.</p><a href="login.php">Ir para Login</a>');
}

// Verificar credenciais do banco
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'controle_gastos');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        throw new Exception("Erro de Conexão: " . $conn->connect_error);
    }
    
    // Verificar se banco existe
    $result = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    $banco_existe = $result->num_rows > 0;
    
} catch (Exception $e) {
    die("<h1>❌ Erro ao Conectar</h1><p>" . $e->getMessage() . "</p>");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Controle de Gastos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            font-size: 14px;
            color: #999;
            text-align: center;
            margin-bottom: 30px;
        }

        .step {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .step-title {
            font-size: 14px;
            font-weight: 700;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .step-content {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }

        .status {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }

        .status-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: white;
        }

        .status-ok {
            background: #3c3;
        }

        .status-erro {
            background: #c33;
        }

        .status-text {
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .alert-info {
            background: #e3f2fd;
            color: #1976d2;
            border-left: 4px solid #1976d2;
        }

        .alert-success {
            background: #e8f5e9;
            color: #388e3c;
            border-left: 4px solid #388e3c;
        }

        .alert-warning {
            background: #fff3e0;
            color: #f57c00;
            border-left: 4px solid #f57c00;
        }

        .code {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            color: #333;
            margin: 10px 0;
            overflow-x: auto;
        }

        @media (max-width: 600px) {
            .container {
                padding: 25px;
            }

            h1 {
                font-size: 24px;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <path fill="url(#grad)" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
            </svg>
            <h1>Controle de Gastos</h1>
            <p class="subtitle">Sistema de Setup Inicial</p>
        </div>

        <?php if (!$banco_existe): ?>
        <div class="alert alert-warning">
            ⚠️ Banco de dados não encontrado. Execute o script SQL primeiro!
        </div>
        <?php endif; ?>

        <form method="POST" action="setup-process.php">
            <!-- Passo 1: Verificação -->
            <div class="step">
                <div class="step-title">✓ Verificação do Sistema</div>
                <div class="step-content">
                    <div class="status">
                        <div class="status-icon status-ok">✓</div>
                        <div class="status-text">PHP <?php echo phpversion(); ?> detectado</div>
                    </div>
                    <div class="status">
                        <div class="status-icon <?php echo extension_loaded('mysqli') ? 'status-ok' : 'status-erro'; ?>">
                            <?php echo extension_loaded('mysqli') ? '✓' : '✕'; ?>
                        </div>
                        <div class="status-text">MySQLi <?php echo extension_loaded('mysqli') ? 'disponível' : 'não disponível'; ?></div>
                    </div>
                    <div class="status">
                        <div class="status-icon <?php echo $banco_existe ? 'status-ok' : 'status-erro'; ?>">
                            <?php echo $banco_existe ? '✓' : '✕'; ?>
                        </div>
                        <div class="status-text">Banco "<?php echo DB_NAME; ?>" <?php echo $banco_existe ? 'encontrado' : 'não encontrado'; ?></div>
                    </div>
                </div>
            </div>

            <?php if ($banco_existe): ?>
            <!-- Passo 2: Criar Admin -->
            <div class="step">
                <div class="step-title">➕ Criar Conta Admin</div>
                <div class="step-content">
                    <p style="margin-bottom: 15px;">Preencha os dados da conta de administrador:</p>

                    <div class="form-group">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="nome" class="form-input" placeholder="Seu Nome" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" placeholder="admin@email.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Renda Mensal (R$)</label>
                        <input type="number" name="renda" class="form-input" placeholder="0.00" step="0.01" value="5000.00" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Senha (mínimo 6 caracteres)</label>
                        <input type="password" name="senha" class="form-input" placeholder="••••••••" minlength="6" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password" name="senha_confirm" class="form-input" placeholder="••••••••" minlength="6" required>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                ℹ️ Após o setup, este arquivo será bloqueado. Você pode usar o admin para criar outros usuários.
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">Inicializar Sistema</button>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                ⚠️ Por favor, crie o banco de dados primeiro executando o script SQL em phpMyAdmin
            </div>
            <div class="step">
                <div class="step-title">📝 Como Criar o Banco</div>
                <div class="step-content">
                    <p style="margin-bottom: 10px;"><strong>1. Abra phpMyAdmin</strong></p>
                    <p style="margin-bottom: 10px;"><code style="background: #f5f5f5; padding: 2px 5px;">http://localhost/phpmyadmin</code></p>
                    
                    <p style="margin-bottom: 10px; margin-top: 15px;"><strong>2. Clique em "SQL"</strong></p>
                    
                    <p style="margin-bottom: 10px; margin-top: 15px;"><strong>3. Cole o conteúdo de <code>database.sql</code></strong></p>
                    
                    <p style="margin-bottom: 10px; margin-top: 15px;"><strong>4. Clique em "Executar"</strong></p>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
