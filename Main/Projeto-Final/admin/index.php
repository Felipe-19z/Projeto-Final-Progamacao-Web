<?php
require_once '../config.php';
verificar_login();

// Verificar se é admin (por enquanto apenas usuário 1)
$usuario_id = $_SESSION['usuario_id'];
if ($usuario_id !== 1) {
    header("Location: ../index.php");
    exit;
}

$usuario = obter_usuario($usuario_id);

// Contar usuários
$sql = "SELECT COUNT(*) as total FROM usuarios";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_usuarios = $row['total'];

// Contar gastos
$sql = "SELECT COUNT(*) as total FROM gastos";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_gastos = $row['total'];

// Usuários com mais acessos
$sql = "SELECT u.id, u.nome, u.email, COUNT(l.id) as acessos, MAX(l.data_acesso) as ultimo_acesso
        FROM usuarios u
        LEFT JOIN logs_acesso l ON u.id = l.usuario_id
        GROUP BY u.id
        ORDER BY acessos DESC
        LIMIT 5";
$result = $conn->query($sql);
$top_usuarios = $result->fetch_all(MYSQLI_ASSOC);

// Mensagens de ajuda não respondidas
$sql = "SELECT COUNT(*) as total FROM mensagens_ajuda WHERE status = 'pendente'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$mensagens_pendentes = $row['total'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Controle de Gastos</title>
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
            position: sticky;
            top: 0;
            z-index: 50;
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
            gap: 20px;
            align-items: center;
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
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
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
            min-height: calc(100vh - 60px);
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 30px;
            color: #333;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            animation: slideInUp 0.5s ease-out;
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

        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead th {
            background: #f9f9f9;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e0e0e0;
        }

        .table tbody td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        .table tbody tr:hover {
            background: #f9f9f9;
        }

        .user-name {
            font-weight: 600;
            color: #333;
        }

        .user-email {
            font-size: 12px;
            color: #999;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: #efe;
            color: #3c3;
        }

        .badge-warning {
            background: #ffe;
            color: #cc3;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-edit {
            background: #4ecdc4;
            color: white;
        }

        .btn-edit:hover {
            background: #45b7d1;
        }

        .btn-delete {
            background: #ff6b6b;
            color: white;
        }

        .btn-delete:hover {
            background: #ff5252;
        }

        .btn-view {
            background: #667eea;
            color: white;
        }

        .btn-view:hover {
            background: #764ba2;
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
                padding: 10px;
            }

            .sidebar-menu a {
                padding: 12px 5px;
                font-size: 0;
            }

            .sidebar-menu a::before {
                content: attr(data-icon);
                font-size: 18px;
            }

            .main-content {
                margin-left: 80px;
            }

            .page-title {
                font-size: 24px;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table {
                font-size: 12px;
            }

            .table thead th,
            .table tbody td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">
            👨‍💼 Painel Admin
        </div>
        <div class="navbar-right">
            <span><?php echo htmlspecialchars($usuario['nome']); ?></span>
            <button class="logout-btn" onclick="logout()">Sair</button>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="index.php" class="active" data-icon="📊">Dashboard</a></li>
            <li><a href="listar-usuarios.php" data-icon="👥">Usuários</a></li>
            <li><a href="criar-usuarios.php" data-icon="➕">Novo Usuário</a></li>
            <li><a href="mensagens.php" data-icon="💬">Mensagens</a></li>
            <li><a href="auditoria.php" data-icon="📋">Auditoria</a></li>
        </ul>
    </div>

    <!-- Conteúdo Principal -->
    <div class="main-content">
        <h1 class="page-title">Dashboard de Administração</h1>

        <!-- Cards de Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Total de Usuários</div>
                <div class="stat-value"><?php echo $total_usuarios; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">💸</div>
                <div class="stat-label">Total de Gastos</div>
                <div class="stat-value"><?php echo $total_gastos; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-label">Mensagens Pendentes</div>
                <div class="stat-value" style="color: #ff6b6b;"><?php echo $mensagens_pendentes; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Sistema</div>
                <div class="stat-value" style="color: #4ecdc4;">Online</div>
            </div>
        </div>

        <!-- Usuários com Mais Acessos -->
        <div class="card">
            <h2 class="card-title">👤 Usuários com Mais Acessos</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Acessos</th>
                        <th>Último Acesso</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_usuarios as $user): ?>
                    <tr>
                        <td>
                            <div class="user-name"><?php echo htmlspecialchars($user['nome']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="badge badge-success"><?php echo $user['acessos']; ?> acessos</span></td>
                        <td><?php echo $user['ultimo_acesso'] ? formatar_data($user['ultimo_acesso']) . ' ' . formatar_hora($user['ultimo_acesso']) : 'Nunca'; ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="ler-usuarios.php?id=<?php echo $user['id']; ?>" class="btn-small btn-view">Ver</a>
                                <a href="deletar-usuarios.php?id=<?php echo $user['id']; ?>" class="btn-small btn-delete">Deletar</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function logout() {
            if (confirm('Tem certeza que deseja sair?')) {
                fetch('../api/logout.php', { method: 'POST' })
                    .then(() => window.location.href = '../login.php');
            }
        }
    </script>
</body>
</html>
