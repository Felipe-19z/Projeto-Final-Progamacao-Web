<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';
verificar_login();

$usuario_id = $_SESSION['usuario_id'];

// Helper to return clean JSON and discard any accidental HTML/warnings emitted before
function api_json($data) {
    // clear all output buffers to avoid mixing HTML/warnings with JSON
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // If requested, return the raw fixed-expense definitions for management UI
    if (isset($_GET['listar_fixos'])) {
        $checkFix = $conn->query("SHOW TABLES LIKE 'gastos_fixos'");
        if ($checkFix && $checkFix->num_rows > 0) {
            $sqlFix = "SELECT * FROM gastos_fixos WHERE usuario_id = ?";
            $stmtFix = $conn->prepare($sqlFix);
            if ($stmtFix) {
                $stmtFix->bind_param('i', $usuario_id);
                $stmtFix->execute();
                $resFix = $stmtFix->get_result();
                $fixos = [];
                while ($fx = $resFix->fetch_assoc()) {
                    $fixos[] = $fx;
                }
                $stmtFix->close();
                api_json(['success' => true, 'fixos' => $fixos]);
            }
        }
        api_json(['success' => true, 'fixos' => []]);
    }
    // Listar gastos com filtro
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

        // Opcional: filtrar por categoria se fornecido via GET
        $categoria_filter = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : 0;

        if ($categoria_filter > 0) {
        $sql = "SELECT g.*, c.nome as categoria, c.cor_hex as cor_categoria
            FROM gastos g
            JOIN categorias c ON g.categoria_id = c.id
            WHERE g.usuario_id = ? AND g.categoria_id = ? AND g.data_gasto BETWEEN ? AND ?
            ORDER BY g.data_gasto DESC, g.hora_gasto DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $usuario_id, $categoria_filter, $data_inicio, $data_fim);
        } else {
        $sql = "SELECT g.*, c.nome as categoria, c.cor_hex as cor_categoria
            FROM gastos g
            JOIN categorias c ON g.categoria_id = c.id
            WHERE g.usuario_id = ? AND g.data_gasto BETWEEN ? AND ?
            ORDER BY g.data_gasto DESC, g.hora_gasto DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
        }
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar se a tabela gasto_historico existe (evita erro se não existir)
    $has_history = false;
    $check = $conn->query("SHOW TABLES LIKE 'gasto_historico'");
    if ($check && $check->num_rows > 0) {
        $has_history = true;
    }

    $gastos = [];
    while ($row = $result->fetch_assoc()) {
        // Fetch history entries for this gasto (se a tabela existir)
        $row['historico'] = [];
        if ($has_history) {
            $gh_sql = "SELECT descricao, valor, data_registro FROM gasto_historico WHERE gasto_id = ? ORDER BY data_registro ASC";
            $gh_stmt = $conn->prepare($gh_sql);
            if ($gh_stmt) {
                // bind_param requires a variable (by reference); use temp var
                $gastoId = $row['id'];
                if (!$gh_stmt->bind_param("i", $gastoId)) {
                    error_log("gastos.php: bind_param failed for gasto_historico: " . $gh_stmt->error);
                } else {
                    $gh_stmt->execute();
                    $gh_res = $gh_stmt->get_result();
                    while ($gh = $gh_res->fetch_assoc()) {
                        $row['historico'][] = $gh;
                    }
                }
                $gh_stmt->close();
            } else {
                // If prepare failed, log it for debugging
                error_log("gastos.php: prepare failed for gasto_historico query: " . $conn->error);
            }
        }

        $gastos[] = $row;
    }
    $stmt->close();

    // Include fixed expenses occurrences (if table exists)
    $checkFix = $conn->query("SHOW TABLES LIKE 'gastos_fixos'");
    if ($checkFix && $checkFix->num_rows > 0) {
        // prepare variables
        $stmtFix = null;
        $resFix = null;
        // If caller requested a category filter, only fetch fixed expenses of that category
        if (isset($categoria_filter) && $categoria_filter > 0) {
            $sqlFix = "SELECT gf.* FROM gastos_fixos gf WHERE gf.usuario_id = ? AND gf.active = 1 AND gf.categoria_id = ?";
            $stmtFix = $conn->prepare($sqlFix);
            if ($stmtFix) {
                $stmtFix->bind_param('ii', $usuario_id, $categoria_filter);
                $stmtFix->execute();
                $resFix = $stmtFix->get_result();
            }
        } else {
            $sqlFix = "SELECT gf.* FROM gastos_fixos gf WHERE gf.usuario_id = ? AND gf.active = 1";
            $stmtFix = $conn->prepare($sqlFix);
            if ($stmtFix) {
                $stmtFix->bind_param('i', $usuario_id);
                $stmtFix->execute();
                $resFix = $stmtFix->get_result();
            }
        }
        if ($resFix && $resFix instanceof mysqli_result) {
            while ($fx = $resFix->fetch_assoc()) {
                // compute occurrences of this fixed expense within the current period
                // gerar todas as ocorrências a partir da start_date até o fim do período
                $start = new DateTime($fx['start_date']);
                $periodStart = new DateTime($data_inicio);
                $periodEnd = new DateTime($data_fim);

                $allOccurrences = [];
                if ($fx['periodicidade'] === 'mes') {
                    $day = intval((new DateTime($fx['start_date']))->format('j'));
                    $cursor = clone $start;
                    // ajustar para o dia correto no mês corrente
                    $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), min($day, (int)$cursor->format('t')));
                    while ($cursor <= $periodEnd) {
                        $allOccurrences[] = $cursor->format('Y-m-d');
                        $cursor->modify('+1 month');
                        $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), min($day, (int)$cursor->format('t')));
                    }
                } else if ($fx['periodicidade'] === 'semana') {
                    $startWeekday = (int)(new DateTime($fx['start_date']))->format('w');
                    $cursor = clone $start;
                    // avançar cursor até o primeiro dia de ocorrência (pode ser start)
                    while ((int)$cursor->format('w') !== $startWeekday) $cursor->modify('+1 day');
                    while ($cursor <= $periodEnd) {
                        $allOccurrences[] = $cursor->format('Y-m-d');
                        $cursor->modify('+7 day');
                    }
                } else if ($fx['periodicidade'] === 'ano') {
                    $dayMonth = (new DateTime($fx['start_date']))->format('m-d');
                    $cursor = clone $start;
                    $cursor->setDate((int)$cursor->format('Y'), intval(substr($dayMonth,0,2)), intval(substr($dayMonth,3,2)));
                    while ($cursor <= $periodEnd) {
                        $allOccurrences[] = $cursor->format('Y-m-d');
                        $cursor->modify('+1 year');
                    }
                }

                // aplicar limite de períodos (se definido >0)
                $periodos_limite = isset($fx['periodos']) ? intval($fx['periodos']) : 0;
                if ($periodos_limite > 0) {
                    $allOccurrences = array_slice($allOccurrences, 0, $periodos_limite);
                }

                // agora filtrar apenas as ocorrências que caem dentro do período requisitado
                $occurrences = [];
                foreach ($allOccurrences as $idx => $occDate) {
                    // Pular ocorrência virtual se já existir um gasto real para este usuário/categoria/data
                    $chkStmt = $conn->prepare("SELECT id FROM gastos WHERE usuario_id = ? AND categoria_id = ? AND data_gasto = ? LIMIT 1");
                    $skip = false;
                    if ($chkStmt) {
                        $chkStmt->bind_param('iis', $usuario_id, $fx['categoria_id'], $occDate);
                        $chkStmt->execute();
                        $chkRes = $chkStmt->get_result();
                        if ($chkRes && $chkRes->num_rows > 0) {
                            $skip = true;
                        }
                        $chkStmt->close();
                    }
                    // incluir apenas se estiver no período pedido
                    if ($occDate < $periodStart->format('Y-m-d') || $occDate > $periodEnd->format('Y-m-d')) continue;
                    if ($skip) continue;
                    $virtual = [
                        'id' => 'fixo_' . $fx['id'] . '_' . ($idx+1),
                        'gasto_fixo_id' => intval($fx['id']),
                        'fixo' => true,
                        'categoria' => '',
                        'cor_categoria' => '#999',
                        'descricao' => $fx['descricao'] . ' (Fixo: ' . $fx['periodicidade'] . ')',
                        'valor' => floatval($fx['valor']),
                        'data_gasto' => $occDate,
                        'historico' => []
                    ];

                    // try to get category name/color
                    $catStmt = $conn->prepare("SELECT nome, cor_hex FROM categorias WHERE id = ? LIMIT 1");
                    if ($catStmt) {
                        $catStmt->bind_param('i', $fx['categoria_id']);
                        $catStmt->execute();
                        $catRes = $catStmt->get_result();
                        if ($catRes && $catRes->num_rows > 0) {
                            $c = $catRes->fetch_assoc();
                            $virtual['categoria'] = $c['nome'];
                            $virtual['cor_categoria'] = $c['cor_hex'];
                        }
                        $catStmt->close();
                    }

                        $gastos[] = $virtual;
                    }
                }
                if ($stmtFix) $stmtFix->close();
            }
    }

            // Sort gastos by date desc, then time desc (virtual fixed items may not have time)
            usort($gastos, function($a, $b) {
                $ta = strtotime($a['data_gasto'] ?? '1970-01-01');
                $tb = strtotime($b['data_gasto'] ?? '1970-01-01');
                if ($ta === $tb) {
                    $ha = isset($a['hora_gasto']) ? strtotime($a['hora_gasto']) : 0;
                    $hb = isset($b['hora_gasto']) ? strtotime($b['hora_gasto']) : 0;
                    return $hb <=> $ha; // desc
                }
                return $tb <=> $ta; // desc
            });

    // Opcional: quando debug_history=1, retornar linhas brutas de gasto_historico para auxiliar no debug
    // Also compute annual projection from fixed expenses
    $projecao_anual = 0.0;
    $checkFix2 = $conn->query("SHOW TABLES LIKE 'gastos_fixos'");
    if ($checkFix2 && $checkFix2->num_rows > 0) {
        $sqlAllFix = "SELECT * FROM gastos_fixos WHERE usuario_id = ? AND active = 1";
        $stmtAll = $conn->prepare($sqlAllFix);
        if ($stmtAll) {
            $stmtAll->bind_param('i', $usuario_id);
            $stmtAll->execute();
            $resAll = $stmtAll->get_result();
            $yearStart = new DateTime(date('Y-01-01'));
            $yearEnd = new DateTime(date('Y-12-31'));
            while ($fx = $resAll->fetch_assoc()) {
                // gerar todas as ocorrências do início (start_date) até o final do ano
                $start = new DateTime($fx['start_date']);
                $all = [];
                if ($fx['periodicidade'] === 'mes') {
                    $day = intval((new DateTime($fx['start_date']))->format('j'));
                    $c = clone $yearStart;
                    $c->setDate((int)$c->format('Y'), (int)$c->format('m'), min($day, (int)$c->format('t')));
                    while ($c <= $yearEnd) {
                        $all[] = $c->format('Y-m-d');
                        $c->modify('+1 month');
                        $c->setDate((int)$c->format('Y'), (int)$c->format('m'), min($day, (int)$c->format('t')));
                    }
                } else if ($fx['periodicidade'] === 'semana') {
                    $startWeekday = (int)(new DateTime($fx['start_date']))->format('w');
                    $c = clone $yearStart;
                    while ((int)$c->format('w') !== $startWeekday && $c <= $yearEnd) $c->modify('+1 day');
                    while ($c <= $yearEnd) {
                        $all[] = $c->format('Y-m-d');
                        $c->modify('+7 day');
                    }
                } else if ($fx['periodicidade'] === 'ano') {
                    $dayMonth = (new DateTime($fx['start_date']))->format('m-d');
                    $c = clone $yearStart;
                    $c->setDate((int)$c->format('Y'), intval(substr($dayMonth,0,2)), intval(substr($dayMonth,3,2)));
                    if ($c < $yearStart) $c->modify('+1 year');
                    while ($c <= $yearEnd) {
                        $all[] = $c->format('Y-m-d');
                        $c->modify('+1 year');
                    }
                }
                // aplicar limite de periodos se definido
                $periodos_limite = isset($fx['periodos']) ? intval($fx['periodos']) : 0;
                if ($periodos_limite > 0) {
                    $all = array_slice($all, 0, $periodos_limite);
                }
                // contar apenas ocorrências que também estão após o start_date
                $count = 0;
                foreach ($all as $d) {
                    $dt = new DateTime($d);
                    if ($dt >= $start && $dt >= $yearStart && $dt <= $yearEnd) $count++;
                }
                $projecao_anual += floatval($fx['valor']) * $count;
            }
            $stmtAll->close();
        }
    }
    if (!empty($_GET['debug_history'])) {
        $hist_raw = [];
        $sql_raw = "SELECT * FROM gasto_historico WHERE usuario_id = ? AND data_registro BETWEEN ? AND ? ORDER BY data_registro ASC";
        $stmt_raw = $conn->prepare($sql_raw);
        if ($stmt_raw) {
            $stmt_raw->bind_param("iss", $usuario_id, $data_inicio, $data_fim);
            $stmt_raw->execute();
            $res_raw = $stmt_raw->get_result();
            while ($r = $res_raw->fetch_assoc()) $hist_raw[] = $r;
            $stmt_raw->close();
        }
        api_json(['success' => true, 'gastos' => $gastos, 'historico_raw' => $hist_raw, 'projecao_anual' => round($projecao_anual, 2)]);
    } else {
        api_json(['success' => true, 'gastos' => $gastos, 'projecao_anual' => round($projecao_anual, 2)]);
    }
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
        $is_fixo = !empty($data['fixo']);
        $periodicidade = in_array($data['periodicidade'] ?? '', ['semana','mes','ano']) ? $data['periodicidade'] : 'mes';

        // Validar categoria pertence ao usuário
        $sql = "SELECT id FROM categorias WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $categoria_id, $usuario_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            api_json(['success' => false, 'message' => 'Categoria inválida']);
        }
        $stmt->close();

        // Ensure history table exists to keep individual descriptions
        $sqlCreate = "CREATE TABLE IF NOT EXISTS gasto_historico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gasto_id INT NOT NULL,
            usuario_id INT NOT NULL,
            descricao TEXT,
            valor DECIMAL(10,2) NOT NULL,
            data_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (gasto_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $conn->query($sqlCreate);

        // Ensure gastos_fixos table exists (for recurring expenses)
        $sqlCreateFix = "CREATE TABLE IF NOT EXISTS gastos_fixos (
            id INT PRIMARY KEY AUTO_INCREMENT,
            usuario_id INT NOT NULL,
            categoria_id INT NOT NULL,
            descricao VARCHAR(255),
            valor DECIMAL(10,2) NOT NULL,
            periodicidade ENUM('semana','mes','ano') NOT NULL DEFAULT 'mes',
            start_date DATE NOT NULL,
            periodos INT DEFAULT 0,
            active BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $conn->query($sqlCreateFix);

        // If the request is to create a fixed expense, insert into gastos_fixos instead of gastos
        if ($is_fixo) {
            // usar a data fornecida como start_date
            $start_date = $data_gasto ?: date('Y-m-d');
            $periodos = isset($data['periodos']) ? intval($data['periodos']) : 0;
            $sqlInsFix = "INSERT INTO gastos_fixos (usuario_id, categoria_id, descricao, valor, periodicidade, start_date, periodos) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtFix = $conn->prepare($sqlInsFix);
            if ($stmtFix) {
                $stmtFix->bind_param('iisdssi', $usuario_id, $categoria_id, $descricao, $valor, $periodicidade, $start_date, $periodos);
                $stmtFix->execute();
                $fix_id = $stmtFix->insert_id;
                $stmtFix->close();

                api_json(['success' => true, 'fixo_id' => $fix_id, 'message' => 'Gasto fixo criado']);
            } else {
                api_json(['success' => false, 'message' => 'Erro ao criar gasto fixo: ' . $conn->error]);
            }
        }

        // Try to find an existing gasto for this user+category+date (aggregate per day)
        $sqlFind = "SELECT id, valor FROM gastos WHERE usuario_id = ? AND categoria_id = ? AND data_gasto = ? LIMIT 1";
        $stmtFind = $conn->prepare($sqlFind);
        $stmtFind->bind_param("iis", $usuario_id, $categoria_id, $data_gasto);
        $stmtFind->execute();
        $resFind = $stmtFind->get_result();

        if ($resFind && $resFind->num_rows > 0) {
            // Merge into existing gasto (sum the value)
            $row = $resFind->fetch_assoc();
            $existing_id = intval($row['id']);
            $existing_val = floatval($row['valor']);
            $new_total = $existing_val + $valor;

            $stmtFind->close();

            $sqlUpdate = "UPDATE gastos SET valor = ?, hora_gasto = ?, descricao = ? WHERE id = ?";
            $stmtUpd = $conn->prepare($sqlUpdate);
            $shortDesc = substr($descricao, 0, 255);
            $stmtUpd->bind_param("dssi", $new_total, $hora_gasto, $shortDesc, $existing_id);
            $stmtUpd->execute();
            $stmtUpd->close();

            // Insert history entry
            $sqlHist = "INSERT INTO gasto_historico (gasto_id, usuario_id, descricao, valor) VALUES (?, ?, ?, ?)";
            $stmtHist = $conn->prepare($sqlHist);
            $stmtHist->bind_param("iisd", $existing_id, $usuario_id, $descricao, $valor);
            $stmtHist->execute();
            $stmtHist->close();

            api_json([
                'success' => true,
                'id' => $existing_id,
                'merged' => true,
                'new_total' => $new_total,
                'message' => 'Gasto somado ao existente'
            ]);
        }

        $stmtFind->close();

        // No existing gasto for same day: insert new gasto
        $sql = "INSERT INTO gastos (usuario_id, categoria_id, descricao, valor, data_gasto, hora_gasto) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisiss", $usuario_id, $categoria_id, $descricao, $valor, $data_gasto, $hora_gasto);
        $stmt->execute();
        $gasto_id = $stmt->insert_id;
        $stmt->close();

        // Insert initial history record
        $sqlHist = "INSERT INTO gasto_historico (gasto_id, usuario_id, descricao, valor) VALUES (?, ?, ?, ?)";
        $stmtHist = $conn->prepare($sqlHist);
        $stmtHist->bind_param("iisd", $gasto_id, $usuario_id, $descricao, $valor);
        $stmtHist->execute();
        $stmtHist->close();

        api_json([
            'success' => true,
            'id' => $gasto_id,
            'merged' => false,
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
            api_json(['success' => false, 'message' => 'Sem permissão']);
        }

        // Deletar
        // Delete related history first
        $sqlHistDel = "DELETE FROM gasto_historico WHERE gasto_id = ?";
        $stmtHistDel = $conn->prepare($sqlHistDel);
        $stmtHistDel->bind_param("i", $id);
        $stmtHistDel->execute();
        $stmtHistDel->close();

        $sql = "DELETE FROM gastos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        api_json(['success' => true, 'message' => 'Gasto deletado']);
    }
    else if ($action === 'deletar_fixo') {
        $id = intval($data['id']);
        // verify ownership
        $sql = "SELECT usuario_id FROM gastos_fixos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row || $row['usuario_id'] != $usuario_id) {
            api_json(['success' => false, 'message' => 'Sem permissão ou fixo inexistente']);
        }

        $sql = "DELETE FROM gastos_fixos WHERE id = ?";
        $stmtDel = $conn->prepare($sql);
        $stmtDel->bind_param("i", $id);
        $stmtDel->execute();
        $stmtDel->close();

        api_json(['success' => true, 'message' => 'Gasto fixo removido']);
    }
}
?>
