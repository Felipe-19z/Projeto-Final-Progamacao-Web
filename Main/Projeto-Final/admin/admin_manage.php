<?php
require_once __DIR__ . '/auth.php';
// Fragmento para gerenciar admins dentro de admin/index.php
// Requer: já foi incluído ../config.php e verificação de sessão no chamador.

// Mensagens flash simples
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['flash'])) $_SESSION['flash'] = null;

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        // fallback
        $_SESSION['csrf_token'] = bin2hex(substr(hash('sha256', uniqid('', true)), 0, 16));
    }
}

$flash = null;
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    $_SESSION['flash'] = null;
}

// Ações por POST
$action = $_GET['action'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $posted_token)) {
        $_SESSION['flash'] = 'Token CSRF inválido. Tente novamente.';
        echo '<script>window.location.href="index.php?section=admins";</script>';
        exit;
    }
    if (($action === 'create') || ($action === 'create_submit')) {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome && $email && $senha) {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "INSERT INTO admin (nome, email, senha, ativo, data_criacao) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssi', $nome, $email, $hash, $ativo);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash'] = 'Admin criado com sucesso.';
            echo '<script>window.location.href="index.php?section=admins";</script>';
            exit;
        } else {
            $flash = 'Preencha todos os campos obrigatórios.';
        }
    }

    if ($action === 'edit' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome && $email) {
            if (!empty($_POST['senha'])) {
                $hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                $sql = "UPDATE admin SET nome = ?, email = ?, senha = ?, ativo = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssii', $nome, $email, $hash, $ativo, $id);
            } else {
                $sql = "UPDATE admin SET nome = ?, email = ?, ativo = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssii', $nome, $email, $ativo, $id);
            }
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash'] = 'Admin atualizado com sucesso.';
            echo '<script>window.location.href="index.php?section=admins";</script>';
            exit;
        } else {
            $flash = 'Nome e email são obrigatórios.';
        }
    }

    if ($action === 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        // Evitar deletar a si mesmo
        if ($id == $_SESSION['usuario_id']) {
            $_SESSION['flash'] = 'Você não pode remover o próprio usuário.';
                echo '<script>window.location.href="index.php?section=admins";</script>';
                exit;
        }
        $sql = "DELETE FROM admin WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash'] = 'Admin removido com sucesso.';
        echo '<script>window.location.href="index.php?section=admins";</script>';
        exit;
    }

    if ($action === 'toggle' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        // Evitar desativar a si mesmo
        if ($id == $_SESSION['usuario_id']) {
            $_SESSION['flash'] = 'Você não pode alterar seu próprio status.';
            echo '<script>window.location.href="index.php?section=admins";</script>';
            exit;
        }
        // Ler estado atual
        $stmt = $conn->prepare("SELECT ativo FROM admin WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($res) {
            $novo = $res['ativo'] ? 0 : 1;
            $stmt = $conn->prepare("UPDATE admin SET ativo = ? WHERE id = ?");
            $stmt->bind_param('ii', $novo, $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash'] = 'Status alterado com sucesso.';
        } else {
            $_SESSION['flash'] = 'Registro não encontrado.';
        }
        echo '<script>window.location.href="index.php?section=admins";</script>';
        exit;
    }
}

// Preparar dados para exibição (listagem ou formulário)
$admins = [];
$sql = "SELECT id, nome, email, ativo, data_criacao FROM admin ORDER BY data_criacao DESC";
$result = $conn->query($sql);
if ($result) {
    $admins = $result->fetch_all(MYSQLI_ASSOC);
}

$editing = false;
$edit_admin = null;
if (($action === 'edit' || $action === 'delete') && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT id, nome, email, ativo, data_criacao FROM admin WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($action === 'edit' && $edit_admin) $editing = true;
}

?>

