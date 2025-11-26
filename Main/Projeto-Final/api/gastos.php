<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Listar gastos com filtro.
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

    $sql = "SELECT g.*, c.nome as categoria, c.cor_hex as cor_categoria
            FROM gastos g
            JOIN categorias c ON g.categoria_id = c.id
            WHERE g.usuario_id = ? AND g.data_gasto BETWEEN ? AND ?
            ORDER BY g.data_gasto DESC, g.hora_gasto DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();

    $gastos = [];
    while ($row = $result->fetch_assoc()) {
        $gastos[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'gastos' => $gastos
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    if ($action === 'criar') {
        $categoria_id = intval($data['categoria_id']);
        $descricao = sanitizar($data['descricao'] ?? '');
        $valor = floatval($data['valor']);
        $data_gasto = sanitizar($data['data_gasto']);
        $hora_gasto = sanitizar($data['hora_gasto'] ?? '00:00');

        // Validar categoria pertence ao usuário
        $sql = "SELECT id FROM categorias WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $categoria_id, $usuario_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Categoria inválida']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        // Inserir gasto
        $sql = "INSERT INTO gastos (usuario_id, categoria_id, descricao, valor, data_gasto, hora_gasto) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisiss", $usuario_id, $categoria_id, $descricao, $valor, $data_gasto, $hora_gasto);
        $stmt->execute();
        $gasto_id = $stmt->insert_id;
        $stmt->close();

        echo json_encode([
            'success' => true,
            'id' => $gasto_id,
            'message' => 'Gasto registrado com sucesso'
        ]);
    } else if ($action === 'deletar') {
        $id = intval($data['id']);

        // Verificar pertencimento
        $sql = "SELECT usuario_id FROM gastos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row || $row['usuario_id'] != $usuario_id) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
        }

        // Deletar
        $sql = "DELETE FROM gastos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Gasto deletado']);
    }
}
?>

