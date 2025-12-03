<?php
require_once '../config.php';
verificar_login();
// Apenas admin
verificar_admin();
$usuario_id = $_SESSION['usuario_id'];

// Buscar auditoria de exclusões
$sql = "SELECT * FROM auditoria_exclusao ORDER BY data_exclusao DESC LIMIT 100";
$result = $conn->query($sql);
$exclusoes = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria - Painel Admin</title>
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

        .motivo {
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
            border-left: 3px solid #ff6b6b;
            font-size: 12px;
            color: #666;
            max-width: 300px;
            white-space: normal;
            word-wrap: break-word;
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

            .table {
                font-size: 12px;
            }

            .table thead th,
            .table tbody td {
                padding: 8px;
            }

            .motivo {
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">📋 Auditoria de Exclusões</div>
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
            <li><a href="mensagens.php">💬 Mensagens</a></li>
            <li><a href="auditoria.php" class="active">📋 Auditoria</a></li>
        </ul>
    </div>

    <!-- Conteúdo Principal -->
    <div class="main-content">
        <h1 class="page-title">Auditoria de Exclusões</h1>

        <div class="card">
            <?php if (empty($exclusoes)): ?>
            <div class="empty-state">
                <p>Nenhuma exclusão registrada</p>
            </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuário Deletado</th>
                        <th>Motivo da Exclusão</th>
                        <th>Data de Exclusão</th>
                        <th>Excluído Por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exclusoes as $item): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($item['nome_usuario']); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($item['email_usuario']); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="motivo"><?php echo htmlspecialchars($item['motivo_exclusao']); ?></div>
                        </td>
                        <td><?php echo formatar_data($item['data_exclusao']) . ' ' . formatar_hora($item['data_exclusao']); ?></td>
                        <td><?php echo $item['excluido_por'] ? 'Admin ID: ' . $item['excluido_por'] : 'Sistema'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
