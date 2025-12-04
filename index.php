<?php
require_once 'config.php';

// Se o visitante não estiver logado, primeiro mostrar a página introdutória (main_menu)
// main_menu.php por sua vez redireciona usuários logados diretamente para o dashboard
// e mostra a opção de login para não-autenticados.
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . SITE_URL . 'main_menu.php');
    exit();
}

// Usuário autenticado: validar sessão/permitir acesso ao dashboard
verificar_login();

$usuario_id = $_SESSION['usuario_id'];
$usuario = obter_usuario($usuario_id);

// Se o usuário não existir (conta removida ou inativa), encerrar sessão e redirecionar
if (!$usuario) {
    session_unset();
    session_destroy();
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$configuracoes = obter_configuracoes($usuario_id);

// Se não existem configurações, criar padrões com prepared statement
if (!$configuracoes) {
    $sqlIns = "INSERT INTO configuracoes_usuario (usuario_id) VALUES (?)";
    $stmtIns = $conn->prepare($sqlIns);
    if ($stmtIns) {
        $stmtIns->bind_param('i', $usuario_id);
        $stmtIns->execute();
        $stmtIns->close();
    }
    $configuracoes = obter_configuracoes($usuario_id);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Controle de Gastos</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            transition: background-color 0.3s ease;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            animation: slideInDown 0.5s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .welcome-title {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .welcome-text {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }

        .tutorial-prompt {
            display: flex;
            align-items: center;
            gap: 15px;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 15px 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .tutorial-prompt.hidden {
            display: none;
        }

        .tutorial-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .tutorial-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        .close-tutorial {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #667eea;
            margin-left: auto;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 1200px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
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
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-box {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #667eea;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        .gastos-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .gasto-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            animation: slideInLeft 0.3s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .gasto-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .gasto-categoria {
            font-weight: 700;
            font-size: 13px;
        }

        .gasto-descricao {
            font-size: 14px;
            color: #666;
        }

        .gasto-valor {
            font-weight: 700;
            color: #333;
            margin-right: 10px;
        }

        .filtro-btn {
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filtro-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .filtro-btn:hover {
            border-color: #667eea;
        }

        .tutorial-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            display: none;
            pointer-events: none;
        }

        .tutorial-overlay.active {
            display: block;
            pointer-events: auto;
        }

        .tutorial-box {
            position: fixed;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 1002;
            max-width: 300px;
            animation: slideInUp 0.3s ease-out;
            pointer-events: auto;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .tutorial-title {
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .tutorial-text {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .tutorial-nav {
            display: flex;
            gap: 10px;
        }

        .tutorial-nav button {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }

        .tutorial-prev {
            background: #f0f0f0;
            color: #333;
        }

        .tutorial-next {
            background: #667eea;
            color: white;
        }

        .logout-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #ff5252;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-left">
            <div class="navbar-brand">
                💰 Controle de Gastos
            </div>
        </div>
        <div class="navbar-right">
            <a href="<?php echo SITE_URL; ?>configuracoes.php" class="nav-link" id="navConfiguracoes">⚙️ Configurações</a>
            <a href="<?php echo SITE_URL; ?>premium.php" class="nav-link" id="navPremium">⭐ Premium</a>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <a href="<?php echo SITE_URL; ?>admin/" class="nav-link" id="navAdmin">🛠️ Admin</a>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>ajuda.php" class="nav-link" id="navAjuda">❓ Ajuda</a>
            <div class="nav-user">
                <div class="user-avatar"><?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($usuario['nome']); ?></span>
                <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium']): ?>
                    <a href="<?php echo SITE_URL; ?>stats_loader.php" class="nav-link" style="margin-left:8px;">📊Estatística</a>
                <?php endif; ?>
                <button class="logout-btn" onclick="logout()">Sair</button>
            </div>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="container">
        <!-- Seção de Bem-vindo -->
        <div class="welcome-section">
            <h1 class="welcome-title">Bem-vindo, <?php echo htmlspecialchars(explode(' ', $usuario['nome'])[0]); ?>! 👋</h1>
            <p class="welcome-text">Você tem uma renda mensal de <strong><?php echo formatar_moeda($usuario['renda_mensal']); ?></strong></p>

            <!-- Tutorial Prompt -->
            <div class="tutorial-prompt" id="tutorialPrompt">
                <span>Gostaria de ver um tutorial sobre como usar o sistema?</span>
                <button class="tutorial-btn" onclick="iniciarTutorial()">Ver Tutorial</button>
                <button class="close-tutorial" onclick="fecharTutorialPrompt()">✕</button>
            </div>
            <div id="debugStatus" style="margin-top:10px; color:#c0392b; font-weight:600;">JS: aguardando inicialização...</div>
            <script>
                // Teste rápido para verificar se scripts inline são executados antes de erros maiores
                (function(){
                    try {
                        var dbg = document.getElementById('debugStatus');
                        if (dbg) dbg.textContent = 'JS: script inline executado';
                        console.log('debugStatus inline script executed');
                    } catch (e) {
                        console.error('Inline debug script failed', e);
                    }
                })();
            </script>
        </div>

        <!-- Grid Principal -->
        <div class="main-grid">
            <!-- Card de Adicionar Gasto -->
            <div id="addGastoCard" class="card">
                <div class="card-title">➕ Adicionar Gasto</div>
                <div id="normalAddBody">
                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select id="categoriaSelect" class="form-input" style="margin-bottom:8px;">
                        <option value="">-- selecione --</option>
                    </select>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="novaCategoria" class="form-input" placeholder="Ex: Alimentação" style="flex: 1;">
                        <button id="btnAddCategoria" onclick="adicionarCategoria()" style="padding: 10px 15px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">+</button>
                        <button id="btnDeleteCategoria" onclick="excluirCategoriaSelecionada()" title="Excluir categoria selecionada" style="padding: 10px 12px; background: #e74c3c; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display:inline-block;">🗑</button>
                    </div>
                    <div id="categoriasList" style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;"></div>
                </div>

                

                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <input type="text" id="descricaoGasto" class="form-input" placeholder="Ex: Compra mercado">
                </div>

                <div class="form-group">
                    <label class="form-label">Tipo de Gasto</label>
                    <select id="tipoGastoSelect" class="form-input">
                        <option value="opcional">Opcional</option>
                        <option value="obrigatorio">Obrigatório</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Valor (R$)</label>
                    <input type="number" id="valorGasto" class="form-input" placeholder="0.00" step="0.01">
                </div>

                <div class="form-group">
                    <label class="form-label">Data</label>
                    <input type="date" id="dataGasto" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Hora</label>
                    <input type="time" id="horaGasto" class="form-input">
                </div>

                <button class="btn-primary" onclick="adicionarGasto()">Registrar Gasto</button>
                </div> <!-- end normalAddBody -->
                
                <!-- Formulário inline de gasto fixo (oculto por padrão) posicionado fora do normalAddBody para o toggle funcionar corretamente -->
                <div id="inlineFixoBlock" style="display:none; margin-top:10px;">
                    <div class="form-group">
                        <label class="form-label">Categoria</label>
                        <select id="categoriaInlineSelect" class="form-input">
                            <option value="">-- selecione --</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 10px; margin-bottom:8px;">
                    <input type="text" id="novaCategoriaFixo" class="form-input" placeholder="Ex: Alimentação" style="flex: 1;">
                    <button id="btnAddCategoriaFixo" onclick="adicionarCategoriaFixo()" style="padding: 10px 15px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">+</button>
                    <button id="btnDeleteCategoriaFixo" onclick="excluirCategoriaSelecionada()" title="Excluir categoria selecionada" style="padding: 10px 12px; background: #e74c3c; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">🗑</button>
                </div>
                    <div class="form-group">
                        <label class="form-label">Periodicidade</label>
                        <select id="periodicidadeInline" class="form-input">
                            <option value="mes">Mensal</option>
                            <option value="semana">Semanal</option>
                            <option value="ano">Anual</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tempo </label>
                        <input type="number" id="periodosInline" class="form-input" placeholder="Ex: 6" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data de início (opcional)</label>
                        <input type="date" id="startDateInline" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <input type="text" id="descricaoInline" class="form-input" placeholder="Ex: Aluguel">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de Gasto</label>
                        <select id="tipoGastoInlineSelect" class="form-input">
                            <option value="opcional">Opcional</option>
                            <option value="obrigatorio">Obrigatório</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor (R$)</label>
                        <input type="number" id="valorInline" class="form-input" placeholder="0.00" step="0.01">
                    </div>
                    <button class="btn-primary" onclick="adicionarGastoFixoInline()">Registrar Gasto Fixo</button>
                </div>

                <div style="margin-top:10px; text-align:center;">
                    <button id="toggleFixoBtn" class="filtro-btn" style="padding:8px 12px; font-size:13px;" onclick="toggleGastoFixo()">Mudar para Gasto Fixo</button>
                </div>
            </div>

           
            <!-- Card de Gráfico -->
            <div class="card">
                <div class="card-title">📊 Resumo Financeiro</div>

                <div class="filtro-container">
                    <button class="filtro-btn active" onclick="alterarFiltro('dia')">Dia</button>
                    <button class="filtro-btn" onclick="alterarFiltro('semana')">Semana</button>
                    <button class="filtro-btn" onclick="alterarFiltro('mes')">Mês</button>
                    <button class="filtro-btn" onclick="alterarFiltro('ano')">Ano</button>
                    <select id="yearSelector" style="margin-left:12px; display:none; padding:6px 8px; font-size:13px; border-radius:6px; border:1px solid #ddd;"></select>
                </div>

                <div class="chart-container">
                    <canvas id="graficoGastos"></canvas>
                </div>

                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-label">Renda</div>
                        <div class="stat-value" id="statRenda">R$ 0,00</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Gastos</div>
                        <div class="stat-value" id="statGastos">R$ 0,00</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Saldo</div>
                        <div class="stat-value" id="statSaldo">R$ 0,00</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de Últimos Gastos -->
        <!-- Card de Gastos Totais (agrupados por categoria) -->
        <div class="card">
            <div class="card-title">📊 Gastos Totais</div>
            <div id="gastosTotais" style="margin-bottom:10px; display:flex; flex-direction:column; gap:8px;">
                <!-- preenchido por carregarCategorias() -->
            </div>
            <div style="font-size:13px; color:#555; margin-bottom:8px;" id="projecaoAnual"> </div>
            <div style="text-align:right; margin-top:6px;">
                <button class="filtro-btn" style="padding:6px 10px; font-size:12px;" onclick="carregarGastos(null)">Limpar seleção</button>
            </div>
        </div>

        <div class="card">
            <div class="card-title">📋 Últimos Gastos</div>
            <div class="gastos-list" id="gastosList">
                <div class="empty-state">
                    <p>Nenhum gasto registrado ainda</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tutorial Overlay -->
    <div class="tutorial-overlay" id="tutorialOverlay"></div>
    <div class="tutorial-box" id="tutorialBox" style="display: none;">
        <div class="tutorial-title" id="tutorialTitle">Tutorial</div>
        <div class="tutorial-text" id="tutorialText">Conteúdo</div>
        <div class="tutorial-nav">
            <button class="tutorial-prev" onclick="tutorialAnterior(); return false;">← Anterior</button>
            <button class="tutorial-next" onclick="tutorialProximo(); return false;">Próximo →</button>
        </div>
    </div>

    <script>
        // Variáveis globais
        let filtroAtual = 'mes';
        let grafico = null;
        let selectedYear = null;
        let tutorialAtivo = false;
        let etapaTutorial = 0;
        let categoriaIdSelecionada = null;
        let categoriaFilter = null; // filtra 'Últimos Gastos' por categoria quando definido
        let overlayDesativadoTemporariamente = false;

        // Captura global de erros para ajudar depuração em produção local
        window.addEventListener('error', function (event) {
            console.error('Erro global capturado:', event.error || event.message, event);
            try { showToast('Erro JavaScript: ' + (event.message || event.error?.message || 'ver console'), 'error', 6000); } catch(e) { console.log('Toast falhou ao exibir erro global'); }
        });
        window.addEventListener('unhandledrejection', function (event) {
            console.error('Unhandled rejection:', event.reason);
            try { showToast('Erro não tratado: ver console', 'error', 6000); } catch(e) { console.log('Toast falhou ao exibir rejection'); }
        });

        const tutoriais = [
            {
                title: "📝 Adicionar Gastos",
                text: "Clique aqui para adicionar uma nova categoria de gasto. Você pode criar categorias personalizadas como 'Alimentação', 'Energia', 'Internet', etc.",
                elemento: "novaCategoria"
            },
            {
                title: "📊 Resumo Financeiro",
                text: "Este gráfico mostra sua situação financeira. Você pode filtrar por Dia, Semana, Mês ou Ano para ver como você está gastando.",
                elemento: "graficoGastos"
            },
            {
                title: "⚙️ Configurações",
                text: "Aqui você pode personalizar seu perfil, como renda mensal, cores do tema e outras preferências do sistema.",
                elemento: "navConfiguracoes"
            },
            {
                title: "❓ Ajuda",
                text: "Clique aqui para acessar o guia de uso completo do sistema e resolver suas dúvidas.",
                elemento: "navAjuda"
            }
        ];

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            // mark debug status element immediately so user can see if JS runs
            try {
                const dbg = document.getElementById('debugStatus');
                if (dbg) dbg.textContent = 'JS: carregado, inicializando...';
                console.log('index.php JS: DOMContentLoaded');
            } catch(e) {}
            // Definir data e hora atuais
            document.getElementById('dataGasto').valueAsDate = new Date();
            document.getElementById('horaGasto').value = new Date().toTimeString().slice(0, 5);

            // Carregar dados
            carregarCategorias();
            // Preparar seletor de anos (para filtro 'ano')
            populateYearSelector();
            // Ajustar botão de filtro ativo de acordo com o filtro padrão
            try {
                document.querySelectorAll('.filtro-btn').forEach(btn => btn.classList.remove('active'));
                const match = Array.from(document.querySelectorAll('.filtro-btn')).find(b => b.getAttribute('onclick') && b.getAttribute('onclick').includes("'" + filtroAtual + "'"));
                if (match) match.classList.add('active');
            } catch(e) { console.warn('Erro ao definir filtro ativo:', e); }
            // carregarGastos retorna os dados; usar para decidir sobre tutorial
            (async () => {
                try {
                    const gastosData = await carregarGastos();
                    // Quando a configuração de tutorial estiver ativa, só iniciar se não houver gastos
                    const mostrarTutorial = <?php echo json_encode($configuracoes['mostrar_tutorial'] ?? true); ?>;
                    // also check localStorage so returning from admin doesn't re-show tutorial
                    const tutorialVistoLocal = localStorage.getItem('tutorial_visto') === '1';
                    // hide the prompt if tutorial already seen or if user already has gastos
                    try {
                        const promptEl = document.getElementById('tutorialPrompt');
                        if (tutorialVistoLocal || (gastosData && Array.isArray(gastosData.gastos) && gastosData.gastos.length > 0)) {
                            if (promptEl) promptEl.classList.add('hidden');
                        }
                    } catch(e) { console.warn('Erro ao manipular tutorial prompt', e); }
                    if (mostrarTutorial && !tutorialVistoLocal && gastosData && Array.isArray(gastosData.gastos) && gastosData.gastos.length === 0) {
                        setTimeout(() => {
                            iniciarTutorial();
                        }, 1000);
                    }
                    // indicate success in debug status
                    try { const dbg = document.getElementById('debugStatus'); if (dbg) dbg.textContent = 'JS: gastos carregados.'; } catch(e) {}
                } catch (e) {
                    console.error('Erro na inicialização de gastos:', e);
                    try { const dbg = document.getElementById('debugStatus'); if (dbg) dbg.textContent = 'JS: erro ao carregar gastos (veja console)'; } catch(e) {}
                    showToast('Erro ao carregar alguns dados iniciais (veja console)', 'error');
                } finally {
                    // Mesmo que carregarGastos falhe, tente sempre atualizar o gráfico e carregar fixos
                    try { await atualizarGrafico(); } catch(e) { console.error('Falha ao atualizar gráfico no finally:', e); }
                    try { carregarGastosFixos(); } catch(e) { console.error('Falha ao carregar gastos fixos no finally:', e); }
                }
            })();

            // Legacy: a UI de checkbox `gastoFixo` foi removida do markup.
            // O formulário inline para gastos fixos é usado atualmente
            // (veja `adicionarGastoFixoInline()` e `toggleGastoFixo()` abaixo).
            // Mantemos o código mínimo aqui por compatibilidade futura.

            // Adicionar event listeners aos botões do tutorial
            const btnProximo = document.querySelector('.tutorial-next');
            const btnAnterior = document.querySelector('.tutorial-prev');
            
            if (btnProximo) {
                btnProximo.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    tutorialProximo();
                });
            }
            
            if (btnAnterior) {
                btnAnterior.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    tutorialAnterior();
                });
            }

            // (removed immediate tutorial start here — tutorial will be started after we know if there are gastos)
        });

        // Carregar categorias
        // Busca as categorias do servidor e preenche os selects/visuais.
        // Também tenta obter dados de totais do gráfico para exibir valores consolidados.
        async function carregarCategorias() {
            try {
                let catUrl = `api/categorias.php?filtro=${filtroAtual}`;
                if (filtroAtual === 'ano' && selectedYear) catUrl += `&year=${selectedYear}`;
                const response = await fetch(catUrl, { credentials: 'same-origin' });
                const data = await response.json();

                try { const dbg = document.getElementById('debugStatus'); if (dbg) dbg.textContent = 'JS: categorias recebidas'; } catch(e) {}

                // também buscar os totais do gráfico (inclui contribuições de gastos fixos)
                let grafData = null;
                try {
                    let gurl = `api/grafico.php?filtro=${filtroAtual}`;
                    if (filtroAtual === 'ano' && selectedYear) gurl += `&year=${selectedYear}`;
                    const gresp = await fetch(gurl, { credentials: 'same-origin' });
                    grafData = await gresp.json();
                } catch (e) {
                    console.warn('Não foi possível obter dados do gráfico para totais (continuando sem fixos):', e);
                }

                const totalsMap = {};
                if (grafData && grafData.success && Array.isArray(grafData.categorias)) {
                    grafData.categorias.forEach(c => { totalsMap[c.id] = parseFloat(c.total || 0); });
                }

                if (data && data.success) {
                    const container = document.getElementById('categoriasList');
                    container.innerHTML = '';

                    // we no longer render category chips/buttons here because the select provides category choice

                    // preencher selects de categorias (Gastos Fixos, Adicionar Gasto, Inline)
                    const selFixo = document.getElementById('categoriaFixoSelect');
                    const selAdd = document.getElementById('categoriaSelect');
                    const selInline = document.getElementById('categoriaInlineSelect');
                    if (selFixo) {
                        selFixo.innerHTML = '<option value="">-- selecione --</option>';
                        data.categorias.forEach(cat => {
                            const opt = document.createElement('option');
                            opt.value = cat.id;
                            opt.textContent = cat.nome;
                            selFixo.appendChild(opt);
                        });
                    }
                    if (selAdd) {
                        selAdd.innerHTML = '<option value="">-- selecione --</option>';
                        data.categorias.forEach(cat => {
                            const opt = document.createElement('option');
                            opt.value = cat.id;
                            opt.textContent = cat.nome;
                            selAdd.appendChild(opt);
                        });
                        // quando o usuário muda o select, atualizar a seleção e realçar
                        selAdd.onchange = function() {
                            const v = parseInt(this.value) || null;
                            if (v) selecionarCategoria(v);
                        }
                    }
                    if (selInline) {
                        selInline.innerHTML = '<option value="">-- selecione --</option>';
                        data.categorias.forEach(cat => {
                            const opt = document.createElement('option');
                            opt.value = cat.id;
                            opt.textContent = cat.nome;
                            selInline.appendChild(opt);
                        });
                        selInline.onchange = function() {
                            const v = parseInt(this.value) || null;
                            if (v) selecionarCategoria(v);
                        }
                    }

                    // Preencher painel 'Gastos Totais' usando totalsMap when available
                    const totContainer = document.getElementById('gastosTotais');
                    if (totContainer) {
                        totContainer.innerHTML = '';
                        data.categorias.forEach(cat => {
                            const row = document.createElement('div');
                            row.style.display = 'flex';
                            row.style.justifyContent = 'space-between';
                            row.style.alignItems = 'center';
                            row.style.padding = '8px';
                            row.style.borderRadius = '8px';
                            row.style.background = '#fff';
                            row.style.border = '1px solid #f0f0f0';

                            const left = document.createElement('div');
                            left.innerHTML = `<strong style="color:${cat.cor_hex};">● ${cat.nome}</strong><div style="font-size:12px;color:#666;">Clique em Ver para listar os gastos desta categoria</div>`;

                            const right = document.createElement('div');
                            const totalVal = (totalsMap[cat.id] !== undefined) ? totalsMap[cat.id] : (cat.total || 0);
                            const totalFormatted = 'R$ ' + parseFloat(totalVal).toFixed(2).replace('.', ',');
                            right.innerHTML = `<span style="font-weight:700; margin-right:12px; color:#333;">${totalFormatted}</span><button class="filtro-btn" style="padding:6px 10px; font-size:12px;" onclick="carregarGastos(${cat.id})">Ver</button>`;

                            row.appendChild(left);
                            row.appendChild(right);
                            totContainer.appendChild(row);
                        });
                    }
                    // atualizar estado do botão de excluir (padrão: desabilitado até selecionar)
                    atualizarEstadoBotaoExcluirCategoria();
                    try { const dbg = document.getElementById('debugStatus'); if (dbg) dbg.textContent = 'JS: categorias preenchidas'; } catch(e) {}
                }
            } catch (error) {
                console.error('Erro ao carregar categorias:', error);
                try { const dbg = document.getElementById('debugStatus'); if (dbg) dbg.textContent = 'JS: falha ao carregar categorias'; } catch(e) {}
                // set selects to show error so user sees something
                const selAdd = document.getElementById('categoriaSelect'); if (selAdd) selAdd.innerHTML = '<option value="">Erro ao carregar categorias</option>';
                const selInline = document.getElementById('categoriaInlineSelect'); if (selInline) selInline.innerHTML = '<option value="">Erro ao carregar categorias</option>';
            }
        }

        // Adicionar categoria
        // Envia nome da nova categoria ao servidor e recarrega a lista.
        async function adicionarCategoria() {
            // Accept name from either main add form or the Gastos Fixos card
            const elMain = document.getElementById('novaCategoria');
            const elFixo = document.getElementById('novaCategoriaFixo');
            const nome = (elMain && elMain.value) ? elMain.value : (elFixo && elFixo.value ? elFixo.value : '');
            if (!nome) {
                showToast('Digite um nome para a categoria', 'error');
                return;
            }

            try {
                const response = await fetch('api/categorias.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'criar', nome })
                });

                const data = await response.json();
                if (data.success) {
                    if (elMain) elMain.value = '';
                    if (elFixo) elFixo.value = '';
                    carregarCategorias();
                } else {
                    showToast(data.message || 'Erro ao criar categoria', 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showToast('Erro ao conectar ao servidor', 'error');
            }
        }

        // Helper to add category from Gastos Fixos card specifically (keeps api contract simple)
        function adicionarCategoriaFixo() {
            adicionarCategoria();
        }

        // Selecionar categoria (marca visualmente e guarda o id)
        function selecionarCategoria(id) {
            categoriaIdSelecionada = id;
            // realçar botão selecionado
            document.querySelectorAll('#categoriasList button').forEach(btn => {
                if (btn.dataset.id == id) {
                    btn.style.boxShadow = '0 6px 18px rgba(102,126,234,0.25)';
                    btn.style.transform = 'translateY(-2px)';
                } else {
                    btn.style.boxShadow = 'none';
                    btn.style.transform = 'none';
                }
            });
            // atualizar selects
            const s1 = document.getElementById('categoriaFixoSelect');
            const s2 = document.getElementById('categoriaSelect');
            const s3 = document.getElementById('categoriaInlineSelect');
            if (s1) s1.value = id;
            if (s2) s2.value = id;
            if (s3) s3.value = id;
            atualizarEstadoBotaoExcluirCategoria();
        }

        // Atualiza estado do botão de excluir categoria (habilita somente se houver seleção)
        function atualizarEstadoBotaoExcluirCategoria() {
            const btn = document.getElementById('btnDeleteCategoria');
            if (!btn) return;
            btn.disabled = !categoriaIdSelecionada;
            btn.style.opacity = btn.disabled ? '0.6' : '1';
            btn.style.cursor = btn.disabled ? 'not-allowed' : 'pointer';
        }

        // OBS: função antiga `adicionarGastoFixo()` removida — utilize
        // `adicionarGastoFixoInline()` que é a implementação ativa no markup.

        // Criar gasto fixo (inline)
        // Envia ao endpoint `api/gastos.php` com a flag `fixo=true`.
        async function adicionarGastoFixoInline() {
            const sel = document.getElementById('categoriaInlineSelect');
            let catId = null;
            if (sel && sel.value) catId = parseInt(sel.value);
            if (!catId) catId = categoriaIdSelecionada;
            if (!catId) {
                showToast('Selecione uma categoria antes de criar o gasto fixo', 'error');
                return;
            }
            const descricao = document.getElementById('descricaoInline').value;
            const valor = parseFloat(document.getElementById('valorInline').value);
            const startDate = document.getElementById('startDateInline').value || null;
            const periodicidade = document.getElementById('periodicidadeInline').value;
            const periodos = parseInt(document.getElementById('periodosInline')?.value) || 0;
            if (!valor || isNaN(valor) || valor <= 0) {
                showToast('Informe um valor válido para o gasto fixo', 'error');
                return;
            }
            try {
                const response = await fetch('api/gastos.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'criar',
                        categoria_id: catId,
                        descricao: descricao,
                        valor: valor,
                        tipo_gasto: document.getElementById('tipoGastoInlineSelect') ? document.getElementById('tipoGastoInlineSelect').value : 'opcional',
                        data_gasto: startDate,
                        fixo: true,
                        periodicidade: periodicidade,
                        periodos: periodos
                    })
                });
                const res = await response.json();
                if (res.success) {
                    document.getElementById('descricaoInline').value = '';
                    document.getElementById('valorInline').value = '';
                    document.getElementById('startDateInline').value = '';
                    carregarGastos();
                    carregarCategorias();
                    atualizarGrafico();
                    carregarGastosFixos();
                    showToast('Gasto fixo criado com sucesso', 'success');
                    // Retomar tutorial se estava aguardando o primeiro gasto
                    retomarTutorialAposGasto();
                } else {
                    showToast('Erro: ' + (res.message || 'Não foi possível criar gasto fixo'), 'error');
                }
            } catch (e) {
                console.error('Erro ao criar gasto fixo (inline):', e);
                showToast('Erro ao conectar ao servidor', 'error');
            }
        }

        // Exclui a categoria atualmente selecionada (usar botão ao lado do campo)
        function excluirCategoriaSelecionada() {
            if (!categoriaIdSelecionada) {
                showToast('Selecione uma categoria para excluir', 'error');
                return;
            }
            // reutiliza função existente que já mostra confirmação
            deletarCategoria(categoriaIdSelecionada);
        }

        // Alterna entre formulário normal e formulário de gasto fixo inline (dentro do card)
        function toggleGastoFixo() {
            const btn = document.getElementById('toggleFixoBtn');
            const normal = document.getElementById('normalAddBody');
            const inline = document.getElementById('inlineFixoBlock');
            if (!btn || !normal || !inline) return;
            if (inline.style.display === 'none' || inline.style.display === '') {
                normal.style.display = 'none';
                inline.style.display = 'block';
                btn.textContent = 'Voltar para Gasto Normal';
                setTimeout(() => inline.scrollIntoView({ behavior: 'smooth', block: 'center' }), 200);
            } else {
                inline.style.display = 'none';
                normal.style.display = 'block';
                btn.textContent = 'Mudar para Gasto Fixo';
                setTimeout(() => normal.scrollIntoView({ behavior: 'smooth', block: 'center' }), 200);
            }
        }

        // Criar gasto normal
        // Envia um gasto pontual (não fixo) ao servidor.
        async function adicionarGasto() {
            if (!categoriaIdSelecionada) {
                showToast('Selecione uma categoria antes de registrar o gasto', 'error');
                return;
            }
            const descricao = document.getElementById('descricaoGasto').value || '';
            const valor = parseFloat(document.getElementById('valorGasto').value);
            const dataG = document.getElementById('dataGasto').value;
            const horaG = document.getElementById('horaGasto').value;
            if (!valor || isNaN(valor) || valor <= 0) {
                showToast('Informe um valor válido para o gasto', 'error');
                return;
            }
            try {
                const response = await fetch('api/gastos.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'criar',
                        categoria_id: categoriaIdSelecionada,
                        descricao: descricao,
                        valor: valor,
                        tipo_gasto: document.getElementById('tipoGastoSelect') ? document.getElementById('tipoGastoSelect').value : 'opcional',
                        data_gasto: dataG,
                        hora_gasto: horaG,
                        fixo: false
                    })
                });
                const res = await response.json();
                if (res.success) {
                    document.getElementById('descricaoGasto').value = '';
                    document.getElementById('valorGasto').value = '';
                    document.getElementById('dataGasto').valueAsDate = new Date();
                    document.getElementById('horaGasto').value = new Date().toTimeString().slice(0,5);
                    carregarGastos();
                    carregarCategorias();
                    atualizarGrafico();
                    showToast('Gasto registrado com sucesso', 'success');
                    // Retomar tutorial se estava aguardando o primeiro gasto
                    retomarTutorialAposGasto();
                } else {
                    showToast('Erro: ' + (res.message || 'Não foi possível registrar gasto'), 'error');
                }
            } catch (e) {
                console.error('Erro ao registrar gasto:', e);
                showToast('Erro ao conectar ao servidor', 'error');
            }
        }

        // Carregar gastos (opcional: passar categoria para filtrar)
        // Recupera gastos (inclui ocorrências virtuais de fixos) e popula a lista.
        async function carregarGastos(categoria = null) {
            try {
                // Se foi passada uma categoria, atualizar o filtro; se explicitamente null, limpar o filtro
                if (categoria !== undefined) {
                    categoriaFilter = categoria;
                }

                // Allow optional category filter
                const url = `api/gastos.php?filtro=${filtroAtual}${categoriaFilter ? '&categoria_id=' + categoriaFilter : ''}`;
                console.debug('carregarGastos ->', url);

                // Preferir `apiFetch` centralizado quando disponível (melhor tratamento de erros)
                let data = null;
                if (typeof window.apiFetch === 'function') {
                    try {
                        data = await window.apiFetch(url);
                    } catch (e) {
                        console.error('carregarGastos: apiFetch falhou', e);
                        showToast('Erro ao carregar gastos: ' + (e.message || 'ver console'), 'error', 7000);
                        return null;
                    }
                } else {
                    const response = await fetch(url, { credentials: 'same-origin' });
                    if (!response.ok) {
                        console.error('carregarGastos: resposta não OK', response.status, response.statusText);
                        const txt = await response.text().catch(() => '');
                        console.error('carregarGastos: resposta (texto):', txt);
                        showToast('Erro ao carregar gastos (status ' + response.status + ')', 'error');
                        return null;
                    }

                    // Verificar content-type para evitar tentar parsear HTML como JSON
                    const contentType = response.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) {
                        const txt = await response.text().catch(() => '');
                        console.error('carregarGastos: resposta não JSON recebida:', txt.slice(0,200));
                        showToast('Resposta inesperada do servidor (não-JSON). Veja console.', 'error', 7000);
                        return null;
                    }

                    try {
                        data = await response.json();
                    } catch (e) {
                        const txt = await response.text().catch(() => '');
                        console.error('carregarGastos: falha ao parsear JSON:', e, txt);
                        showToast('Erro ao interpretar resposta do servidor. Veja console.', 'error', 7000);
                        return null;
                    }
                }

                console.debug('carregarGastos resultado', data);

                const container = document.getElementById('gastosList');
                if (!data || !data.success) {
                    if (container) container.innerHTML = '<div class="empty-state"><p>Erro ao carregar gastos</p></div>';
                    return data || null;
                }

                // ensure gastos is an array
                if (!Array.isArray(data.gastos) || data.gastos.length === 0) {
                    if (container) container.innerHTML = '<div class="empty-state"><p>Nenhum gasto neste período</p></div>';
                    // still return the data so callers can inspect projection/totals
                    return data;
                }

                // defensive: proteger todo o bloco de renderização para evitar exceptions inesperadas
                try {
                    const gastosArr = (data && Array.isArray(data.gastos)) ? data.gastos : [];
                    container.innerHTML = gastosArr.map(gasto => {
                                const isF = gasto.fixo ? true : false;
                                const catColor = gasto.cor_categoria || '#999';
                                const catName = gasto.categoria || '';
                                const descricao = gasto.descricao || '';
                                const valor = parseFloat(gasto.valor).toFixed(2).replace('.', ',');
                                const dataG = gasto.data_gasto || '';

                                let actionButtons = '';
                                if (isF) {
                                    actionButtons = `<button class="btn-small" style="background:#ff7043; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer;" onclick="deletarGastoFixo(${gasto.gasto_fixo_id})">Remover Fixo</button>`;
                                } else {
                                    actionButtons = `<button class="btn-small" style="background:#ff6b6b; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer;" onclick="deletarGasto(${gasto.id})">Deletar</button>`;
                                }

                                const historyArr = Array.isArray(gasto.historico) ? gasto.historico : [];
                                const historyBtn = (historyArr.length) ? `<button class="btn-small" style="background:#667eea; color:white; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; margin-left:6px;" onclick="toggleHistory('${gasto.id}')">Histórico (${historyArr.length})</button>` : '';

                                const historyHtml = (historyArr.length) ? `
                                    <div id="gasto-history-${gasto.id}" class="gasto-history" style="display:none; padding:8px 18px; border-left:4px solid #f0f0f0; background:#fafafa; margin:6px 0 12px 0; border-radius:6px;">
                                        ${historyArr.map(h => `<div style="display:flex; justify-content:space-between; gap:12px; padding:6px 0; border-bottom:1px dashed #eee;"><div style="color:#444; font-size:13px;">${h.descricao || '<i>sem descrição</i>'}</div><div style="color:#999; font-size:13px;">R$ ${parseFloat(h.valor).toFixed(2).replace('.', ',')} • ${new Date(h.data_registro).toLocaleString()}</div></div>`).join('')}
                                    </div>` : '';

                                return `
                                <div class="gasto-item">
                                    <div class="gasto-info">
                                        <div class="gasto-categoria" style="color: ${catColor}">●${catName}${isF ? ' • Fixo' : ''}</div>
                                        <div class="gasto-descricao">${descricao} - ${dataG}</div>
                                    </div>
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <div class="gasto-valor">-R$ ${valor}</div>
                                        ${actionButtons}
                                        ${historyBtn}
                                    </div>
                                </div>
                                ${historyHtml}
                                `;
                            }).join('');
                } catch (renderErr) {
                    console.error('Erro ao renderizar lista de gastos:', renderErr, data);
                    if (container) container.innerHTML = '<div class="empty-state"><p>Erro ao processar dados de gastos</p></div>';
                    showToast('Erro ao processar dados de gastos (veja console)', 'error', 6000);
                    return data;
                }
                            // Show annual projection if provided
                            if (data.projecao_anual !== undefined && document.getElementById('projecaoAnual')) {
                                document.getElementById('projecaoAnual').textContent = 'Projeção anual (fixos): R$ ' + parseFloat(data.projecao_anual).toFixed(2).replace('.', ',');
                            } else if (document.getElementById('projecaoAnual')) {
                                document.getElementById('projecaoAnual').textContent = '';
                            }
                // retornar os dados para quem chamou (útil para decidir sobre tutorial)
                return data;
            } catch (error) {
                console.error('Erro:', error);
                return null;
            }
        }

        // Alterar filtro
        // Populate year selector with a reasonable range (currentYear-2 .. currentYear+5)
        function populateYearSelector() {
            const sel = document.getElementById('yearSelector');
            if (!sel) return;
            const now = new Date();
            const currentYear = now.getFullYear();
            const start = currentYear - 2;
            const end = currentYear + 5;
            sel.innerHTML = '';
            for (let y = start; y <= end; y++) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.text = y;
                sel.appendChild(opt);
            }
            // default to current year
            sel.value = currentYear;
            selectedYear = currentYear;
            sel.addEventListener('change', function() {
                selectedYear = parseInt(sel.value, 10) || null;
                // when changing year while filtro is 'ano', refresh chart
                if (filtroAtual === 'ano') {
                    atualizarGrafico();
                    // also refresh category totals to reflect selected year
                    carregarCategorias();
                }
            });
        }

        function alterarFiltro(filtro) {
            filtroAtual = filtro;
            // Atualizar estado visual dos botões de filtro
            try {
                document.querySelectorAll('.filtro-btn').forEach(btn => btn.classList.remove('active'));
                // encontrar botão cujo onclick chama alterarFiltro com o filtro atual
                const match = Array.from(document.querySelectorAll('.filtro-btn')).find(b => b.getAttribute('onclick') && b.getAttribute('onclick').includes("'" + filtro + "'"));
                if (match) match.classList.add('active');
            } catch(e) { console.warn('Erro ao atualizar botões de filtro', e); }
            // Mostrar ou ocultar seletor de ano
            try {
                const sel = document.getElementById('yearSelector');
                if (sel) {
                    if (filtro === 'ano') {
                        sel.style.display = 'inline-block';
                        // ensure selectedYear is set
                        selectedYear = parseInt(sel.value, 10) || new Date().getFullYear();
                    } else {
                        sel.style.display = 'none';
                    }
                }
            } catch(e) { console.warn('Erro ao manipular yearSelector', e); }
            carregarCategorias();
            carregarGastos();
            atualizarGrafico();
        }

        // Atualizar gráfico
        // Solicita dados de resumo ao endpoint `api/grafico.php` e redraw do chart.
        async function atualizarGrafico() {
            try {
                let url = `api/grafico.php?filtro=${filtroAtual}`;
                if (filtroAtual === 'ano' && selectedYear) {
                    url += `&year=${selectedYear}`;
                }
                const response = await fetch(url, { credentials: 'same-origin' });
                const data = await response.json();

                if (data.success) {
                    // Atualizar estatísticas
                    document.getElementById('statRenda').textContent = 'R$ ' + data.renda.toFixed(2).replace('.', ',');
                    document.getElementById('statGastos').textContent = 'R$ ' + data.gastos_total.toFixed(2).replace('.', ',');
                    document.getElementById('statSaldo').textContent = 'R$ ' + data.saldo.toFixed(2).replace('.', ',');

                    // Criar gráfico
                    const ctx = document.getElementById('graficoGastos').getContext('2d');
                    
                    if (grafico) {
                        grafico.destroy();
                    }

                    grafico = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Gastos', 'Saldo'],
                            datasets: [{
                                data: [data.gastos_total, data.saldo],
                                backgroundColor: [
                                    '<?php echo $configuracoes["cor_gastos"] ?? "#FF6B6B"; ?>',
                                    '<?php echo $configuracoes["cor_grafico_1"] ?? "#4ECDC4"; ?>'
                                ],
                                borderColor: 'white',
                                borderWidth: 3
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        font: { size: 14, weight: 'bold' }
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Erro ao atualizar gráfico:', error);
            }
        }

        // Tutorial
        function iniciarTutorial() {
            tutorialAtivo = true;
            etapaTutorial = 0;
            document.getElementById('tutorialOverlay').classList.add('active');
            mostrarEtapaTutorial();
        }

        function mostrarEtapaTutorial() {
            const tutorial = tutoriais[etapaTutorial];
            const elemento = document.getElementById(tutorial.elemento);
            const box = document.getElementById('tutorialBox');

            if (elemento) {
                const rect = elemento.getBoundingClientRect();
                box.style.left = (rect.left + window.scrollX) + 'px';
                box.style.top = (rect.bottom + window.scrollY + 10) + 'px';
            } else {
                // Se elemento não existir, centralizar a box
                box.style.left = '50%';
                box.style.top = '50%';
                box.style.transform = 'translate(-50%, -50%)';
            }

            document.getElementById('tutorialTitle').textContent = tutorial.title;
            document.getElementById('tutorialText').textContent = tutorial.text;
            box.style.display = 'block';
        }

        function tutorialProximo() {
            console.log('Próximo clicado, etapa:', etapaTutorial, 'total:', tutoriais.length);
            if (etapaTutorial < tutoriais.length - 1) {
                etapaTutorial++;
                // Desativar overlay permanentemente até que o usuário crie um gasto
                overlayDesativadoTemporariamente = true;
                document.getElementById('tutorialOverlay').classList.remove('active');
                document.getElementById('tutorialBox').style.display = 'none';
                console.log('Overlay desativado. Aguardando primeiro gasto...');
            } else {
                encerrarTutorial();
            }
        }

        function tutorialAnterior() {
            if (etapaTutorial > 0) {
                etapaTutorial--;
                mostrarEtapaTutorial();
            }
        }

        function encerrarTutorial() {
            tutorialAtivo = false;
            document.getElementById('tutorialOverlay').classList.remove('active');
            document.getElementById('tutorialBox').style.display = 'none';
            overlayDesativadoTemporariamente = false;
            // Mostrar mensagem de conclusão (usando toast para não bloquear)
            showToast('✅ Tutorial concluído! Você já conhece todas as funcionalidades do sistema.', 'success');
            // marcar localmente para não reexibir ao voltar do admin
            try { localStorage.setItem('tutorial_visto', '1'); } catch(e) { }
            // Notificar ao servidor que o tutorial foi visto (se falhar, localStorage ainda impede reexibição)
            fetch('api/configuracoes.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'tutorial_visto' })
            }).then(res => res.json())
            .then(data => {
                console.log('Tutorial marcado como visto:', data);
            })
            .catch(e => console.error('Erro ao atualizar tutorial:', e));
        }

        function fecharTutorialPrompt() {
            document.getElementById('tutorialPrompt').classList.add('hidden');
        }

        // Retomar tutorial após o primeiro gasto ser registrado
        function retomarTutorialAposGasto() {
            if (overlayDesativadoTemporariamente && !tutorialAtivo && etapaTutorial > 0) {
                console.log('Retomando tutorial na etapa:', etapaTutorial);
                tutorialAtivo = true;
                overlayDesativadoTemporariamente = false;
                document.getElementById('tutorialOverlay').classList.add('active');
                mostrarEtapaTutorial();
                showToast('👉 Continue acompanhando o tutorial!', 'info', 3000);
            }
        }

        // (Duplicated simple confirm/alert handlers removed)

        // Toggle histórico display
        function toggleHistory(id) {
            const el = document.getElementById('gasto-history-' + id);
            if (!el) return;
            el.style.display = (el.style.display === 'block') ? 'none' : 'block';
        }

        // Carregar lista de gastos fixos (definições) para gerenciamento
        // Usado para a interface de gerenciamento de fixos (listar/remover).
        async function carregarGastosFixos() {
            try {
                const resp = await fetch(`api/gastos.php?listar_fixos=1`, { credentials: 'same-origin' });
                const data = await resp.json();
                const container = document.getElementById('listaGastosFixos');
                if (!container) return;
                container.innerHTML = '';
                if (!data || !data.success || !Array.isArray(data.fixos) || data.fixos.length === 0) {
                    container.innerHTML = '<div style="color:#666; font-size:13px;">Nenhum gasto fixo registrado</div>';
                    return;
                }
                data.fixos.forEach(f => {
                    const el = document.createElement('div');
                    el.style.display = 'flex';
                    el.style.justifyContent = 'space-between';
                    el.style.alignItems = 'center';
                    el.style.padding = '8px';
                    el.style.border = '1px solid #f0f0f0';
                    el.style.borderRadius = '8px';
                    el.style.background = '#fff';
                    const left = document.createElement('div');
                    left.innerHTML = `<div style="font-weight:700;">${f.descricao || 'Sem descrição'}</div><div style="font-size:12px; color:#666;">${f.periodicidade} • R$ ${parseFloat(f.valor).toFixed(2).replace('.',',')} • Início: ${f.start_date} • Periodos: ${f.periodos}</div>`;
                    const right = document.createElement('div');
                    const btn = document.createElement('button');
                    btn.className = 'filtro-btn';
                    btn.style.padding = '6px 10px';
                    btn.style.fontSize = '12px';
                    btn.textContent = 'Remover';
                    btn.onclick = function() { deletarGastoFixo(f.id); };
                    right.appendChild(btn);
                    el.appendChild(left);
                    el.appendChild(right);
                    container.appendChild(el);
                });
            } catch (e) {
                console.error('Erro ao carregar gastos fixos:', e);
            }
        }
    </script>
    <!-- Toast / alert interno -->
    <div id="siteToast" style="position:fixed; top:20px; right:20px; z-index:3000; min-width:260px; display:none;
        background: rgba(0,0,0,0.8); color:white; padding:12px 16px; border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.2);">
        <div id="siteToastMsg" style="font-size:14px;"></div>
    </div>
    <script>
        // Compatibilidade: delegar `showToast` para `assets/js/main.js` se disponível.
        // Caso contrário, definimos um fallback simples que reutiliza o markup `#siteToast`.
        if (typeof window.showToast !== 'function') {
            window.showToast = function(message, type = 'info', duration = 4000) {
                const toast = document.getElementById('siteToast');
                const msg = document.getElementById('siteToastMsg');
                if (!toast || !msg) {
                    console.log('Toast (fallback):', message);
                    return;
                }
                msg.textContent = message;
                toast.style.display = 'block';
                if (type === 'success') toast.style.background = 'linear-gradient(90deg,#2ecc71,#27ae60)';
                else if (type === 'error') toast.style.background = 'linear-gradient(90deg,#e74c3c,#c0392b)';
                else toast.style.background = 'rgba(0,0,0,0.8)';
                clearTimeout(window._siteToastTimer);
                window._siteToastTimer = setTimeout(() => {
                    toast.style.display = 'none';
                }, duration);
            };
        }
    </script>
    <!-- Confirm modal e showConfirm centralizados em `assets/js/main.js`. -->
    <script>
        // Stub de compatibilidade: se `assets/js/main.js` ainda não carregou, garantimos
        // que `showConfirm` exista (usa `confirm()` como fallback simples).
        if (typeof window.showConfirm !== 'function') {
            window.showConfirm = function(message, title = 'Confirmar ação') {
                return new Promise(resolve => {
                    const ok = confirm(title + "\n\n" + message);
                    resolve(Boolean(ok));
                });
            };
        }
    </script>
    <script>
        // Override functions to use modal
        async function logout() {
            const ok = await showConfirm('Tem certeza que deseja sair?', 'Sair');
            if (!ok) return;
            try {
                await fetch('api/logout.php', { method: 'POST', credentials: 'same-origin' });
                window.location.href = 'login.php';
            } catch (e) {
                console.error(e);
                window.location.href = 'login.php';
            }
        }

        async function deletarCategoria(id) {
            const ok = await showConfirm('Tem certeza que deseja deletar esta categoria? Todos os gastos associados também serão removidos.', 'Deletar Categoria');
            if (!ok) return;
            try {
                const response = await fetch('api/categorias.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'deletar', id })
                });
                const data = await response.json();
                if (data.success) carregarCategorias();
                else showToast('Erro: ' + (data.message || 'Não foi possível deletar'), 'error');
            } catch (error) {
                console.error('Erro:', error);
                showToast('Erro ao conectar ao servidor', 'error');
            }
        }

        async function deletarGasto(id) {
            const ok = await showConfirm('Tem certeza que deseja deletar este gasto? Esta ação não pode ser desfeita.', 'Deletar Gasto');
            if (!ok) return;
            try {
                const res = await fetch('api/gastos.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'deletar', id })
                });
                const data = await res.json();
                if (data.success) {
                    carregarGastos();
                    atualizarGrafico();
                } else {
                    showToast('Erro: ' + (data.message || 'Não foi possível deletar'), 'error');
                }
            } catch (e) {
                console.error('Erro ao deletar gasto:', e);
                showToast('Erro ao conectar ao servidor', 'error');
            }
        }

        async function deletarGastoFixo(id) {
            const ok = await showConfirm('Tem certeza que deseja remover este gasto fixo? Isso não removerá lançamentos históricos já existentes.', 'Remover Gasto Fixo');
            if (!ok) return;
            try {
                const res = await fetch('api/gastos.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'deletar_fixo', id })
                });
                const data = await res.json();
                if (data.success) {
                    carregarGastos();
                    carregarCategorias();
                    atualizarGrafico();
                    carregarGastosFixos();
                } else {
                    showToast('Erro: ' + (data.message || 'Não foi possível remover o fixo'), 'error');
                }
            } catch (e) {
                console.error('Erro ao remover gasto fixo:', e);
                showToast('Erro ao conectar ao servidor', 'error');
            }
        }
    </script>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>
