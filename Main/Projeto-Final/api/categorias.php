<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Listar categorias
    $sql = "SELECT id, nome, cor_hex FROM categorias WHERE usuario_id = ? ORDER BY data_criacao DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'categorias' => $categorias
    ]);
    exit;
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
            echo json_encode(['success' => false, 'message' => 'Esta categoria já existe']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        // Inserir categoria
        $sql = "INSERT INTO categorias (usuario_id, nome, cor_hex) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $usuario_id, $nome, $cor);
        $stmt->execute();
        $categoria_id = $stmt->insert_id;
        $stmt->close();

        echo json_encode([
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
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            exit;
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

        echo json_encode(['success' => true, 'message' => 'Categoria deletada']);
    }
}
?>
