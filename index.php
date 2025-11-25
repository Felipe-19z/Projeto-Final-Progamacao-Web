<?php

    include_once "topo.php";
    include_once "menu.php";

    // Conteúdo (envuelto em main.site-content para empurrar o footer para baixo)
    echo '<main class="site-content container-custom">';
    if(empty($_SERVER["QUERY_STRING"])){
        $var = "conteudo";
        include_once "$var.php";
    }elseif(isset($_GET['pg']) && $_GET['pg']){
        $pg = $_GET['pg'];
        include_once "$pg.php";
    }else{
        echo "Página não encontrada";
    }
    echo '</main>';

    include_once "rodape.php";