<?php
require_once __DIR__ . '/admin/config.inc.php';

// Página pública que lista consultas
?>
<div class="container-custom">
    <h1>Consultas</h1>
    <?php
    $sql = "SELECT c.id, c.cliente_id, c.data, c.hora, c.status, cl.cliente as nome_cliente
            FROM consultas c
            LEFT JOIN clientes cl ON cl.id = c.cliente_id
            ORDER BY c.data, c.hora";
    $res = @mysqli_query($conexao, $sql);
    if (!$res) {
        echo "<p class='p-muted'>Nenhuma consulta encontrada ou erro na consulta ao banco.</p>";
    } else {
        if (mysqli_num_rows($res) == 0) {
            echo "<p class='p-muted'>Nenhuma consulta registrada.</p>";
        } else {
            echo "<table>
                    <thead>
                        <tr><th>ID</th><th>Cliente</th><th>Data</th><th>Hora</th><th>Status</th></tr>
                    </thead>
                    <tbody>";
            while ($row = mysqli_fetch_assoc($res)) {
                $cliente = htmlspecialchars($row['nome_cliente'] ?? '—');
                $data = htmlspecialchars($row['data']);
                $hora = htmlspecialchars($row['hora']);
                $status = htmlspecialchars($row['status']);
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$cliente}</td>
                        <td>{$data}</td>
                        <td>{$hora}</td>
                        <td>{$status}</td>
                      </tr>";
            }
            echo "</tbody></table>";
        }
    }
    ?>
</div>
