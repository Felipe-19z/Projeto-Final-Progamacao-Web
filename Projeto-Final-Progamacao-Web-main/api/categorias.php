<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Listar categorias e somar gastos por categoria no período (opcional: filtro=dia|semana|mes|ano)
    $filtro = $_GET['filtro'] ?? 'mes';
    $yearParam = isset($_GET['year']) ? intval($_GET['year']) : null;
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
            $yearToUse = $yearParam ?? intval(date('Y'));
            $data_inicio = sprintf('%04d-01-01', $yearToUse);
            $data_fim = sprintf('%04d-12-31', $yearToUse);
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
    $totaisPorCategoria = [];
    while ($row = $result->fetch_assoc()) {
        // garantir total numérico
        $row['total'] = floatval($row['total']);
        $categorias[] = $row;
        $totaisPorCategoria[$row['id']] = $row['total'];
    }
    $stmt->close();

    // Incluir gastos fixos no período (se tabela existe)
    $checkFix = $conn->query("SHOW TABLES LIKE 'gastos_fixos'");
    if ($checkFix && $checkFix->num_rows > 0) {
        $sqlFix = "SELECT * FROM gastos_fixos WHERE usuario_id = ? AND active = 1";
        $stmtFix = $conn->prepare($sqlFix);
        if ($stmtFix) {
            $stmtFix->bind_param('i', $usuario_id);
            $stmtFix->execute();
            $resFix = $stmtFix->get_result();
            
            while ($fx = $resFix->fetch_assoc()) {
                $fixStart = new DateTime($fx['start_date']);
                $periodStart = new DateTime($data_inicio);
                $periodEnd = new DateTime($data_fim);
                
                // Gerar ocorrências do gasto fixo no período solicitado
                $occurrences = [];
                
                if ($fx['periodicidade'] === 'mes') {
                    $day = intval($fixStart->format('j'));
                    $cursor = clone $fixStart;
                    if ($cursor < $periodStart) {
                        $cursor->setDate((int)$periodStart->format('Y'), (int)$periodStart->format('m'), min($day, (int)$periodStart->format('t')));
                    }
                    while ($cursor <= $periodEnd) {
                        $d = $cursor->format('Y-m-d');
                        if ($d >= $data_inicio && $d <= $data_fim) {
                            $occurrences[] = $d;
                        }
                        $cursor->modify('+1 month');
                        $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), min($day, (int)$cursor->format('t')));
                    }
                } else if ($fx['periodicidade'] === 'semana') {
                    $startWeekday = intval($fixStart->format('w'));
                    $cursor = clone $fixStart;
                    if ($cursor < $periodStart) {
                        $cursor = clone $periodStart;
                        while ((int)$cursor->format('w') !== $startWeekday && $cursor <= $periodEnd) {
                            $cursor->modify('+1 day');
                        }
                    } else {
                        while ((int)$cursor->format('w') !== $startWeekday && $cursor <= $periodEnd) {
                            $cursor->modify('+1 day');
                        }
                    }
                    while ($cursor <= $periodEnd) {
                        $d = $cursor->format('Y-m-d');
                        if ($d >= $data_inicio && $d <= $data_fim) {
                            $occurrences[] = $d;
                        }
                        $cursor->modify('+7 days');
                    }
                } else if ($fx['periodicidade'] === 'ano') {
                    $parts = explode('-', $fixStart->format('m-d'));
                    $m = intval($parts[0]);
                    $d = intval($parts[1]);
                    $cursor = clone $fixStart;
                    $cursor->setDate((int)$cursor->format('Y'), $m, $d);
                    if ($cursor < $periodStart) {
                        $cursor->setDate((int)$periodStart->format('Y'), $m, $d);
                    }
                    while ($cursor <= $periodEnd) {
                        $dd = $cursor->format('Y-m-d');
                        if ($dd >= $data_inicio && $dd <= $data_fim) {
                            $occurrences[] = $dd;
                        }
                        $cursor->modify('+1 year');
                    }
                }
                
                // Para cada ocorrência que não tem um gasto real registrado, adicionar ao total da categoria
                $catId = $fx['categoria_id'];
                foreach ($occurrences as $occ_date) {
                    $chk = $conn->prepare("SELECT id FROM gastos WHERE usuario_id = ? AND categoria_id = ? AND data_gasto = ? LIMIT 1");
                    if ($chk) {
                        $chk->bind_param('iis', $usuario_id, $catId, $occ_date);
                        $chk->execute();
                        $rchk = $chk->get_result();
                        if ($rchk && $rchk->num_rows === 0) {
                            if (!isset($totaisPorCategoria[$catId])) {
                                $totaisPorCategoria[$catId] = 0;
                            }
                            $totaisPorCategoria[$catId] += floatval($fx['valor']);
                        }
                        $chk->close();
                    }
                }
            }
            $stmtFix->close();
        }
    }
    
    // Atualizar os totais nas categorias com os gastos fixos inclusos
    foreach ($categorias as &$cat) {
        $cat['total'] = $totaisPorCategoria[$cat['id']] ?? 0;
    }

    // Debug: gravar em arquivo para inspeção (temporário)
    @file_put_contents(__DIR__ . '/../gastos_categorias_debug.json', json_encode([
        'timestamp' => date('c'),
        'usuario_id' => $usuario_id,
        'filtro' => $filtro,
        'period' => ['inicio' => $data_inicio, 'fim' => $data_fim],
        'categorias' => $categorias,
        'totaisPorCategoria' => $totaisPorCategoria
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

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

