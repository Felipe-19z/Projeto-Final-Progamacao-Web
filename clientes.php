<?php

    require_once "admin/config.inc.php";
    $sql = "SELECT * FROM clientes";
    $resultado = mysqli_query($conexao, $sql);

?>
<div class="container mt-3">
    <h2>Clientes atendidos pela Empresa</h2>
    <p>Detalhes dos nossos clientes por Cidade e Estado</p>
    <table class="table table-dark table-hover">
        <thead>
        <tr>
            <th>Cliente</th>
            <th>Cidade</th>
            <th>Estado</th>
        </tr>
        </thead>
        <tbody>
        <?php
            while($cliente = mysqli_fetch_array($resultado)){
        ?>
        <tr>
            <td><?=$cliente['cliente']?></td>
            <td><?=$cliente['cidade']?></td>
            <td><?=$cliente['estado']?></td>
        </tr>
        <?php
            }
        ?>
        </tbody>
    </table>
</div>
