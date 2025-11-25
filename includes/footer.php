<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$year = date('Y');
$appName = 'Controle de Gastos';
$userName = isset($_SESSION['usuario_nome']) ? htmlspecialchars($_SESSION['usuario_nome']) : '';
$isAdmin = !empty($_SESSION['is_admin']);
?>
<footer style="background: linear-gradient(90deg, #f7f7f8, #ffffff); border-top:1px solid #e6e6e6; padding:18px 20px; margin-top:40px; font-family:inherit;">
    <div style="max-width:1200px; margin:0 auto; display:flex; gap:20px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
        <div style="display:flex; gap:12px; align-items:center;">
            <div style="font-weight:700; color:#333;">&copy; <?php echo $year; ?> <?php echo $appName; ?></div>
            <div style="color:#666; font-size:13px;">• Versão 1.0</div>
        </div>

        <div style="display:flex; gap:12px; align-items:center; color:#666; font-size:13px;">
            <?php if ($userName): ?>
                <div style="display:flex; gap:8px; align-items:center;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="#999" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 21C20 16.5817 16.4183 13 12 13C7.58172 13 4 16.5817 4 21" stroke="#999" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span title="Usuário logado">Olá, <?php echo $userName; ?></span>
                </div>
            <?php endif; ?>

            <a href="ajuda.php" style="color:#667eea; text-decoration:none; font-weight:600;">Ajuda</a>
            <a href="configuracoes.php" style="color:#667eea; text-decoration:none; font-weight:600;">Configurações</a>
            <?php if ($isAdmin): ?>
                <a href="admin/index.php" style="color:#667eea; text-decoration:none; font-weight:600;">Admin</a>
            <?php endif; ?>
        </div>
    </div>

    <div style="max-width:1200px; margin:8px auto 0; color:#999; font-size:12px; text-align:center;">
        <small>Feito com ❤️ — Proteja suas credenciais e mantenha backups regulares.</small>
    </div>
</footer>

<!-- Small accessibility helper: ensure footer is focusable for keyboard users -->
<a href="#top" style="position:absolute; left:-9999px; top:auto; width:1px; height:1px; overflow:hidden;">Ir para o topo</a>
