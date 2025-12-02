<?php
require_once '../config.php';
verificar_login();

// Apenas admin
$usuario_id = $_SESSION['usuario_id'];
if ($usuario_id !== 1) {
    header("Location: ../index.php");
    exit;
}

// Buscar mensagens de ajuda
$filtro = $_GET['filtro'] ?? 'pendente';
$sql = "SELECT * FROM mensagens_ajuda WHERE status = ? ORDER BY data_criacao DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $filtro);
$stmt->execute();
$mensagens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens - Painel Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
        }

        .navbar-right {
            display: flex;
            gap: 20px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 60px;
            width: 250px;
            height: calc(100vh - 60px);
            background: white;
            border-right: 1px solid #e0e0e0;
            padding: 20px;
            overflow-y: auto;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
        }

        .sidebar-menu a {
            color: #666;
            text-decoration: none;
            padding: 12px 15px;
            display: block;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            color: #667eea;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .filtro-container {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        .filtro-btn {
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .filtro-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .filtro-btn:hover {
            border-color: #667eea;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .mensagem-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .mensagem-remetente {
            flex: 1;
        }

        .remetente-nome {
            font-weight: 700;
            color: #333;
            font-size: 16px;
        }

        .remetente-email {
            font-size: 12px;
            color: #999;
        }

        .mensagem-data {
            font-size: 12px;
            color: #999;
            text-align: right;
        }

        .mensagem-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }

        .status-pendente {
            background: #fff3cd;
            color: #cc3;
        }

        .status-respondido {
            background: #efe;
            color: #3c3;
        }

        .mensagem-corpo {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            line-height: 1.6;
        }

        .resposta-container {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }

        .resposta-label {
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            display: block;
        }

        .resposta-form {
            display: none;
        }

        .resposta-form.ativa {
            display: block;
        }

        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 10px;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-responder {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .btn-responder:hover {
            background: #764ba2;
        }

        .btn-cancelar {
            background: #f0f0f0;
            color: #333;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            margin-left: 5px;
        }

        .btn-cancelar:hover {
            background: #e0e0e0;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .page-title {
                font-size: 24px;
            }

            .mensagem-header {
                flex-direction: column;
                text-align: left;
            }

            .mensagem-data {
                text-align: left;
                margin-top: 10px;
            }

            .filtro-container {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">💬 Mensagens de Suporte</div>
        <div class="navbar-right">
            <a href="index.php" class="nav-link">← Dashboard</a>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="index.php">📊 Dashboard</a></li>
            <li><a href="listar-usuarios.php">👥 Usuários</a></li>
            <li><a href="criar-usuarios.php">➕ Novo Usuário</a></li>
            <li><a href="mensagens.php" class="active">💬 Mensagens</a></li>
            <li><a href="auditoria.php">📋 Auditoria</a></li>
        </ul>
    </div>

    <!-- Conteúdo Principal -->
    <div class="main-content">
        <h1 class="page-title">Mensagens de Suporte</h1>

        <div class="filtro-container">
            <button class="filtro-btn <?php echo $filtro === 'pendente' ? 'active' : ''; ?>" onclick="location.href='?filtro=pendente'">
                Pendentes
            </button>
            <button class="filtro-btn <?php echo $filtro === 'respondido' ? 'active' : ''; ?>" onclick="location.href='?filtro=respondido'">
                Respondidas
            </button>
            <button class="filtro-btn <?php echo $filtro === '' ? 'active' : ''; ?>" onclick="location.href='?filtro='">
                Todas
            </button>
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
                        <?php echo formatar_data($msg['data_criacao']); ?>
                        <br>
                        <?php echo formatar_hora($msg['data_criacao']); ?>
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
                    <div class="mensagem-corpo">
                        <?php echo nl2br(htmlspecialchars($msg['resposta'])); ?>
                    </div>
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
    </div>

    <script>
        function mostrarFormulario(id) {
            const form = document.getElementById(`form-${id}`);
            form.classList.add('ativa');
            document.getElementById(`resposta-${id}`).focus();
            event.target.style.display = 'none';
        }

        function cancelarResposta(id) {
            const form = document.getElementById(`form-${id}`);
            form.classList.remove('ativa');
            const btn = document.querySelector(`[onclick="mostrarFormulario(${id})"]`);
            if (btn) btn.style.display = 'block';
        }

        async function enviarResposta(id) {
            const resposta = document.getElementById(`resposta-${id}`).value;
            if (!resposta.trim()) {
                alert('Digite uma resposta');
                return;
            }

            try {
                const response = await fetch('api/responder-mensagem.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, resposta })
                });

                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                alert('Erro ao enviar resposta');
            }
        }
    </script>
</body>
</html>
