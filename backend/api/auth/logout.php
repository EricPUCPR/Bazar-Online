<?php
/**
 * POST /api/auth/logout.php
 *
 * Com JWT stateless, "logout" é feito no frontend simplesmente
 * descartando o token do localStorage/sessionStorage.
 *
 * Este endpoint existe para registrar o evento no log e confirmar
 * a operação. O token enviado NÃO é invalidado no servidor
 * (para invalidação real, implemente uma blocklist no banco — fora do escopo inicial).
 *
 * Header obrigatório: Authorization: Bearer <token>
 */

require_once __DIR__ . '/../../config/app.php';

app_cors();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$token   = jwt_from_request();
$payload = $token ? jwt_verify($token) : null;

if ($payload) {
    app_log_event(
        'Logout',
        'Usuário encerrou a sessão.',
        (int) $payload['sub'],
        $payload['nome'] ?? null,
        $payload['email'] ?? null
    );
}

echo json_encode(['success' => true, 'mensagem' => 'Logout registrado. Descarte o token no cliente.']);
