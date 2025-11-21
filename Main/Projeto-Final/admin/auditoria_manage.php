<?php
require_once __DIR__ . '/auth.php';
// Fragment: Auditoria (renderiza dentro de admin/index.php)
// Busca registros de exclusão e renderiza o conteúdo interno

// Apenas assume que autenticação e $conn foram tratados pelo shell (index.php)
$sql = "SELECT * FROM auditoria_exclusao ORDER BY data_exclusao DESC LIMIT 100";
$result = $conn->query($sql);
$exclusoes = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php if (empty($exclusoes)): ?>
    <div class="empty-state">
        <p>Nenhuma exclusão registrada</p>
    </div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Usuário Deletado</th>
                <th>Motivo da Exclusão</th>
                <th>Data de Exclusão</th>
                <th>Excluído Por</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($exclusoes as $item): ?>
            <tr>
                <td>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($item['nome_usuario']); ?></div>
                        <div class="user-email"><?php echo htmlspecialchars($item['email_usuario']); ?></div>
                    </div>
                </td>
                <td>
                    <div class="motivo"><?php echo htmlspecialchars($item['motivo_exclusao']); ?></div>
                </td>
                <td><?php echo formatar_data($item['data_exclusao']) . ' ' . formatar_hora($item['data_exclusao']); ?></td>
                <td><?php echo $item['excluido_por'] ? 'Admin ID: ' . $item['excluido_por'] : 'Sistema'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
