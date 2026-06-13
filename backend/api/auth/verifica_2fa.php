<?php
/**
 * POST /api/auth/verifica_2fa.php
 *
 * Etapa 2 do login de usuário comum.
 * Recebe o "pending_token" (gerado em login.php) e o código 2FA digitado pelo usuário.
 * Se correto, emite o JWT de autenticação definitivo.
 *
 * Body (multipart/form-data ou application/x-www-form-urlencoded):
 *   pending_token: string
 *   codigo:        string (6 dígitos)
 *
 * Resposta de sucesso:
 *   { "success": true, "token": "<jwt>", "nome": "João", "admin": false, "mensagem": "..." }
 */

require_once __DIR__ . '/../../config/app.php';

app_cors();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$pendingToken = trim($_POST['pending_token'] ?? '');
$codigoDigitado = trim($_POST['codigo'] ?? '');

if ($pendingToken === '' || $codigoDigitado === '') {
    echo json_encode(['success' => false, 'mensagem' => 'Dados incompletos.']);
    exit;
}

// Valida o pending_token
$pending = jwt_verify($pendingToken);

if ($pending === null || ($pending['tipo'] ?? '') !== '2fa_pendente') {
    echo json_encode(['success' => false, 'mensagem' => 'Sessão de verificação expirada. Faça login novamente.']);
    exit;
}

// Valida o código
if (!preg_match('/^\d{6}$/', $codigoDigitado)) {
    echo json_encode(['success' => false, 'mensagem' => 'Digite um código válido com 6 dígitos.']);
    exit;
}

if ($codigoDigitado !== (string) $pending['codigo']) {
    echo json_encode(['success' => false, 'mensagem' => 'Código de verificação inválido.']);
    exit;
}

// Código correto — emite JWT de autenticação definitivo (8 horas)
$userId    = (int) $pending['sub'];
$userNome  = $pending['nome'];
$userEmail = $pending['email'];

$token = jwt_generate([
    'sub'   => $userId,
    'nome'  => $userNome,
    'email' => $userEmail,
    'admin' => false,
]);

app_log_event(
    'Login',
    'Login de usuário validado por código de e-mail.',
    $userId,
    $userNome,
    $userEmail
);

echo json_encode([
    'success'  => true,
    'token'    => $token,
    'nome'     => $userNome,
    'admin'    => false,
    'mensagem' => 'Login realizado com sucesso.',
]);
