<?php
/**
 * GET /api/auth/me.php
 *
 * Retorna os dados do usuário autenticado a partir do JWT.
 * Substitui o antigo session_usuario.php.
 *
 * Header obrigatório: Authorization: Bearer <token>
 *
 * Resposta de sucesso:
 *   { "logado": true, "sub": 1, "nome": "João", "email": "...", "admin": false }
 *
 * Resposta sem token (ou token inválido):
 *   { "logado": false }
 */

require_once __DIR__ . '/../../config/app.php';

app_cors();
header('Content-Type: application/json');

$token   = jwt_from_request();
$payload = $token ? jwt_verify($token) : null;

if ($payload === null) {
    echo json_encode(['logado' => false]);
    exit;
}

echo json_encode([
    'logado' => true,
    'sub'    => $payload['sub'],
    'nome'   => $payload['nome'],
    'email'  => $payload['email'] ?? null,
    'admin'  => $payload['admin'] ?? false,
]);
