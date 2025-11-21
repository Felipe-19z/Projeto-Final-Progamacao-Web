<?php
require_once __DIR__ . '/auth.php';

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: listar-usuarios.php");
    exit;
}

// Buscar usuário
$usuario = obter_usuario($id);
if (!$usuario) {
    header("Location: listar-usuarios.php");
    exit;
}

// Buscar estatísticas do usuário
$sql = "SELECT COUNT(*) as total_gastos, COALESCE(SUM(valor), 0) as total_gasto
        FROM gastos WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result()->fetch_assoc();
$stmt->close();

$sql = "SELECT COUNT(*) as total_acessos, MAX(data_acesso) as ultimo_acesso
        FROM logs_acesso WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$acessos = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Últimos gastos
$sql = "SELECT g.*, c.nome as categoria FROM gastos g
        JOIN categorias c ON g.categoria_id = c.id
        WHERE g.usuario_id = ?
        ORDER BY g.data_gasto DESC
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$gastos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Usuário - Painel Admin</title>
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

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 36px;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .user-email {
            font-size: 14px;
            color: #999;
            margin-bottom: 10px;
        }

        .badges {
            display: flex;
            gap: 10px;
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

        .badge-ativo {
            background: #efe;
            color: #3c3;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 18px;
            font-weight: 700;
            color: #333;
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

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-delete {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: #ff5252;
            transform: translateY(-2px);
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

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        @media (max-width: 768px) {
            .user-header {
                flex-direction: column;
                text-align: center;
            }

            .user-avatar {
                margin: 0 auto;
            }

            .info-grid {
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
        <div class="navbar-brand">👤 Detalhes do Usuário</div>
        <div class="navbar-right">
            <a href="listar-usuarios.php" class="nav-link">← Voltar</a>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="container">
        <!-- Informações Básicas -->
        <div class="card">
            <div class="user-header">
                <div class="user-avatar"><?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($usuario['email']); ?></div>
                    <div class="badges">
                        <span class="badge badge-ativo"><?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?></span>
                    </div>
                </div>
                <div class="action-buttons">
                    <button class="btn-delete" onclick="deletarUsuario(<?php echo $usuario['id']; ?>)">Deletar Usuário</button>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Renda Mensal</div>
                <div class="info-value"><?php echo formatar_moeda($usuario['renda_mensal']); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Total de Gastos</div>
                <div class="info-value"><?php echo $resultado['total_gastos']; ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Valor Total Gasto</div>
                <div class="info-value"><?php echo formatar_moeda($resultado['total_gasto']); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Total de Acessos</div>
                <div class="info-value"><?php echo $acessos['total_acessos']; ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Último Acesso</div>
                <div class="info-value"><?php echo $acessos['ultimo_acesso'] ? formatar_data($acessos['ultimo_acesso']) : 'Nunca'; ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Data de Cadastro</div>
                <div class="info-value"><?php echo formatar_data($usuario['data_criacao']); ?></div>
            </div>
        </div>

        <!-- Últimos Gastos -->
        <div class="card">
            <h2 class="card-title">📋 Últimos Gastos</h2>

            <?php if (empty($gastos)): ?>
            <div class="empty-state">
                <p>Nenhum gasto registrado</p>
            </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gastos as $gasto): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($gasto['categoria']); ?></td>
                        <td><?php echo htmlspecialchars($gasto['descricao']); ?></td>
                        <td><?php echo formatar_moeda($gasto['valor']); ?></td>
                        <td><?php echo formatar_data($gasto['data_gasto']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function deletarUsuario(id) {
            if (confirm('Tem certeza que deseja deletar este usuário? Esta ação não pode ser desfeita.')) {
                window.location.href = 'deletar-usuarios.php?id=' + id;
            }
        }
    </script>
</body>
</html>
