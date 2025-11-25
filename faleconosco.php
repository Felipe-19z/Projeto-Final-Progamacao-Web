<?php
@include_once __DIR__ . '/../topo.php';
?>
<body class="body-dark home-gradient">

<div class="container-custom">
    <div class="card">
        <h3>Fale Conosco</h3>
        <form action="admin/submit_contato.php" method="post">
            <div class="mb-3 mt-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" placeholder="Enter email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="nome" class="form-label">Nome:</label>
                <input type="text" class="form-control" id="nome" placeholder="Seu nome" name="nome" required>
            </div>
            <div class="mb-3">
                <label for="mensagem" class="form-label">Mensagem:</label>
                <textarea class="form-control" id="mensagem" placeholder="Mensagem" name="mensagem" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Enviar</button>
        </form>
    </div>
</div>
<?php
@include_once __DIR__ . '/../rodape.php';
?>
