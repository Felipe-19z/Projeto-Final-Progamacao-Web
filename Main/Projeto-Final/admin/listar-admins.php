<?php
require_once __DIR__ . '/auth.php';

// Listar todos os admins
$sql = "SELECT id, nome, email, ativo, data_criacao FROM admin ORDER BY data_criacao DESC";
$result = $conn->query($sql);
$admins = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admins - Painel Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        h1 { font-size: 24px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f9f9f9; font-weight: 700; }
        tr:hover { background: #f1f1f1; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; padding: 8px 18px; border-radius: 8px; cursor: pointer; font-weight: 700; text-decoration: none; }
        .btn:hover { background: #764ba2; }
        .badge { padding: 5px 10px; border-radius: 5px; font-size: 11px; font-weight: 700; }
        .badge-ativo { background: #efe; color: #3c3; }
        .badge-inativo { background: #fee; color: #c33; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admins Cadastrados</h1>
        <a href="criar-admin.php" class="btn">➕ Novo Admin</a>
        <table>
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
                <?php foreach ($admins as $admin): ?>
                <tr>
                    <td><?php echo htmlspecialchars($admin['nome']); ?></td>
                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    <td><span class="badge <?php echo $admin['ativo'] ? 'badge-ativo' : 'badge-inativo'; ?>"><?php echo $admin['ativo'] ? 'Ativo' : 'Inativo'; ?></span></td>
                    <td><?php echo $admin['data_criacao']; ?></td>
                    <td>
                        <a href="editar-admin.php?id=<?php echo $admin['id']; ?>" class="btn">Editar</a>
                        <?php if ($admin['id'] != $_SESSION['usuario_id']): ?>
                        <form method="post" action="deletar-admin.php?id=<?php echo $admin['id']; ?>" style="display:inline" onsubmit="return confirm('Remover admin <?php echo addslashes(htmlspecialchars($admin['nome'])); ?>?');">
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
        <a href="../index.php" style="display:block;margin-top:20px;">← Voltar ao painel</a>
    </div>
</body>
</html>
