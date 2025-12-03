<?php
require_once 'config.php';
verificar_login();
$usuario = obter_usuario($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Premium - Controle de Gastos</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;margin:0;padding:40px}
        .box{max-width:900px;margin:0 auto;background:#fff;padding:24px;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,0.08)}
        .plans{display:flex;gap:16px;flex-wrap:wrap}
        .plan{flex:1;min-width:200px;border:1px solid #eee;padding:18px;border-radius:8px;text-align:center}
        .plan h3{margin:0 0 8px}
        .plan .price{font-size:22px;font-weight:800;margin:12px 0}
        .plan button{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:10px 14px;border:none;border-radius:8px;cursor:pointer}
    </style>
</head>
<body>
    <div class="box">
        <h1>⭐ Premium</h1>
        <p>Escolha um plano para desbloquear funcionalidades avançadas.</p>
        <div class="plans">
            <div class="plan">
                <h3>Mensal</h3>
                <div class="price">R$ 10</div>
                <div>Assinatura renovável mensalmente</div>
                <p><button onclick="location.href='premium_loading.php?plan=mensal'">Assinar Mensal</button></p>
            </div>
            <div class="plan">
                <h3>Anual</h3>
                <div class="price">R$ 80</div>
                <div>Economize em comparação ao plano mensal</div>
                <p><button onclick="location.href='premium_loading.php?plan=anual'">Assinar Anual</button></p>
            </div>
            <div class="plan">
                <h3>Vitalício</h3>
                <div class="price">R$ 150</div>
                <div>Pagamento único. Acesso permanente</div>
                <p><button onclick="location.href='premium_loading.php?plan=vitalicio'">Pagar Vitalício</button></p>
            </div>
        </div>
        <p style="margin-top:18px;color:#666;font-size:13px">Observação: Este é um projeto demonstrativo — não há integração com pagamentos reais.</p>
        <p><a href="index.php">Voltar ao menu</a></p>
    </div>
</body>
</html>