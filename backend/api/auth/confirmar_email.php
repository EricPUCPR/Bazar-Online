<?php
/**
 * GET /api/auth/confirmar_email.php?token=<token>
 *
 * Valida o token de confirmação de e-mail e retorna JSON.
 * O frontend exibe a mensagem de sucesso/erro na página confirmar-email.html.
 *
 * Resposta de sucesso:
 *   { "success": true, "mensagem": "E-mail validado com sucesso." }
 */

require_once __DIR__ . '/../../config/app.php';

app_cors();
header('Content-Type: application/json');

$conn = db_connect('DB_NAME');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Erro de conexão com o banco.']);
    exit;
}

db_ensure_usuario_schema($conn);

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    echo json_encode(['success' => false, 'mensagem' => 'Link de validação inválido.']);
    exit;
}

$stmt = $conn->prepare("CALL sp_buscar_usuario_por_token_confirmacao(?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'mensagem' => 'Erro interno ao validar o e-mail.']);
    exit;
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$row    = $result ? $result->fetch_assoc() : null;
$stmt->close();
while ($conn->next_result()) { }

if (!$row) {
    echo json_encode(['success' => false, 'mensagem' => 'Link de validação inválido ou expirado.']);
    exit;
}

$idUsuario = (int) $row['id'];
$update = $conn->prepare("CALL sp_confirmar_email(?)");

if (!$update) {
    echo json_encode(['success' => false, 'mensagem' => 'Erro interno ao validar o e-mail.']);
    exit;
}

$update->bind_param("i", $idUsuario);
$ok = $update->execute();
$update->close();
while ($conn->next_result()) { }

if ($ok) {
    app_log_event('Validação de e-mail', 'Usuário validou o e-mail da conta.', $idUsuario);
    echo json_encode(['success' => true, 'mensagem' => 'E-mail validado com sucesso! Agora você pode fazer login.']);
} else {
    echo json_encode(['success' => false, 'mensagem' => 'Erro interno ao validar o e-mail.']);
}
