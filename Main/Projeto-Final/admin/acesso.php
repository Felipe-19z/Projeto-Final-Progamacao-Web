<?php
require_once __DIR__ . '/auth.php';

// Buscar logs de acesso
$sql = "SELECT l.id, l.usuario_id, u.nome, u.email, l.data_acesso, l.ip_address AS ip_acesso
        FROM logs_acesso l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        ORDER BY l.data_acesso DESC
        LIMIT 100";
$result = $conn->query($sql);
$acessos = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel de Acessos</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        h1 { font-size: 24px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f9f9f9; font-weight: 700; }
        tr:hover { background: #f1f1f1; }
        .back { display: block; margin-top: 20px; color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Painel de Acessos</h1>
        <table>
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Email</th>
                    <th>Data/Hora</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($acessos as $acesso): ?>
                <tr>
                    <td><?php echo htmlspecialchars($acesso['nome']); ?></td>
                    <td><?php echo htmlspecialchars($acesso['email']); ?></td>
                    <td><?php echo formatar_data($acesso['data_acesso']) . ' ' . formatar_hora($acesso['data_acesso']); ?></td>
                    <td><?php echo htmlspecialchars($acesso['ip_acesso']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="index.php" class="back">← Voltar ao painel admin</a>
    </div>
</body>
</html>
