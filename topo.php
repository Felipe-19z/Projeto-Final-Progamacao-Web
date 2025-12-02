<?php $nome = "READVINA"; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>READVINA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Estilos customizados do projeto -->
    <link rel="stylesheet" href="/projeto1/css/style.css">
</head>
<?php $bodyClass = (empty($_GET['pg']) || (isset($_GET['pg']) && $_GET['pg'] === 'conteudo')) ? 'home-gradient' : ''; ?>
<body class="<?= $bodyClass ?>">
<header class="navbar navbar-expand-sm">
    <div class="container-fluid">
       <h2><?=$nome;?></h2>
    </div>
</header>

