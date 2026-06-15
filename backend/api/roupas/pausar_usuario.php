<?php
/**
 * POST /api/roupas/pausar_usuario.php
 *
 * Permite que o próprio usuário pause/remova uma roupa que ainda está disponível.
 * Só funciona se a roupa pertencer ao usuário autenticado e estiver disponível (pausado = 0).
 *
 * Header: Authorization: Bearer <token>
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

$payload    = jwt_require(false);
$id_usuario = (int) $payload['sub'];

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

$stmt = $conn->prepare("CALL sp_pausar_roupa_usuario(?, ?)");
$stmt->bind_param("ii", $id, $id_usuario);
$stmt->execute();
$res      = $stmt->get_result();
$row      = $res ? $res->fetch_assoc() : null;
$afetadas = (int) ($row['afetadas'] ?? 0);
$stmt->close();
while ($conn->next_result()) { }

$conn->close();

if ($afetadas > 0) {
    echo json_encode(['success' => true, 'mensagem' => 'Publicação removida com sucesso.']);
} else {
    echo json_encode(['success' => false, 'mensagem' => 'Não foi possível remover a publicação. Ela já pode estar indisponível ou não pertence a você.']);
}
