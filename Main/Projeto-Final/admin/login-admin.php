<?php
require_once '../config.php';

// Se já está logado como admin, redireciona
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (!$email || !$senha) {
        $erro = 'Preencha todos os campos.';
    } else {
        $sql = "SELECT id, nome, senha, ativo FROM admin WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        if ($admin && $admin['ativo'] && password_verify($senha, $admin['senha'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nome'] = $admin['nome'];
            header('Location: index.php');
            exit;
        } else {
            $erro = 'Email ou senha inválidos.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <style>
    :root{
        --bg-gradient-1: #eef2ff;
        --bg-gradient-2: #e9f0ff;
        --accent-1: #5263e6; /* primary */
        --accent-2: #7b4aa6; /* secondary */
        --muted: #6b7280;
        --surface: #ffffff;
        --radius: 12px;
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body{
        margin:0;
        min-height:100vh;
        font-family: Inter, 'Segoe UI', Roboto, system-ui, Arial, sans-serif;
        color: #111827;
        background: linear-gradient(135deg,var(--bg-gradient-1) 0%, var(--bg-gradient-2) 100%);
        -webkit-font-smoothing:antialiased;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:32px;
    }

    .login-container{
        width:100%;
        max-width:420px;
        background:var(--surface);
        border-radius:var(--radius);
        box-shadow:0 10px 30px rgba(20,24,64,0.08);
        padding:36px 32px;
        display:flex;
        flex-direction:column;
        gap:14px;
        border:1px solid rgba(15,23,42,0.03);
    }

    .login-title{
        font-size:26px;
        font-weight:700;
        color:var(--accent-1);
        text-align:center;
        letter-spacing:0.4px;
    }

    .login-subtitle{font-size:13px;color:var(--muted);text-align:center;margin-top:-4px}

    .alert-error{
        background:#fff5f5;
        color:#7f1d1d;
        border-left:4px solid #f87171;
        padding:12px 14px;
        border-radius:8px;
        font-size:14px;
    }

    .form-group{display:flex;flex-direction:column;gap:8px}
    .form-label{font-size:13px;color:var(--muted);font-weight:600}

    .form-input{
        width:100%;
        padding:12px 14px;
        border-radius:10px;
        border:1px solid #e6e9ef;
        background:#fbfdff;
        font-size:15px;
        transition:box-shadow .18s ease, border-color .18s ease, transform .08s ease;
    }
    .form-input::placeholder{color:#9aa3b2}
    .form-input:focus{outline:none;border-color:var(--accent-1);box-shadow:0 6px 18px rgba(82,99,230,0.12);transform:translateY(-1px)}
    .form-input:focus:not(:focus-visible){box-shadow:none}

    .form-button{
        width:100%;
        padding:12px 14px;
        background:linear-gradient(90deg,var(--accent-1),var(--accent-2));
        color:#fff;
        border:none;
        border-radius:10px;
        font-weight:700;
        letter-spacing:0.6px;
        cursor:pointer;
        box-shadow:0 6px 20px rgba(82,99,230,0.12);
        transition:transform .14s ease, box-shadow .14s ease;
    }
    .form-button:hover{transform:translateY(-3px);box-shadow:0 14px 36px rgba(82,99,230,0.16)}
    .form-button:active{transform:translateY(-1px)}

    @media (max-width:420px){
        .login-container{padding:24px;border-radius:14px}
        .login-title{font-size:22px}
    }

    /* Utility helpers used across site */
    .container{max-width:1100px;margin:0 auto;padding:0 18px}
    .muted{color:var(--muted)}

    /* Espaçamento extra para o campo senha */
    .password-group{margin-top:14px;margin-bottom:18px}

    /* Pequeno espaçamento entre senha e botão */
    .form-button{margin-top:6px}

    /* Accessibility */
    :focus{outline:3px solid rgba(82,99,230,0.08);outline-offset:2px}
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-title">Login Admin</div>
        <?php if ($erro): ?>
            <div class="alert-error">✕ <?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" required autofocus>
            </div>
            <div class="form-group password-group">
                <label class="form-label">Senha</label>
                <input type="password" name="senha" class="form-input" required>
            </div>
            <button type="submit" class="form-button">Entrar</button>
        </form>
    </div>
</body>
</html>
