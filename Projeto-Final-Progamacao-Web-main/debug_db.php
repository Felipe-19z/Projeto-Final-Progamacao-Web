<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

function table_exists($conn, $table) {
    $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    return ($res && $res->num_rows > 0);
}

$tables = ['usuarios', 'categorias', 'gastos', 'gastos_fixos', 'gasto_historico', 'configuracoes_usuario'];
$out = ['db' => DB_NAME, 'connected' => true, 'tables' => []];

foreach ($tables as $t) {
    $exists = table_exists($conn, $t);
    $count = null;
    if ($exists) {
        $r = $conn->query("SELECT COUNT(*) AS c FROM `" . $conn->real_escape_string($t) . "`");
        if ($r) {
            $row = $r->fetch_assoc();
            $count = intval($row['c']);
        }
    }
    $out['tables'][$t] = ['exists' => $exists, 'count' => $count];
}

// Show current DB user and host
$out['db_user'] = DB_USER;
$out['db_host'] = DB_HOST;

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
