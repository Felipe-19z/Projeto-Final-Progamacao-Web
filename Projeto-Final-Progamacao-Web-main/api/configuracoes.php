<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Buscar configurações do usuário
    $sql = "SELECT * FROM configuracoes_usuario WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $config = $result->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'configuracoes' => $config
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    if ($action === 'tutorial_visto') {
        // Marcar tutorial como visto
        $sql = "UPDATE configuracoes_usuario SET mostrar_tutorial = FALSE WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Tutorial marcado como visto'
        ]);
        exit;
    }

    if ($action === 'atualizar') {
        $cor_fundo = $data['cor_fundo'] ?? null;
        $cor_gastos = $data['cor_gastos'] ?? null;
        $cor_grafico_1 = $data['cor_grafico_1'] ?? null;

        if ($cor_fundo) {
            $sql = "UPDATE configuracoes_usuario SET cor_fundo = ? WHERE usuario_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $cor_fundo, $usuario_id);
            $stmt->execute();
            $stmt->close();
        }

        if ($cor_gastos) {
            $sql = "UPDATE configuracoes_usuario SET cor_gastos = ? WHERE usuario_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $cor_gastos, $usuario_id);
            $stmt->execute();
            $stmt->close();
        }

        if ($cor_grafico_1) {
            $sql = "UPDATE configuracoes_usuario SET cor_grafico_1 = ? WHERE usuario_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $cor_grafico_1, $usuario_id);
            $stmt->execute();
            $stmt->close();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Configurações atualizadas'
        ]);
        exit;
    }

    if ($action === 'reverter') {
        $histId = intval($data['id'] ?? 0);
        if ($histId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit;
        }

        // Buscar histórico
        $sql = "SELECT * FROM configuracoes_usuario_historico WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $histId, $usuario_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Registro de histórico não encontrado']);
            $stmt->close();
            exit;
        }
        $row = $res->fetch_assoc();
        $stmt->close();

        // Aplicar configuração
        $sqlUpd = "UPDATE configuracoes_usuario SET cor_fundo = ?, cor_gastos = ?, cor_grafico_1 = ?, cor_grafico_2 = ?, cor_grafico_3 = ? WHERE usuario_id = ?";
        $stmt2 = $conn->prepare($sqlUpd);
        $stmt2->bind_param("sssssi", $row['cor_fundo'], $row['cor_gastos'], $row['cor_grafico_1'], $row['cor_grafico_2'], $row['cor_grafico_3'], $usuario_id);
        $stmt2->execute();
        $stmt2->close();

        // Atualizar renda do usuário se presente no histórico
        if ($row['renda_mensal'] !== null) {
            $sqlR = "UPDATE usuarios SET renda_mensal = ? WHERE id = ?";
            $stmt3 = $conn->prepare($sqlR);
            $stmt3->bind_param("di", $row['renda_mensal'], $usuario_id);
            $stmt3->execute();
            $stmt3->close();
        }

        echo json_encode(['success' => true, 'message' => 'Configurações revertidas com sucesso']);
        exit;
    }

    echo json_encode([
        'success' => false,
        'message' => 'Ação não reconhecida'
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método não permitido']);

