<?php
/**
 * GET  /api/admin/usuarios.php         — lista todos os usuários
 * POST /api/admin/usuarios.php         — remove ou promove usuário
 *
 * Requer JWT de admin.
 * Lógica extraída de admin_usuarios.php.
 *
 * Header: Authorization: Bearer <token>  (admin)
 *
 * POST body (uma das duas ações):
 *   action=remover  + id
 *   action=promover + id
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

db_ensure_usuario_schema($conn);

// ─── GET: listar usuários ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result   = $conn->query("CALL sp_listar_usuarios()");
    $usuarios = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }
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

// Busca o usuário
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

if ($action === 'promover') {
    if ((int) $usuario['is_admin'] === 1) {
        echo json_encode(['success' => false, 'mensagem' => 'Este usuário já é admin.']);
        exit;
    }

    $stmt = $conn->prepare("CALL sp_promover_usuario(?)");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    while ($conn->next_result()) { }

    if ($ok) {
        app_log_event('Promoção admin', 'Usuário promovido a admin.', $id, $usuario['nome'], $usuario['email']);
        echo json_encode(['success' => true, 'mensagem' => 'Usuário promovido a admin com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'mensagem' => 'Não foi possível promover o usuário.']);
    }
    exit;
}

if ($action === 'remover') {
    if ((int) $usuario['is_admin'] === 1) {
        echo json_encode(['success' => false, 'mensagem' => 'Contas admin não podem ser removidas por esta página.']);
        exit;
    }

    $stmt = $conn->prepare("CALL sp_excluir_conta(?)");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    while ($conn->next_result()) { }

    if ($ok) {
        app_log_event('Remoção de usuário', 'Admin removeu um usuário.', $id, $usuario['nome'], $usuario['email']);
        echo json_encode(['success' => true, 'mensagem' => 'Usuário removido com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'mensagem' => 'Não foi possível remover o usuário.']);
    }
    exit;
}

echo json_encode(['success' => false, 'mensagem' => 'Ação inválida.']);
$conn->close();
