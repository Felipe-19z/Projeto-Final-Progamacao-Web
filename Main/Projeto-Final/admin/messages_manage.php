<?php
require_once __DIR__ . '/auth.php';
// Fragmento para exibir Mensagens dentro de admin/index.php
// Requer que ../config.php já tenha sido incluído pelo chamador.

// filtro
$filtro = $_GET['filtro'] ?? 'pendente';
if ($filtro === 'todas' || $filtro === '') {
    // Todas as mensagens
    $query = "SELECT * FROM mensagens_ajuda ORDER BY data_criacao DESC";
    $result = $conn->query($query);
    $mensagens = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
} else {
    $stmt = $conn->prepare("SELECT * FROM mensagens_ajuda WHERE status = ? ORDER BY data_criacao DESC");
    $stmt->bind_param("s", $filtro);
    $stmt->execute();
    $mensagens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<style>
/* Styles copied from mensagens.php for the messages area */
.filtro-container { display: flex; gap: 10px; margin-bottom: 30px; }
.filtro-btn { padding: 8px 15px; border: 2px solid #e0e0e0; background: white; border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.3s ease; }
.filtro-btn.active { background: #667eea; color: white; border-color: #667eea; }
.card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
.mensagem-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; }
.remetente-nome { font-weight: 700; color: #333; font-size: 16px; }
.remetente-email { font-size: 12px; color: #999; }
.mensagem-data { font-size: 12px; color: #999; text-align: right; }
.mensagem-status { display: inline-block; padding: 5px 10px; border-radius: 5px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 10px; }
.status-pendente { background: #fff3cd; color: #cc3; }
.status-respondido { background: #efe; color: #3c3; }
.mensagem-corpo { margin: 15px 0; padding: 15px; background: #f9f9f9; border-radius: 8px; border-left: 4px solid #667eea; line-height: 1.6; }
.resposta-container { margin-top: 15px; padding-top: 15px; border-top: 2px solid #f0f0f0; }
.resposta-label { font-weight: 700; color: #333; margin-bottom: 10px; display: block; }
.resposta-form { display: none; }
.resposta-form.ativa { display: block; }
.form-textarea { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; font-family: inherit; resize: vertical; min-height: 100px; margin-bottom: 10px; }
.btn-responder { background: #667eea; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 12px; transition: all 0.3s ease; }
.btn-responder:hover { background: #764ba2; }
.btn-cancelar { background: #f0f0f0; color: #333; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 12px; margin-left: 5px; }
.empty-state { text-align: center; padding: 40px; color: #999; }
</style>

<div class="filtro-container">
    <button class="filtro-btn <?php echo $filtro === 'pendente' ? 'active' : ''; ?>" onclick="location.href='?section=mensagens&filtro=pendente'">Pendentes</button>
    <button class="filtro-btn <?php echo $filtro === 'respondido' ? 'active' : ''; ?>" onclick="location.href='?section=mensagens&filtro=respondido'">Respondidas</button>
    <button class="filtro-btn <?php echo $filtro === 'todas' ? 'active' : ''; ?>" onclick="location.href='?section=mensagens&filtro=todas'">Todas</button>
</div>

<?php if (empty($mensagens)): ?>
    <div class="card">
        <div class="empty-state">
            <p>Nenhuma mensagem nesta categoria</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($mensagens as $msg): ?>
        <div class="card">
            <div class="mensagem-header">
                <div class="mensagem-remetente">
                    <div class="remetente-nome"><?php echo htmlspecialchars($msg['nome']); ?></div>
                    <div class="remetente-email"><?php echo htmlspecialchars($msg['email']); ?></div>
                </div>
                <div class="mensagem-data">
                    <?php echo formatar_data($msg['data_criacao']); ?><br><?php echo formatar_hora($msg['data_criacao']); ?>
                </div>
            </div>

            <span class="mensagem-status <?php echo $msg['status'] === 'respondido' ? 'status-respondido' : 'status-pendente'; ?>">
                <?php echo ucfirst($msg['status']); ?>
            </span>

            <div class="mensagem-corpo">
                <?php echo nl2br(htmlspecialchars($msg['descricao'])); ?>
            </div>

            <div class="resposta-container">
                <?php if ($msg['status'] === 'respondido' && $msg['resposta']): ?>
                    <div class="resposta-label">📝 Resposta enviada:</div>
                    <div class="mensagem-corpo"><?php echo nl2br(htmlspecialchars($msg['resposta'])); ?></div>
                <?php else: ?>
                    <div class="resposta-label">Enviar Resposta</div>
                    <div class="resposta-form" id="form-<?php echo $msg['id']; ?>">
                        <textarea class="form-textarea" id="resposta-<?php echo $msg['id']; ?>" placeholder="Escreva sua resposta..."></textarea>
                        <button class="btn-responder" onclick="enviarResposta(<?php echo $msg['id']; ?>)">Enviar</button>
                        <button class="btn-cancelar" onclick="cancelarResposta(<?php echo $msg['id']; ?>)">Cancelar</button>
                    </div>
                    <button class="btn-responder" onclick="mostrarFormulario(<?php echo $msg['id']; ?>)">Responder</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
    const API_RESPONDER = '<?php echo rtrim(SITE_URL, "/"); ?>/api/responder-mensagem.php';

    function mostrarFormulario(id) {
        const form = document.getElementById(`form-${id}`);
        if (!form) return;
        form.classList.add('ativa');
        const textarea = document.getElementById(`resposta-${id}`);
        if (textarea) textarea.focus();
        const btn = document.querySelector(`[onclick="mostrarFormulario(${id})"]`);
        if (btn) btn.style.display = 'none';
    }

    function cancelarResposta(id) {
        const form = document.getElementById(`form-${id}`);
        if (!form) return;
        form.classList.remove('ativa');
        const btn = document.querySelector(`[onclick="mostrarFormulario(${id})"]`);
        if (btn) btn.style.display = 'block';
    }

    async function enviarResposta(id) {
        const resposta = document.getElementById(`resposta-${id}`).value;
        if (!resposta.trim()) { alert('Digite uma resposta'); return; }
        try {
            const response = await fetch(API_RESPONDER, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, resposta })
            });
            const data = await response.json();
            if (data.success) location.reload(); else alert(data.message || 'Erro');
        } catch (err) { alert('Erro ao enviar resposta'); }
    }
</script>