<style>
/* Admin manage visual improvements */
.card { padding: 20px; border-radius: 14px; }
.card .details summary { background:#f7f7f7; padding:10px 12px; border-radius:8px; }
.table thead th { background:#fafafa; color:#666; font-weight:700; }
.table tbody td { padding:12px; }
.badge-ativo { display:inline-block; padding:6px 12px; border-radius:999px; background: linear-gradient(135deg,#34d399,#10b981); color:#fff; box-shadow:0 6px 18px rgba(16,185,129,0.12); font-weight:700; }
.badge-inativo { display:inline-block; padding:6px 12px; border-radius:999px; background: linear-gradient(135deg,#f97373,#ef4444); color:#fff; box-shadow:0 6px 18px rgba(239,68,68,0.12); font-weight:700; }
.btn {
    padding: 8px 14px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    background: #667eea;
    color: white;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    min-width: 100px;
    height: 40px;
}
.btn-small {
    padding: 8px 14px;
    font-size: 14px;
    border-radius: 8px;
    min-width: 100px;
    height: 40px;
}
.action-form { display:inline-block; margin-left:8px; }
.details summary { list-style:none; }

/* Form styles similar to Criar Usuário */
.form-group { margin-bottom: 16px; }
.form-label { display:block; font-size:13px; font-weight:700; color:#333; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.6px; }
.form-input { width:100%; padding:10px 12px; border:2px solid #eaeaea; border-radius:8px; font-size:14px; }
.form-input:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 4px rgba(102,126,234,0.08); }
.form-button { display:inline-block; padding:10px 18px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border-radius:8px; border:none; font-weight:700; cursor:pointer; text-transform:uppercase; }
.card-title { font-size:20px; font-weight:700; margin-bottom:12px; }
</style>

    <?php if ($flash): ?>
        <div id="flash-message" style="padding:12px;background:#eef9ff;border-radius:8px;margin-bottom:12px;color:#055;"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <?php if ($editing && $edit_admin): ?>
        <div style="margin-bottom:16px; max-width:600px;">
            <div class="card-title">✎ Editar Admin</div>
            <form method="post" action="index.php?section=admins&action=edit&id=<?php echo $edit_admin['id']; ?>" style="margin-top:8px;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-group">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-input" value="<?php echo htmlspecialchars($edit_admin['nome']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($edit_admin['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Senha <small style="font-weight:400; text-transform:none;">(deixe em branco para manter)</small></label>
                    <input type="password" name="senha" class="form-input">
                </div>

                <div class="form-group">
                    <label style="font-weight:700;"><input type="checkbox" name="ativo" <?php echo $edit_admin['ativo'] ? 'checked' : ''; ?>> Ativo</label>
                </div>

                <button type="submit" class="form-button">Salvar Alterações</button>
                <a href="index.php?section=admins" class="btn" style="background:#999;margin-left:8px;">Cancelar</a>
            </form>
        </div>
    <?php else: ?>
        <!-- Formulário para criar novo admin -->
        <div style="margin-bottom:16px;">
            <button id="toggle-new-admin" class="btn" style="background:#fff;color:#333;border:1px solid #e6e6e6;padding:10px 14px;box-shadow:none;">➕ Novo Admin</button>

            <div id="new-admin-form" style="display:none; margin-top:12px; max-width:520px;">
                <form method="post" action="index.php?section=admins&action=create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label><input type="checkbox" name="ativo" checked> Ativo</label>
                    </div>

                    <button type="submit" class="form-button">Criar Admin</button>
                    <button type="button" id="cancel-new-admin" class="btn" style="background:#f0f0f0;color:#333;margin-left:8px;">Cancelar</button>
                </form>
            </div>

            <script>
                (function(){
                    const toggle = document.getElementById('toggle-new-admin');
                    const form = document.getElementById('new-admin-form');
                    const cancel = document.getElementById('cancel-new-admin');
                    if(toggle && form){
                        toggle.addEventListener('click', function(){
                            form.style.display = form.style.display === 'none' ? 'block' : 'none';
                            toggle.style.background = form.style.display === 'block' ? '#f7f7f7' : '#fff';
                        });
                    }
                    if(cancel){
                        cancel.addEventListener('click', function(){ form.style.display = 'none'; toggle.style.background = '#fff'; });
                    }
                })();
            </script>
        </div>

        <!-- Lista de admins -->
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Data Criação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $adm): ?>
                <tr>
                    <td><?php echo htmlspecialchars($adm['nome']); ?></td>
                    <td><?php echo htmlspecialchars($adm['email']); ?></td>
                    <td><?php echo $adm['ativo'] ? '<span class="badge badge-ativo">Ativo</span>' : '<span class="badge badge-inativo">Inativo</span>'; ?></td>
                    <td><?php echo $adm['data_criacao']; ?></td>
                    <td>
                        <a class="btn" href="index.php?section=admins&action=edit&id=<?php echo $adm['id']; ?>">Editar</a>

                        <?php if (!isset($_SESSION['admin_id']) || $adm['id'] != $_SESSION['admin_id']): ?>
                            <form method="post" action="index.php?section=admins&action=delete&id=<?php echo $adm['id']; ?>" style="display:inline" onsubmit="return confirm('Remover admin <?php echo addslashes(htmlspecialchars($adm['nome'])); ?>?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <button type="submit" class="btn" style="background:linear-gradient(135deg,#ff6b6b 0%,#ff5252 100%);margin-left:8px;">Remover</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#999;margin-left:8px;">(Você)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<script>
    // auto-hide flash after 10 seconds
    (function(){
        const el = document.getElementById('flash-message');
        if (!el) return;
        setTimeout(() => {
            el.style.transition = 'opacity 0.6s, max-height 0.6s, margin 0.6s';
            el.style.opacity = '0';
            el.style.maxHeight = '0';
            el.style.margin = '0';
            setTimeout(() => { if (el && el.parentNode) el.parentNode.removeChild(el); }, 700);
        }, 5000);
    })();
</script>

<?php
// fim do fragmento
