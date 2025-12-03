<?php
require_once 'config.php';
verificar_login();
$plan = isset($_GET['plan']) ? $_GET['plan'] : 'mensal';
$valid = ['mensal','anual','vitalicio'];
if (!in_array($plan, $valid)) $plan = 'mensal';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Processando Pagamento</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;margin:0;padding:40px}
.center{max-width:700px;margin:0 auto;text-align:center}
.loader{width:80px;height:80px;border-radius:50%;border:8px solid #eee;border-top-color:#667eea;margin:40px auto;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.success{display:none}
.confetti{position:fixed;left:0;top:0;right:0;bottom:0;pointer-events:none}
.btn{display:inline-block;margin-top:16px;padding:10px 14px;background:#667eea;color:#fff;border-radius:8px;text-decoration:none}
</style>
</head>
<body>
    <div class="center">
        <h2>Processando pagamento - plano <?php echo htmlspecialchars($plan); ?>...</h2>
        <div class="loader" id="loader"></div>
        <div id="status">Aguarde. Redirecionando em alguns segundos...</div>
        <div class="success" id="successBox">
            <h2>✔ Pagamento Efetuado! Muito obrigado pela sua escolha 😎</h2>
            <p id="confettiText"> </p>
            <a class="btn" href="index.php">Voltar ao menu ↩</a>
        </div>
        <div id="confettiCanvas" class="confetti"></div>
    </div>

<script>
// Após 5s, chamar API para marcar usuário premium e mostrar sucesso com confetes
setTimeout(async function(){
    try{
        const resp = await fetch('api/premium.php', {
            method:'POST',
            credentials:'same-origin',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ plan: '<?php echo $plan; ?>' })
        });
        const data = await resp.json();
        console.log('premium api', data);
    } catch(e){ console.error('Erro ao marcar premium', e); }

    // mostrar sucesso
    document.getElementById('loader').style.display = 'none';
    document.getElementById('status').style.display = 'none';
    document.getElementById('successBox').style.display = 'block';

    // confetti simples
    (function(){
        const colors = ['#f94144','#f3722c','#f9c74f','#90be6d','#577590'];
        const area = document.getElementById('confettiCanvas');
        for(let i=0;i<50;i++){
            const d = document.createElement('div');
            d.style.position='absolute';
            d.style.left = Math.random()*100 + '%';
            d.style.top = Math.random()*80 + '%';
            d.style.width='10px'; d.style.height='14px';
            d.style.background = colors[Math.floor(Math.random()*colors.length)];
            d.style.opacity = '0.95';
            d.style.borderRadius='2px';
            d.style.transform = 'translateY(0px)';
            d.style.animation = 'confettiUpDown ' + (2+Math.random()*2) + 's ease-in-out infinite';
            area.appendChild(d);
        }
        const style = document.createElement('style');
        style.innerHTML = '@keyframes confettiUpDown { 0% { transform: translateY(-40vh) rotate(0deg);} 50% { transform: translateY(-60vh) rotate(180deg);} 100% { transform: translateY(-40vh) rotate(360deg);} }';
        document.head.appendChild(style);
    })();

},5000);
</script>
</body>
</html>