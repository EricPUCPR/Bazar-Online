<?php
/**
 * POST /api/roupas/excluir.php
 *
 * Exclui uma roupa. Requer JWT de admin.
 * Lógica extraída de excluir_roupa.php.
 *
 * Header: Authorization: Bearer <token>  (admin)
 * Body: id (int)
 */

require_once __DIR__ . '/../../config/app.php';

app_cors();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

// Apenas admin pode excluir
$payload = jwt_require(true);

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'mensagem' => 'Anúncio inválido.']);
    exit;
}

$conn = db_connect('DB_NAME');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Erro ao conectar no banco.']);
    exit;
}

db_ensure_roupa_schema($conn);

// Recupera caminho da foto antes de deletar
$fotoPath = null;
$stmtFoto = $conn->prepare("CALL sp_buscar_foto_roupa(?)");
if ($stmtFoto) {
    $stmtFoto->bind_param("i", $id);
    $stmtFoto->execute();
    $resultFoto = $stmtFoto->get_result();
    $foto       = $resultFoto ? $resultFoto->fetch_assoc() : null;
    $fotoPath   = $foto['foto_path'] ?? null;
    $stmtFoto->close();
    while ($conn->next_result()) { }
}

$stmt = $conn->prepare("CALL sp_excluir_roupa(?)");

if (!$stmt) {
    echo json_encode(['success' => false, 'mensagem' => 'Erro ao preparar exclusão.']);
    exit;
}

$stmt->bind_param("i", $id);
$ok       = $stmt->execute();
$afetadas = $stmt->affected_rows;
$stmt->close();
while ($conn->next_result()) { }

// Remove arquivo de foto
if ($ok && $afetadas > 0 && $fotoPath) {
    $arquivo = dirname(__DIR__, 2) . '/storage/' . ltrim($fotoPath, '/');
    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}

if ($ok && $afetadas > 0) {
    app_log_event(
        'Exclusão de anúncio',
        'Admin excluiu um anúncio de roupa.',
        (int) $payload['sub'],
        $payload['nome'] ?? null,
        $payload['email'] ?? null
    );
}

echo json_encode([
    'success'  => $ok && $afetadas > 0,
    'mensagem' => $ok && $afetadas > 0 ? 'Anúncio excluído.' : 'Anúncio não encontrado.',
]);

$conn->close();
