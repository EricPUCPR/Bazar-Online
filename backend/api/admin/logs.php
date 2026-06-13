<?php
/**
 * GET /api/admin/logs.php
 *
 * Retorna logs do sistema. Requer JWT de admin.
 * Lógica extraída de admin_logs.php.
 *
 * Header: Authorization: Bearer <token>  (admin)
 *
 * Query params (opcionais):
 *   limite (padrão 200), pagina (padrão 1)
 *
 * Resposta:
 *   { "success": true, "logs": [...], "total": N }
 */

require_once __DIR__ . '/../../config/app.php';

app_cors();
header('Content-Type: application/json');

$payload = jwt_require(true);

$conn = db_connect('DB_NAME');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Erro de conexão com o banco.']);
    exit;
}

db_ensure_log_schema($conn);

$limite = max(1, min(500, (int) ($_GET['limite'] ?? 200)));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $limite;

// Total
$totalRes = $conn->query("SELECT COUNT(*) AS total FROM logs_sistema");
$total    = $totalRes ? (int) $totalRes->fetch_assoc()['total'] : 0;

$stmt = $conn->prepare("SELECT id, usuario_id, nome, email, acao, detalhes, criado_em FROM logs_sistema ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limite, $offset);
$stmt->execute();
$result = $stmt->get_result();
$logs   = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'logs' => $logs, 'total' => $total, 'pagina' => $pagina, 'limite' => $limite]);
