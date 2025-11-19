<?php
// index.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Controle de Gastos - Bem-vindo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #f9fafb;
            padding: 2rem 1rem 3rem;
        }

        .container {
            max-width: 1100px;
            width: 100%;
            position: relative;
        }

        /* Botão de login no canto superior esquerdo */
        .login-btn {
            position: absolute;
            top: 0;
            left: 0;
            padding: 0.5rem 1.3rem;
            border-radius: 999px;
            border: 1px solid rgba(248, 250, 252, 0.4);
            background: rgba(15, 23, 42, 0.7);
            color: #f9fafb;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            backdrop-filter: blur(8px);
            transition: transform 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
        }

        .login-btn:hover {
            background: #22c55e;
            color: #0f172a;
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.3);
            transform: translateY(-1px);
        }

        .card {
            margin-top: 3.2rem;
            background: rgba(15, 23, 42, 0.9);
            border-radius: 1.5rem;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.4);
            display: grid;
            gap: 2rem;
        }

        @media (min-width: 768px) {
            .card {
                grid-template-columns: 1.4fr 1fr;
                padding: 3rem;
            }
        }

        .title {
            font-size: 2.2rem;
            line-height: 1.2;
            margin-bottom: 0.8rem;
        }

        .title span {
            color: #22c55e;
        }

        .subtitle {
            color: #cbd5f5;
            margin-bottom: 1.8rem;
        }

        .bullets {
            list-style: none;
            display: grid;
            gap: 0.6rem;
            margin-bottom: 2rem;
        }

        .bullets li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            color: #e5e7eb;
        }

        .bullet-dot {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 999px;
            background: #22c55e;
        }

        .cta-area {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            align-items: center;
        }

        .primary-btn {
            padding: 0.7rem 1.5rem;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            background: #22c55e;
            color: #0f172a;
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.3);
            transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
        }

        .primary-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
            box-shadow: 0 14px 30px rgba(34, 197, 94, 0.4);
        }

        .cta-text {
            font-size: 0.85rem;
            color: #9ca3af;
        }

        .highlight-box {
            border-radius: 1.2rem;
            border: 1px dashed rgba(148, 163, 184, 0.7);
            padding: 1.4rem 1.2rem;
            background: radial-gradient(circle at top, rgba(34, 197, 94, 0.22), transparent 55%);
            font-size: 0.9rem;
            color: #e5e7eb;
        }

        .highlight-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .highlight-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #a5b4fc;
            margin-bottom: 0.4rem;
        }

        .mini-line {
            width: 40px;
            height: 2px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.9);
            margin-bottom: 0.6rem;
        }

        /* SEÇÃO DE VÍDEOS */
        .videos-section {
            margin-top: 2rem;
            background: rgba(15, 23, 42, 0.85);
            border-radius: 1.5rem;
            padding: 2rem 1.5rem 2.3rem;
            border: 1px solid rgba(148, 163, 184, 0.4);
        }

        .videos-title {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
        }

        .videos-subtitle {
            font-size: 0.9rem;
            color: #9ca3af;
            margin-bottom: 1.5rem;
        }

        .video-grid {
            display: grid;
            gap: 1.5rem;
        }

        @media (min-width: 900px) {
            .video-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .video-card {
            background: rgba(15, 23, 42, 0.9);
            border-radius: 1rem;
            padding: 1rem;
            border: 1px solid rgba(55, 65, 81, 0.9);
        }

        .video-card h3 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .video-card p {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-bottom: 0.7rem;
        }

        .video-card video {
            width: 100%;
            border-radius: 0.7rem;
            outline: none;
        }
    </style>
</head>
<body>
    <div class="container">

        <!-- Botão de Login canto superior esquerdo -->
        <a href="login.php" class="login-btn">Login</a>

        <!-- CARD DE INTRODUÇÃO -->
        <div class="card">
            <div>
                <h1 class="title">
                    Controle seus <span>gastos</span><br>
                    de forma simples e visual.
                </h1>
                <p class="subtitle">
                    Acompanhe entradas, saídas, categorias e metas em um único lugar.
                    Veja para onde o seu dinheiro está indo e tome decisões melhores.
                </p>

                <ul class="bullets">
                    <li>
                        <div class="bullet-dot"></div>
                        Relatórios mensais claros e fáceis de entender.
                    </li>
                    <li>
                        <div class="bullet-dot"></div>
                        Cadastro de despesas por categoria (contas, lazer, estudos etc.).
                    </li>
                    <li>
                        <div class="bullet-dot"></div>
                        Metas de economia com acompanhamento em tempo real.
                    </li>
                </ul>

                <div class="cta-area">
                    <button class="primary-btn" onclick="window.location.href='login.php'">
                        Começar agora
                    </button>
                    <span class="cta-text">
                        Acesse com seu login e já veja o resumo do seu mês.
                    </span>
                </div>
            </div>

            <div class="highlight-box">
                <div class="highlight-label">Resumo inteligente</div>
                <div class="highlight-value">+35%</div>
                <div class="mini-line"></div>
                <p>
                    Usuários que controlam seus gastos aqui economizam em média
                    <strong>35% a mais</strong> em até 3 meses, apenas entendendo melhor seus hábitos.
                </p>
            </div>
        </div>

        <!-- SEÇÃO DE VÍDEOS POR TIPO DE EMPRESA -->
        <div class="videos-section">
            <h2 class="videos-title">Vídeos por segmento de empresas</h2>
            <p class="videos-subtitle">
                CONTROLE DE GASTOS DAS GRANDE EMPRESAS
            </p>

            <div class="video-grid">
                <!-- BEBIDAS -->
                <div class="video-card">
                    <h3>Empresas de Bebidas</h3>
                    <p>
                        
                    </p>
                    <video controls poster="imagens/thumb-bebidas.jpg">
                        <source src="COCA.mp4" type="video/mp4">
                        Seu navegador não suporta o elemento de vídeo.
                    </video>
                </div>

                <!-- ALIMENTOS -->
                <div class="video-card">
                    <h3>Empresas de Alimentos</h3>
                    <p>
                        
                    </p>
                    <video controls poster="imagens/thumb-alimentos.jpg">
                        <source src="mequi.mp4" type="video/mp4">
                        Seu navegador não suporta o elemento de vídeo.
                    </video>
                </div>

                <!-- GAMES -->
                <div class="video-card">
                    <h3>Empresas de Games</h3>
                    <p>
                        
                    </p>
                    <video controls poster="imagens/thumb-games.jpg">
                        <source src="Steam.mp4" type="video/mp4">
                        Seu navegador não suporta o elemento de vídeo.
                    </video>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
