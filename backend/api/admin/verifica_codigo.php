<?php
/**
 * POST /api/admin/verifica_codigo.php
 *
 * Etapa 2 do login admin (via código Telegram/e-mail).
 * Lógica extraída de admin_verifica_codigo.php.
 *
 * Body: pending_token, codigo
 *
 * Resposta de sucesso:
 *   { "success": true, "token": "<jwt_admin>", "mensagem": "..." }
 */

require_once __DIR__ . '/../../config/app.php';

app_cors();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$pendingToken   = trim($_POST['pending_token'] ?? '');
$codigoDigitado = trim($_POST['codigo'] ?? '');

if ($pendingToken === '' || $codigoDigitado === '') {
    echo json_encode(['success' => false, 'mensagem' => 'Dados incompletos.']);
    exit;
}

$pending = jwt_verify($pendingToken);

if ($pending === null || ($pending['tipo'] ?? '') !== 'admin_2fa_pendente') {
    echo json_encode(['success' => false, 'mensagem' => 'Sessão de login admin expirada. Faça login novamente.']);
    exit;
}

if (!preg_match('/^\d{6}$/', $codigoDigitado)) {
    echo json_encode(['success' => false, 'mensagem' => 'Digite um código válido com 6 dígitos.']);
    exit;
}

if ($codigoDigitado !== (string) $pending['codigo']) {
    echo json_encode(['success' => false, 'mensagem' => 'Código de verificação inválido.']);
    exit;
}

$adminId    = (int) $pending['sub'];
$adminNome  = $pending['nome'];
$adminEmail = $pending['email'];

// JWT stateless: apenas sub (id do admin) e admin=true
$token = jwt_generate([
    'sub'   => $adminId,
    'admin' => true,
]);

app_log_event('Login admin', 'Login admin validado por código.', null, $adminId, $adminNome, $adminEmail);

echo json_encode([
    'success'  => true,
    'token'    => $token,
    'nome'     => $adminNome,
    'admin'    => true,
    'mensagem' => 'Login admin realizado com sucesso.',
]);
