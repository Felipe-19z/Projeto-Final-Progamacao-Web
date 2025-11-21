<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Controle de Gastos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            width: 100%;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .container-auth {
            width: 100%;
            max-width: 450px;
            padding: 20px;
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .logo-auth {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .logo-auth svg {
            width: 40px;
            height: 40px;
            fill: white;
        }

        .auth-title {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .auth-subtitle {
            text-align: center;
            font-size: 14px;
            color: #999;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.name-email {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group.name-email .form-input {
            width: 100%;
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
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-input::placeholder {
            color: #bbb;
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
            margin-top: 10px;
        }

        .form-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .form-button:active {
            transform: translateY(0);
        }

        .form-toggle {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .form-toggle a {
            color: #667eea;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .form-toggle a:hover {
            color: #764ba2;
        }

        .auth-form {
            display: none;
        }

        .auth-form.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideInDown 0.3s ease-out;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
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

        .password-toggle {
            position: relative;
        }

        .toggle-btn {
            position: absolute;
            right: 12px;
            top: 40px;
            background: none;
            border: none;
            cursor: pointer;
            color: #667eea;
            font-size: 18px;
            padding: 5px;
        }

        /* Responsivo */
        @media (max-width: 600px) {
            .auth-card {
                padding: 30px 20px;
            }

            .form-group.name-email {
                grid-template-columns: 1fr;
            }

            .auth-title {
                font-size: 24px;
            }

            .logo-auth {
                width: 60px;
                height: 60px;
            }

            .logo-auth svg {
                width: 30px;
                height: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container-auth">
        <div class="auth-card">
            <!-- Logo -->
            <div class="logo-auth">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
                </svg>
            </div>

            <!-- Formulário de Login -->
            <form id="loginForm" class="auth-form active" method="POST">
                <h2 class="auth-title">Bem-vindo</h2>
                <p class="auth-subtitle">Faça login na sua conta</p>

                <div id="loginAlert"></div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" id="loginEmail" class="form-input" placeholder="seu@email.com" required>
                </div>

                <div class="form-group password-toggle">
                    <label class="form-label">Senha</label>
                    <input type="password" id="loginSenha" class="form-input" placeholder="••••••••" required>
                    <button type="button" class="toggle-btn" onclick="togglePassword('loginSenha')">👁️</button>
                </div>

                <button type="button" class="form-button" onclick="fazerLogin()">Entrar</button>

                <div class="form-toggle">
                    Não tem conta? <a onclick="trocarForm()">Registrar-se</a>
                </div>
            </form>

            <!-- Formulário de Registro -->
            <form id="registerForm" class="auth-form" method="POST">
                <h2 class="auth-title">Criar Conta</h2>
                <p class="auth-subtitle">Registre-se para começar</p>

                <div id="registerAlert"></div>

                <div class="form-group name-email">
                    <div class="form-input">
                        <label class="form-label">Nome</label>
                        <input type="text" id="registerNome" class="form-input" placeholder="Seu nome" required>
                    </div>
                    <div class="form-input">
                        <label class="form-label">Email</label>
                        <input type="email" id="registerEmail" class="form-input" placeholder="seu@email.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Renda Mensal (R$)</label>
                    <input type="number" id="registerRenda" class="form-input" placeholder="0.00" step="0.01" required>
                </div>

                <div class="form-group password-toggle">
                    <label class="form-label">Senha</label>
                    <input type="password" id="registerSenha" class="form-input" placeholder="••••••••" required>
                    <button type="button" class="toggle-btn" onclick="togglePassword('registerSenha')">👁️</button>
                </div>

                <div class="form-group password-toggle">
                    <label class="form-label">Confirmar Senha</label>
                    <input type="password" id="registerConfirm" class="form-input" placeholder="••••••••" required>
                    <button type="button" class="toggle-btn" onclick="togglePassword('registerConfirm')">👁️</button>
                </div>

                <button type="button" class="form-button" onclick="fazerRegistro()">Registrar</button>

                <div class="form-toggle">
                    Já tem conta? <a onclick="trocarForm()">Fazer login</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Trocar entre Login e Registro
        function trocarForm() {
            document.getElementById('loginForm').classList.toggle('active');
            document.getElementById('registerForm').classList.toggle('active');
            document.getElementById('loginAlert').innerHTML = '';
            document.getElementById('registerAlert').innerHTML = '';
        }

        // Toggle visibilidade de senha
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        // Fazer Login
        async function fazerLogin() {
            const email = document.getElementById('loginEmail').value;
            const senha = document.getElementById('loginSenha').value;

            if (!email || !senha) {
                mostrarAlerta('loginAlert', 'Por favor, preencha todos os campos', 'error');
                return;
            }

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, senha })
                });

                const data = await response.json();

                if (data.success) {
                    mostrarAlerta('loginAlert', 'Login realizado! Redirecionando...', 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    mostrarAlerta('loginAlert', data.message, 'error');
                }
            } catch (error) {
                mostrarAlerta('loginAlert', 'Erro ao conectar ao servidor', 'error');
            }
        }

        // Fazer Registro
        async function fazerRegistro() {
            const nome = document.getElementById('registerNome').value;
            const email = document.getElementById('registerEmail').value;
            const renda = document.getElementById('registerRenda').value;
            const senha = document.getElementById('registerSenha').value;
            const confirm = document.getElementById('registerConfirm').value;

            if (!nome || !email || !renda || !senha || !confirm) {
                mostrarAlerta('registerAlert', 'Por favor, preencha todos os campos', 'error');
                return;
            }

            if (senha !== confirm) {
                mostrarAlerta('registerAlert', 'As senhas não coincidem', 'error');
                return;
            }

            if (senha.length < 6) {
                mostrarAlerta('registerAlert', 'A senha deve ter no mínimo 6 caracteres', 'error');
                return;
            }

            try {
                const response = await fetch('api/registrar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ nome, email, renda, senha })
                });

                const data = await response.json();

                if (data.success) {
                    mostrarAlerta('registerAlert', 'Conta criada com sucesso! Fazendo login...', 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    mostrarAlerta('registerAlert', data.message, 'error');
                }
            } catch (error) {
                mostrarAlerta('registerAlert', 'Erro ao conectar ao servidor', 'error');
            }
        }

        // Mostrar alerta
        function mostrarAlerta(elementId, mensagem, tipo) {
            const alert = document.getElementById(elementId);
            alert.innerHTML = `<div class="alert alert-${tipo}">${mensagem}</div>`;
        }

        // Enter para enviar
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('loginEmail').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') fazerLogin();
            });
            document.getElementById('loginSenha').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') fazerLogin();
            });
            document.getElementById('registerSenha').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') fazerRegistro();
            });
            document.getElementById('registerConfirm').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') fazerRegistro();
            });
        });
    </script>
</body>
</html>
