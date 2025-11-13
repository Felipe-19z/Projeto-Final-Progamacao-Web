<?php
require_once 'config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];
$usuario = obter_usuario($usuario_id);
$configuracoes = obter_configuracoes($usuario_id);

// Garantir que a tabela de histórico exista (criação segura no carregamento)
$createHistorySql = "CREATE TABLE IF NOT EXISTS configuracoes_usuario_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    cor_fundo VARCHAR(20),
    cor_gastos VARCHAR(20),
    cor_grafico_1 VARCHAR(20),
    cor_grafico_2 VARCHAR(20),
    cor_grafico_3 VARCHAR(20),
    renda_mensal DECIMAL(10,2) DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
// Executa a criação sem interromper em caso de erro (por exemplo, permissões)
if (!$conn->query($createHistorySql)) {
    // registrar erro no log (não exibe para o usuário)
    error_log('Não foi possível criar tabela configuracoes_usuario_historico: ' . $conn->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Inicializar variáveis de cor como null e só sanitizar se vierem no POST
    $cor_fundo = null;
    $cor_gastos = null;
    $cor_grafico_1 = null;
    $cor_grafico_2 = null;
    $cor_grafico_3 = null;

    if (isset($_POST['cor_fundo'])) {
        $cor_fundo = sanitizar($_POST['cor_fundo']);
    }
    if (isset($_POST['cor_gastos'])) {
        $cor_gastos = sanitizar($_POST['cor_gastos']);
    }
    if (isset($_POST['cor_grafico_1'])) {
        $cor_grafico_1 = sanitizar($_POST['cor_grafico_1']);
    }
    if (isset($_POST['cor_grafico_2'])) {
        $cor_grafico_2 = sanitizar($_POST['cor_grafico_2']);
    }
    if (isset($_POST['cor_grafico_3'])) {
        $cor_grafico_3 = sanitizar($_POST['cor_grafico_3']);
    }

    // Só ler renda_mensal se o campo foi enviado no form (evita sobrescrever com 0)
    $renda_mensal = null;
    if (isset($_POST['renda_mensal'])) {
        $renda_mensal = floatval($_POST['renda_mensal']);
    }

    // --- Criar tabela de histórico se não existir ---
    $createHistorySql = "CREATE TABLE IF NOT EXISTS configuracoes_usuario_historico (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        cor_fundo VARCHAR(20),
        cor_gastos VARCHAR(20),
        cor_grafico_1 VARCHAR(20),
        cor_grafico_2 VARCHAR(20),
        cor_grafico_3 VARCHAR(20),
        renda_mensal DECIMAL(10,2) DEFAULT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->query($createHistorySql);

    // Antes de alterar, salvar snapshot atual nas configuracoes_usuario_historico
    $sqlSnapshot = "SELECT c.cor_fundo, c.cor_gastos, c.cor_grafico_1, c.cor_grafico_2, c.cor_grafico_3, u.renda_mensal
                    FROM configuracoes_usuario c
                    LEFT JOIN usuarios u ON u.id = ?
                    WHERE c.usuario_id = ?";
    $stmtSnap = $conn->prepare($sqlSnapshot);
    $stmtSnap->bind_param("ii", $usuario_id, $usuario_id);
    $stmtSnap->execute();
    $resSnap = $stmtSnap->get_result();
    $current = $resSnap->fetch_assoc();
    $stmtSnap->close();

    $insertHist = $conn->prepare("INSERT INTO configuracoes_usuario_historico (usuario_id, cor_fundo, cor_gastos, cor_grafico_1, cor_grafico_2, cor_grafico_3, renda_mensal) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $renda_snap = isset($current['renda_mensal']) ? $current['renda_mensal'] : null;
    $insertHist->bind_param("isssssd", $usuario_id, $current['cor_fundo'], $current['cor_gastos'], $current['cor_grafico_1'], $current['cor_grafico_2'], $current['cor_grafico_3'], $renda_snap);
    $insertHist->execute();
    $insertHist->close();

    // Atualizar configurações (apenas os campos enviados no POST)
    if ($cor_fundo !== null) {
        $sql = "UPDATE configuracoes_usuario SET cor_fundo = ? WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $cor_fundo, $usuario_id);
        $stmt->execute();
        $stmt->close();
    }
    if ($cor_gastos !== null) {
        $sql = "UPDATE configuracoes_usuario SET cor_gastos = ? WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $cor_gastos, $usuario_id);
        $stmt->execute();
        $stmt->close();
    }
    if ($cor_grafico_1 !== null) {
        $sql = "UPDATE configuracoes_usuario SET cor_grafico_1 = ? WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $cor_grafico_1, $usuario_id);
        $stmt->execute();
        $stmt->close();
    }
    if ($cor_grafico_2 !== null) {
        $sql = "UPDATE configuracoes_usuario SET cor_grafico_2 = ? WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $cor_grafico_2, $usuario_id);
        $stmt->execute();
        $stmt->close();
    }
    if ($cor_grafico_3 !== null) {
        $sql = "UPDATE configuracoes_usuario SET cor_grafico_3 = ? WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $cor_grafico_3, $usuario_id);
        $stmt->execute();
        $stmt->close();
    }

    // Atualizar renda (apenas se foi enviada no POST)
    if ($renda_mensal !== null) {
        $sql = "UPDATE usuarios SET renda_mensal = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $renda_mensal, $usuario_id);
        $stmt->execute();
        $stmt->close();
    }

    // Recarregar
    $usuario = obter_usuario($usuario_id);
    $configuracoes = obter_configuracoes($usuario_id);
    $sucesso = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Controle de Gastos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: <?php echo $configuracoes['cor_fundo'] ?? '#f5f5f5'; ?>;
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

        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 30px;
            color: #333;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input, .color-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .color-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .color-input-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .color-input {
            width: 60px;
            height: 50px;
            padding: 2px;
            cursor: pointer;
        }

        .color-preview {
            flex: 1;
            padding: 10px 15px;
            border-radius: 8px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            font-size: 12px;
        }

        .form-button {
            display: inline-block;
            padding: 12px 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.12s ease, box-shadow 0.12s ease, opacity 0.12s ease;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            box-shadow: 0 6px 18px rgba(118, 75, 162, 0.08);
        }

        .form-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(118, 75, 162, 0.12);
            opacity: 0.98;
        }

        /* Small secondary button used for history and small actions */
        .btn-small {
            padding: 8px 12px;
            font-size: 13px;
            border-radius: 8px;
            text-transform: none;
            letter-spacing: normal;
            background: #f0f0f0;
            color: #333;
            box-shadow: none;
            border: 1px solid #e0e0e0;
        }

        .btn-small:hover {
            background: #eef2ff;
            border-color: #d6d6f0;
            transform: translateY(-1px);
        }

        .revert-btn {
            background: linear-gradient(90deg, #ff6b6b, #ff4d4d);
            color: white;
            border: none;
            box-shadow: 0 6px 16px rgba(255, 75, 75, 0.12);
        }

        .revert-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(255, 75, 75, 0.16);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInDown 0.3s ease-out;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
        }

        .back-link:hover {
            color: #764ba2;
        }

        .preview-section {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #667eea;
        }

        .preview-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .preview-box {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 24px;
        }

        @media (max-width: 600px) {
            .page-title {
                font-size: 24px;
            }

            .card {
                padding: 20px;
            }

            .color-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">⚙️ Configurações</div>
        <div>
            <a href="index.php" class="nav-link">← Voltar</a>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="container">
        <?php if (isset($sucesso)): ?>
        <div class="alert alert-success">
            ✓ Configurações salvas com sucesso!
        </div>
        <?php endif; ?>

        <!-- Configurações de Perfil -->
        <div class="card">
            <div class="card-title">👤 Informações de Perfil</div>

            <form method="POST" id="perfilForm">
                <div class="form-group">
                    <label class="form-label">Nome</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($usuario['nome']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label class="form-label">Renda Mensal (R$)</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="number" name="renda_mensal" id="rendaMensalInput" class="form-input" value="<?php echo $usuario['renda_mensal']; ?>" step="0.01" disabled>
                        <label style="font-size:13px; display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" id="toggleRenda"> <span>Atualizar Renda Mensal</span>
                        </label>
                        <button type="submit" class="btn-small form-button" id="salvarRendaBtn" style="display:none;">Salvar Renda</button>
                    </div>
                </div>
            </form>
        </div>

                <!-- Histórico de Configurações -->
                <div class="card">
                    <div class="card-title">📜 Histórico de Configurações</div>
                    <div class="form-group">
                        <?php
                        // Buscar últimas 5 entradas do histórico
                        $histSql = "SELECT id, cor_fundo, cor_gastos, cor_grafico_1, cor_grafico_2, cor_grafico_3, renda_mensal, criado_em FROM configuracoes_usuario_historico WHERE usuario_id = ? ORDER BY criado_em DESC LIMIT 5";
                        $stmtH = $conn->prepare($histSql);
                        $stmtH->bind_param("i", $usuario_id);
                        $stmtH->execute();
                        $resH = $stmtH->get_result();
                        if ($resH->num_rows === 0) {
                            echo '<div>Nenhum histórico disponível.</div>';
                        } else {
                            echo '<ul style="list-style:none; padding:0;">';
                            while ($rowH = $resH->fetch_assoc()) {
                                echo '<li style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">';
                                echo '<div><strong>' . htmlspecialchars($rowH['criado_em']) . '</strong><br>';
                                echo 'Renda: ' . ($rowH['renda_mensal'] !== null ? formatar_moeda($rowH['renda_mensal']) : '—') . '</div>';
                                echo '<div><button class="btn-small revert-btn" onclick="reverterConfig(' . $rowH['id'] . ')">Reverter</button></div>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                        $stmtH->close();
                        ?>
                    </div>
                </div>

        <!-- Configurações de Tema -->
        <div class="card">
            <div class="card-title">🎨 Personalização de Cores</div>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Cor de Fundo</label>
                </div>

                <div class="color-group">
                    <div class="form-group">
                        <label class="form-label">Fundo Principal</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="cor_fundo" class="color-input" value="<?php echo $configuracoes['cor_fundo'] ?? '#FFFFFF'; ?>" onchange="atualizarPreview()">
                            <div class="color-preview" id="preview_fundo" style="background-color: <?php echo $configuracoes['cor_fundo'] ?? '#FFFFFF'; ?>">
                                <?php echo $configuracoes['cor_fundo'] ?? '#FFFFFF'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cor dos Gastos</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="cor_gastos" class="color-input" value="<?php echo $configuracoes['cor_gastos'] ?? '#FF6B6B'; ?>" onchange="atualizarPreview()">
                            <div class="color-preview" id="preview_gastos" style="background-color: <?php echo $configuracoes['cor_gastos'] ?? '#FF6B6B'; ?>">
                                <?php echo $configuracoes['cor_gastos'] ?? '#FF6B6B'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cor Gráfico 1</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="cor_grafico_1" class="color-input" value="<?php echo $configuracoes['cor_grafico_1'] ?? '#4ECDC4'; ?>" onchange="atualizarPreview()">
                            <div class="color-preview" id="preview_grafico1" style="background-color: <?php echo $configuracoes['cor_grafico_1'] ?? '#4ECDC4'; ?>">
                                <?php echo $configuracoes['cor_grafico_1'] ?? '#4ECDC4'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cor Gráfico 2</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="cor_grafico_2" class="color-input" value="<?php echo $configuracoes['cor_grafico_2'] ?? '#45B7D1'; ?>" onchange="atualizarPreview()">
                            <div class="color-preview" id="preview_grafico2" style="background-color: <?php echo $configuracoes['cor_grafico_2'] ?? '#45B7D1'; ?>">
                                <?php echo $configuracoes['cor_grafico_2'] ?? '#45B7D1'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cor Gráfico 3</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="cor_grafico_3" class="color-input" value="<?php echo $configuracoes['cor_grafico_3'] ?? '#FFA07A'; ?>" onchange="atualizarPreview()">
                            <div class="color-preview" id="preview_grafico3" style="background-color: <?php echo $configuracoes['cor_grafico_3'] ?? '#FFA07A'; ?>">
                                <?php echo $configuracoes['cor_grafico_3'] ?? '#FFA07A'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="preview-section">
                    <div class="preview-text">Pré-visualização:</div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                        <div class="preview-box" id="preview" style="background-color: <?php echo $configuracoes['cor_gastos'] ?? '#FF6B6B'; ?>">Gastos</div>
                        <div class="preview-box" style="background-color: <?php echo $configuracoes['cor_grafico_1'] ?? '#4ECDC4'; ?>">Saldo</div>
                        <div class="preview-box" style="background-color: <?php echo $configuracoes['cor_grafico_2'] ?? '#45B7D1'; ?>">Extra</div>
                    </div>
                </div>

                <button type="submit" class="form-button" style="margin-top: 20px;">Salvar Configurações</button>
            </form>
        </div>
    </div>

    <script>
        function atualizarPreview() {
            const inputs = document.querySelectorAll('input[type="color"]');
            inputs.forEach(input => {
                const name = input.name;
                const value = input.value;
                const preview = document.getElementById(`preview_${name.replace('cor_', '')}`);
                if (preview) {
                    preview.style.backgroundColor = value;
                    preview.textContent = value.toUpperCase();
                }
            });
        }
        // Toggle para atualizar renda mensal: habilita o input e mostra botão salvar
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('toggleRenda');
            const rendaInput = document.getElementById('rendaMensalInput');
            const salvarBtn = document.getElementById('salvarRendaBtn');

            if (toggle && rendaInput && salvarBtn) {
                toggle.addEventListener('change', function() {
                    if (this.checked) {
                        rendaInput.disabled = false;
                        salvarBtn.style.display = 'inline-block';
                        rendaInput.focus();
                    } else {
                        rendaInput.disabled = true;
                        salvarBtn.style.display = 'none';
                    }
                });
            }
            // Função para chamar API e reverter configuração
            window.reverterConfig = function(histId) {
                if (!confirm('Reverter para essa versão?')) return;
                fetch('api/configuracoes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'reverter', id: histId })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        alert('Configurações revertidas. Recarregando...');
                        window.location.reload();
                    } else {
                        alert('Erro: ' + (data.message || 'Não foi possível reverter'));
                    }
                }).catch(e => {
                    console.error(e);
                    alert('Erro ao conectar com o servidor');
                });
            }
        });
    </script>
</body>
</html>
