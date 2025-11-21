<?php
require_once __DIR__ . '/auth.php';
// Fragment: admin/acessos_manage.php
// Este arquivo é incluído por admin/index.php quando ?section=acessos

// Buscar últimos logs de acesso (máx 100)
$sql = "SELECT l.id, l.usuario_id, u.nome, u.email, l.data_acesso, l.ip_address
        FROM logs_acesso l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        ORDER BY l.data_acesso DESC
        LIMIT 100";
$result = $conn->query($sql);
$acessos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<div>
    <div class="card-title">Registros de Acesso</div>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Email</th>
                    <th>Data/Hora</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($acessos)): ?>
                    <tr><td colspan="4">Nenhum acesso registrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($acessos as $acesso): ?>
                        <tr>
                            <td class="user-name"><?php echo htmlspecialchars($acesso['nome'] ?: '—'); ?></td>
                            <td class="user-email"><?php echo htmlspecialchars($acesso['email'] ?: '—'); ?></td>
                            <td><?php echo formatar_data($acesso['data_acesso']) . ' ' . formatar_hora($acesso['data_acesso']); ?></td>
                            <td><?php echo htmlspecialchars($acesso['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
