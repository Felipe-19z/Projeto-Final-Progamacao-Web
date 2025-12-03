<?php
require_once '../config.php';
verificar_login();
// Apenas admin
verificar_admin();
$usuario_id = $_SESSION['usuario_id'];

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: listar-usuarios.php");
    exit;
}

// Buscar usuário
$usuario = obter_usuario($id);
if (!$usuario) {
    header("Location: listar-usuarios.php");
    exit;
}

// Verificar se este usuário é admin e se é o ÚNICO admin
$cannot_delete_last_admin = false;
$admin_count = 0;
$stmt_admin = $conn->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE is_admin = 1");
if ($stmt_admin) {
    $stmt_admin->execute();
    $res_admin = $stmt_admin->get_result();
    $row_admin = $res_admin->fetch_assoc();
    $admin_count = intval($row_admin['total'] ?? 0);
    $stmt_admin->close();
}

if (!empty($usuario['is_admin']) && $admin_count <= 1) {
    $cannot_delete_last_admin = true;
}

$deletado = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo = sanitizar($_POST['motivo'] ?? '');

    // Se não for permitido deletar último admin, impedir
    if ($cannot_delete_last_admin) {
        $deletado = false;
    } else {
        // Registrar na auditoria
        $sql = "INSERT INTO auditoria_exclusao (usuario_id, nome_usuario, email_usuario, motivo_exclusao, excluido_por)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssi", $id, $usuario['nome'], $usuario['email'], $motivo, $usuario_id);
        $stmt->execute();
        $stmt->close();

        // Deletar usuário
        $sql = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $deletado = true;
    }

    // Registrar na auditoria
    $sql = "INSERT INTO auditoria_exclusao (usuario_id, nome_usuario, email_usuario, motivo_exclusao, excluido_por)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $id, $usuario['nome'], $usuario['email'], $motivo, $usuario_id);
    $stmt->execute();
    $stmt->close();

    // Deletar usuário
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $deletado = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deletar Usuário - Painel Admin</title>
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
            text-align: center;
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

        .success-icon {
            font-size: 64px;
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
            margin-bottom: 10px;
        }

        .success-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .card-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
        }

        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: left;
        }

        .warning-title {
            font-weight: 700;
            color: #cc3;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .warning-text {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }

        .info-item {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: left;
            border-left: 4px solid #667eea;
        }

        .info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
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

        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-cancel {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: #ff5252;
            transform: translateY(-2px);
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

        .alert-success {
            background: #efe;
            border: 2px solid #3c3;
            color: #3c3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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

            .button-group {
                flex-direction: column;
            }

            .btn-cancel,
            .btn-delete,
            .btn-voltar {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">⚠️ Deletar Usuário</div>
    </div>

    <!-- Conteúdo -->
    <div class="container">
        <?php if ($deletado): ?>
        <!-- Sucesso -->
        <div class="card">
            <div class="success-icon">✓</div>
            <div class="success-title">Usuário Deletado com Sucesso</div>
            <div class="success-text">
                O usuário <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong> foi removido do sistema.
                Todos os dados associados foram arquivados para auditoria.
            </div>
            <a href="listar-usuarios.php" class="btn-voltar">Voltar para Lista de Usuários</a>
        </div>
        <?php else: ?>
        <!-- Confirmação -->
        <div class="card">
            <h1 class="card-title">Confirmar Exclusão</h1>

            <div class="warning-box">
                <div class="warning-title">⚠️ ATENÇÃO - AÇÃO IRREVERSÍVEL</div>
                <div class="warning-text">
                    Você está prestes a deletar este usuário. Esta ação não pode ser desfeita.
                    Todos os dados do usuário, incluindo gastos e configurações, serão removidos permanentemente.
                </div>
            </div>

                <?php if ($cannot_delete_last_admin): ?>
                <div class="info-item" style="border-left:4px solid #c33; background:#fff3f3;">
                    <div class="info-label">Proteção contra perda de administração</div>
                    <div class="info-value">Este usuário é o único administrador do sistema. Para evitar perda de acesso administrativo, a exclusão está bloqueada.
                    Para prosseguir, promova outro usuário a administrador ou crie um novo admin diretamente no banco de dados. Se precisar, você pode remover o arquivo <code>setup.lock</code> e executar o setup novamente (apenas em ambiente de desenvolvimento).</div>
                </div>
                <?php endif; ?>

            <div class="info-item">
                <div class="info-label">Nome do Usuário</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['nome']); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['email']); ?></div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Motivo da Exclusão (obrigatório)</label>
                    <textarea name="motivo" class="form-textarea" placeholder="Por que este usuário está sendo deletado?" required></textarea>
                </div>

                <div class="button-group">
                    <a href="ler-usuarios.php?id=<?php echo $id; ?>" class="btn-cancel">Cancelar</a>
                    <button type="button" id="btnDeleteUser" class="btn-delete" <?php if ($cannot_delete_last_admin) echo 'disabled'; ?>>
                        Deletar Usuário
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Confirm modal centralizado em `assets/js/main.js` (footer). Fallback abaixo para compatibilidade. -->
    <script>
        if (typeof window.showConfirm !== 'function') {
            window.showConfirm = function(message, title = 'Confirmar ação') {
                return new Promise(resolve => {
                    const ok = confirm(title + "\n\n" + message);
                    resolve(Boolean(ok));
                });
            };
        }

        document.getElementById('btnDeleteUser').addEventListener('click', async function() {
            if (this.disabled) return;
            const ok = await window.showConfirm('ATENÇÃO: Esta ação é irreversível. Tem certeza que deseja deletar este usuário?', 'Deletar Usuário');
            if (!ok) return;
            document.querySelector('form').submit();
        });
    </script>
</body>
</html>
