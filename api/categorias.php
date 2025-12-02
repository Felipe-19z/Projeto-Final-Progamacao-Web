<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Listar categorias e somar gastos por categoria no período (opcional: filtro=dia|semana|mes|ano)
    $filtro = $_GET['filtro'] ?? 'mes';
    $data_inicio = date('Y-m-d');
    $data_fim = date('Y-m-d');
    switch ($filtro) {
        case 'dia':
            $data_inicio = $data_fim = date('Y-m-d');
            break;
        case 'semana':
            $data_inicio = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'mes':
            $data_inicio = date('Y-m-01');
            $data_fim = date('Y-m-d', strtotime('last day of this month'));
            break;
        case 'ano':
            $data_inicio = date('Y-01-01');
            $data_fim = date('Y-12-31');
            break;
    }

    // Seleciona categorias e soma os gastos do período para cada categoria
    $sql = "SELECT c.id, c.nome, c.cor_hex, COALESCE(SUM(g.valor), 0) AS total
            FROM categorias c
            LEFT JOIN gastos g ON g.categoria_id = c.id AND g.usuario_id = ? AND g.data_gasto BETWEEN ? AND ?
            WHERE c.usuario_id = ?
            GROUP BY c.id
            ORDER BY c.data_criacao DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $usuario_id, $data_inicio, $data_fim, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        // garantir total numérico
        $row['total'] = floatval($row['total']);
        $categorias[] = $row;
    }
    $stmt->close();

    api_json([
        'success' => true,
        'categorias' => $categorias,
        'filtro' => $filtro,
        'period' => [ 'inicio' => $data_inicio, 'fim' => $data_fim ]
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    if ($action === 'criar') {
        $nome = sanitizar($data['nome']);
        $cor = $data['cor'] ?? '#FF0000';

        // Verificar duplicata
        $sql = "SELECT id FROM categorias WHERE usuario_id = ? AND nome = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $usuario_id, $nome);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            api_json(['success' => false, 'message' => 'Esta categoria já existe']);
        }
        $stmt->close();

        // Inserir categoria
        $sql = "INSERT INTO categorias (usuario_id, nome, cor_hex) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $usuario_id, $nome, $cor);
        $stmt->execute();
        $categoria_id = $stmt->insert_id;
        $stmt->close();

        api_json([
            'success' => true,
            'id' => $categoria_id,
            'message' => 'Categoria criada com sucesso'
        ]);
    } else if ($action === 'deletar') {
        $id = intval($data['id']);

        // Verificar pertencimento
        $sql = "SELECT usuario_id FROM categorias WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row || $row['usuario_id'] != $usuario_id) {
            api_json(['success' => false, 'message' => 'Sem permissão']);
        }

        // Deletar gastos associados (opcional: pode arquivar em vez de deletar)
        $sql = "DELETE FROM gastos WHERE categoria_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Deletar categoria
        $sql = "DELETE FROM categorias WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        api_json(['success' => true, 'message' => 'Categoria deletada']);
    }
}

