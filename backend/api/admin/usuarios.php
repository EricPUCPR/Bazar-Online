<?php
/**
 * GET  /api/admin/usuarios.php  — lista usuários comuns
 * POST /api/admin/usuarios.php  — remove usuário (action=remover + id)
 *
 * Requer JWT de admin.
 * A promoção de usuários foi removida — admins são gerenciados diretamente no banco.
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

// ─── GET: listar usuários comuns ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result   = $conn->query("CALL sp_listar_usuarios()");
    $usuarios = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }
    while ($conn->next_result()) { }
    $conn->close();
    echo json_encode(['success' => true, 'usuarios' => $usuarios]);
    exit;
}

// ─── POST: ação sobre um usuário ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$action = trim($_POST['action'] ?? '');
$id     = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'mensagem' => 'Usuário inválido.']);
    exit;
}

// Busca o usuário para confirmar que existe
$stmtU = $conn->prepare("CALL sp_buscar_usuario_por_id(?)");
$stmtU->bind_param("i", $id);
$stmtU->execute();
$resU    = $stmtU->get_result();
$usuario = $resU ? $resU->fetch_assoc() : null;
$stmtU->close();
while ($conn->next_result()) { }

if (!$usuario) {
    echo json_encode(['success' => false, 'mensagem' => 'Usuário não encontrado.']);
    exit;
}

if ($action === 'remover') {
    $stmt = $conn->prepare("CALL sp_excluir_usuario_admin(?)");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    while ($conn->next_result()) { }

    if ($ok) {
        app_log_event('Remoção de usuário', 'Admin removeu um usuário.', $id, null, $usuario['nome'], $usuario['email']);
        echo json_encode(['success' => true, 'mensagem' => 'Usuário removido com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'mensagem' => 'Não foi possível remover o usuário.']);
    }
    exit;
}

echo json_encode(['success' => false, 'mensagem' => 'Ação inválida.']);
$conn->close();
