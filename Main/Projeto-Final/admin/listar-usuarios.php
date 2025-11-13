<?php
require_once '../config.php';
verificar_login();

// Apenas admin
$usuario_id = $_SESSION['usuario_id'];
if ($usuario_id !== 1) {
    header("Location: ../index.php");
    exit;
}

// Listar todos os usuários
$sql = "SELECT u.id, u.nome, u.email, u.renda_mensal, u.ativo, u.data_criacao, 
               COUNT(l.id) as total_acessos, MAX(l.data_acesso) as ultimo_acesso
        FROM usuarios u
        LEFT JOIN logs_acesso l ON u.id = l.usuario_id
        GROUP BY u.id
        ORDER BY u.data_criacao DESC";
$result = $conn->query($sql);
$usuarios = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Painel Admin</title>
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        .btn-novo {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-novo:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
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
            width: fit-content;
        }

        .badge-ativo {
            background: #efe;
            color: #3c3;
        }

        .badge-inativo {
            background: #fee;
            color: #c33;
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

        .btn-view {
            background: #4ecdc4;
            color: white;
        }

        .btn-view:hover {
            background: #45b7d1;
            transform: translateY(-1px);
        }

        .btn-edit {
            background: #667eea;
            color: white;
        }

        .btn-edit:hover {
            background: #764ba2;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #ff6b6b;
            color: white;
        }

        .btn-delete:hover {
            background: #ff5252;
            transform: translateY(-1px);
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

            .page-header {
                flex-direction: column;
                gap: 15px;
            }

            .table {
                font-size: 12px;
            }

            .table thead th,
            .table tbody td {
                padding: 8px;
            }

            .user-info {
                gap: 8px;
            }

            .user-avatar {
                width: 30px;
                height: 30px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">👥 Gerenciar Usuários</div>
        <div class="navbar-right">
            <a href="index.php" class="nav-link">← Dashboard</a>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="index.php">📊 Dashboard</a></li>
            <li><a href="listar-usuarios.php" class="active">👥 Usuários</a></li>
            <li><a href="criar-usuarios.php">➕ Novo Usuário</a></li>
            <li><a href="mensagens.php">💬 Mensagens</a></li>
            <li><a href="auditoria.php">📋 Auditoria</a></li>
        </ul>
    </div>

    <!-- Conteúdo Principal -->
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Usuários Cadastrados</h1>
            <a href="criar-usuarios.php" class="btn-novo">➕ Novo Usuário</a>
        </div>

        <div class="card">
            <?php if (empty($usuarios)): ?>
            <div class="empty-state">
                <p>Nenhum usuário cadastrado ainda</p>
            </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Renda Mensal</th>
                        <th>Status</th>
                        <th>Acessos</th>
                        <th>Último Acesso</th>
                        <th>Data Criação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $user): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar"><?php echo strtoupper(substr($user['nome'], 0, 1)); ?></div>
                                <div class="user-details">
                                    <div class="user-name"><?php echo htmlspecialchars($user['nome']); ?></div>
                                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo formatar_moeda($user['renda_mensal']); ?></td>
                        <td>
                            <span class="badge <?php echo $user['ativo'] ? 'badge-ativo' : 'badge-inativo'; ?>">
                                <?php echo $user['ativo'] ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td><?php echo $user['total_acessos']; ?></td>
                        <td><?php echo $user['ultimo_acesso'] ? formatar_data($user['ultimo_acesso']) . ' ' . formatar_hora($user['ultimo_acesso']) : 'Nunca'; ?></td>
                        <td><?php echo formatar_data($user['data_criacao']); ?></td>
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
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
