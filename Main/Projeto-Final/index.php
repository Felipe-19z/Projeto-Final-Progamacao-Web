<?php
require_once 'config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];
$usuario = obter_usuario($usuario_id);
$configuracoes = obter_configuracoes($usuario_id);

// Se não existem configurações, criar padrões
if (!$configuracoes) {
    $conn->query("INSERT INTO configuracoes_usuario (usuario_id) VALUES ($usuario_id)");
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
            flex: 1;
        }

        .gasto-categoria {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .gasto-descricao {
            font-size: 12px;
            color: #999;
            margin-top: 3px;
        }

        .gasto-valor {
            font-weight: 700;
            color: #ff6b6b;
            font-size: 16px;
        }

        .filtro-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
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
            <a href="configuracoes.php" class="nav-link" id="navConfiguracoes">⚙️ Configurações</a>
            <a href="ajuda.php" class="nav-link" id="navAjuda">❓ Ajuda</a>
            <div class="nav-user">
                <div class="user-avatar"><?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($usuario['nome']); ?></span>
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
        </div>

        <!-- Grid Principal -->
        <div class="main-grid">
            <!-- Card de Adicionar Gasto -->
            <div class="card">
                <div class="card-title">➕ Adicionar Gasto</div>

                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="novaCategoria" class="form-input" placeholder="Ex: Alimentação" style="flex: 1;">
                        <button onclick="adicionarCategoria()" style="padding: 10px 15px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">+</button>
                    </div>
                    <div id="categoriasList" style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <input type="text" id="descricaoGasto" class="form-input" placeholder="Descrição do gasto">
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
            </div>

            <!-- Card de Gráfico -->
            <div class="card">
                <div class="card-title">📊 Resumo Financeiro</div>

                <div class="filtro-container">
                    <button class="filtro-btn active" onclick="alterarFiltro('dia')">Dia</button>
                    <button class="filtro-btn" onclick="alterarFiltro('semana')">Semana</button>
                    <button class="filtro-btn" onclick="alterarFiltro('mes')">Mês</button>
                    <button class="filtro-btn" onclick="alterarFiltro('ano')">Ano</button>
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
        let filtroAtual = 'dia';
        let grafico = null;
        let tutorialAtivo = false;
        let etapaTutorial = 0;
        let categoriaIdSelecionada = null;
        let overlayDesativadoTemporariamente = false;

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
            // Definir data e hora atuais
            document.getElementById('dataGasto').valueAsDate = new Date();
            document.getElementById('horaGasto').value = new Date().toTimeString().slice(0, 5);

            // Carregar dados
            carregarCategorias();
            carregarGastos();
            atualizarGrafico();

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

            // Verificar se deve mostrar tutorial
            const mostrarTutorial = <?php echo json_encode($configuracoes['mostrar_tutorial'] ?? true); ?>;
            if (mostrarTutorial) {
                setTimeout(() => {
                    iniciarTutorial();
                }, 1000);
            }
        });

        // Carregar categorias
        async function carregarCategorias() {
            try {
                const response = await fetch('api/categorias.php');
                const data = await response.json();

                if (data.success) {
                    const container = document.getElementById('categoriasList');
                    container.innerHTML = '';

                    data.categorias.forEach(cat => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.style.padding = '8px 15px';
                        btn.style.background = cat.cor_hex;
                        btn.style.color = 'white';
                        btn.style.border = 'none';
                        btn.style.borderRadius = '5px';
                        btn.style.cursor = 'pointer';
                        btn.style.fontWeight = '600';
                        btn.style.fontSize = '12px';
                        btn.innerHTML = `${cat.nome} <span style="margin-left: 5px; cursor: pointer;" onclick="deletarCategoria(${cat.id})">✕</span>`;
                        btn.onclick = () => selecionarCategoria(cat.id);
                        container.appendChild(btn);
                    });
                }
            } catch (error) {
                console.error('Erro ao carregar categorias:', error);
            }
        }

        // Adicionar categoria
        async function adicionarCategoria() {
            const nome = document.getElementById('novaCategoria').value;
            if (!nome) {
                alert('Digite um nome para a categoria');
                return;
            }

            try {
                const response = await fetch('api/categorias.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'criar', nome })
                });

                const data = await response.json();
                if (data.success) {
                    document.getElementById('novaCategoria').value = '';
                    carregarCategorias();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        // Selecionar categoria
        function selecionarCategoria(id) {
            categoriaIdSelecionada = id;
            console.log('Categoria selecionada:', id);
            
            // Remover seleção anterior
            document.querySelectorAll('#categoriasList button').forEach(btn => {
                btn.style.opacity = '0.6';
            });
            
            // Destacar categoria selecionada
            event.target.closest('button').style.opacity = '1';
            event.target.closest('button').style.border = '3px solid #333';
        }

        // Adicionar gasto
        async function adicionarGasto() {
            console.log('adicionarGasto chamado');
            console.log('categoriaIdSelecionada:', categoriaIdSelecionada);
            
            if (!categoriaIdSelecionada) {
                alert('Selecione uma categoria');
                return;
            }

            const descricao = document.getElementById('descricaoGasto').value;
            const valor = parseFloat(document.getElementById('valorGasto').value);
            const data = document.getElementById('dataGasto').value;
            const hora = document.getElementById('horaGasto').value;

            if (!valor || !data) {
                alert('Preencha todos os campos obrigatórios');
                return;
            }

            try {
                const response = await fetch('api/gastos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'criar',
                        categoria_id: categoriaIdSelecionada,
                        descricao,
                        valor,
                        data_gasto: data,
                        hora_gasto: hora
                    })
                });

                const data_response = await response.json();
                if (data_response.success) {
                    document.getElementById('descricaoGasto').value = '';
                    document.getElementById('valorGasto').value = '';
                    document.getElementById('horaGasto').value = new Date().toTimeString().slice(0, 5);
                    carregarGastos();
                    atualizarGrafico();
                    
                    // Se o overlay foi desativado, reativar para continuar o tutorial
                    if (overlayDesativadoTemporariamente) {
                        console.log('Primeiro gasto criado! Reativando tutorial...');
                        overlayDesativadoTemporariamente = false;
                        document.getElementById('tutorialOverlay').classList.add('active');
                        mostrarEtapaTutorial();
                    }
                } else {
                    alert(data_response.message);
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        // Carregar gastos
        async function carregarGastos() {
            try {
                const response = await fetch(`api/gastos.php?filtro=${filtroAtual}`);
                const data = await response.json();

                if (data.success) {
                    const container = document.getElementById('gastosList');
                    if (data.gastos.length === 0) {
                        container.innerHTML = '<div class="empty-state"><p>Nenhum gasto neste período</p></div>';
                        return;
                    }

                    container.innerHTML = data.gastos.map(gasto => `
                        <div class="gasto-item">
                            <div class="gasto-info">
                                <div class="gasto-categoria" style="color: ${gasto.cor_categoria}">●${gasto.categoria}</div>
                                <div class="gasto-descricao">${gasto.descricao} - ${gasto.data_gasto}</div>
                            </div>
                            <div class="gasto-valor">-R$ ${parseFloat(gasto.valor).toFixed(2).replace('.', ',')}</div>
                        </div>
                    `).join('');
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        // Alterar filtro
        function alterarFiltro(filtro) {
            filtroAtual = filtro;
            document.querySelectorAll('.filtro-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            carregarGastos();
            atualizarGrafico();
        }

        // Atualizar gráfico
        async function atualizarGrafico() {
            try {
                const response = await fetch(`api/grafico.php?filtro=${filtroAtual}`);
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
            
            // Mostrar mensagem de conclusão
            alert('✅ Tutorial concluído! Você já conhece todas as funcionalidades do sistema.');
            
            // Notificar ao servidor que o tutorial foi visto
            fetch('api/configuracoes.php', {
                method: 'POST',
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

        // Logout
        function logout() {
            if (confirm('Tem certeza que deseja sair?')) {
                fetch('api/logout.php', { method: 'POST' })
                    .then(() => window.location.href = 'login.php');
            }
        }

        // Deletar categoria
        async function deletarCategoria(id) {
            if (confirm('Tem certeza?')) {
                try {
                    const response = await fetch('api/categorias.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'deletar', id })
                    });
                    const data = await response.json();
                    if (data.success) {
                        carregarCategorias();
                    }
                } catch (error) {
                    console.error('Erro:', error);
                }
            }
        }
    </script>
</body>
</html>
