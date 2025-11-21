<?php
require_once __DIR__ . '/auth.php';
// Fragmento usado dentro de listar-usuarios.php quando ?novo=1

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

<style>
    /* Estilos com escopo definido para o fragmento para manter a aparência consistente */
    /* Visual igual ao .card da listagem */
    .fragment-container {
        width: 100%;
        margin: 0 0 30px 0;
    }
    .fragment-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
    }
    .card-title { font-size: 22px; font-weight:700; margin-bottom:18px; }
    .form-group { margin-bottom: 14px; }
    .form-label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:#333; text-transform:uppercase; }
    .form-input { width:100%; padding:10px 12px; border:2px solid #e0e0e0; border-radius:8px; }
    .form-input:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,0.08); }
    .form-button { padding:12px 18px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; }
    .alert-error { background:#fee; color:#c33; padding:10px 12px; border-radius:8px; margin-bottom:12px; }
    .button-group { display:flex; gap:10px; margin-top:14px; }
    .btn-voltar { background:#667eea; color:white; padding:8px 14px; border-radius:8px; text-decoration:none; font-weight:700; }
</style>

<div class="fragment-container">
<?php if ($criado): ?>
    <div class="fragment-card">
        <div style="font-size:48px; text-align:center; color:#3c3;">✓</div>
        <h3 style="text-align:center; color:#3c3;">Usuário Criado com Sucesso</h3>
        <p style="text-align:center; color:#666;">O usuário foi criado e já pode fazer login no sistema.</p>
        <div class="button-group" style="justify-content:center;">
            <a href="?novo=1" class="btn-voltar">Criar Outro</a>
            <a href="listar-usuarios.php" class="btn-voltar">Ver Usuários</a>
        </div>
    </div>
<?php else: ?>
    <div class="fragment-card">
        <h2 class="card-title">➕ Novo Usuário</h2>
        <?php if ($erro): ?>
            <div class="alert-error">✕ <?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form method="POST" action="?novo=1">
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

