<?php
/**
 * POST /api/admin/verifica_pergunta.php
 *
 * Etapa 2 do login admin via pergunta de segurança.
 * Lógica extraída de admin_verifica_pergunta.php.
 *
 * Body: pending_token, resposta_seguranca
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
$resposta     = trim($_POST['resposta_seguranca'] ?? '');

if ($pendingToken === '' || $resposta === '') {
    echo json_encode(['success' => false, 'mensagem' => 'Dados incompletos.']);
    exit;
}

$pending = jwt_verify($pendingToken);

if ($pending === null || ($pending['tipo'] ?? '') !== 'admin_pergunta_pendente') {
    echo json_encode(['success' => false, 'mensagem' => 'Sessão de pergunta expirada. Faça login novamente.']);
    exit;
}

if (!password_verify(strtolower($resposta), $pending['resposta_hash'])) {
    echo json_encode(['success' => false, 'mensagem' => 'Resposta incorreta.']);
    exit;
}

$adminId    = (int) $pending['sub'];
$adminNome  = $pending['nome'];
$adminEmail = $pending['email'];

$token = jwt_generate([
    'sub'   => $adminId,
    'nome'  => $adminNome,
    'email' => $adminEmail,
    'admin' => true,
]);

app_log_event('Login admin', 'Login admin validado por pergunta de segurança.', $adminId, $adminNome, $adminEmail);

echo json_encode([
    'success'  => true,
    'token'    => $token,
    'nome'     => $adminNome,
    'admin'    => true,
    'mensagem' => 'Login admin realizado com sucesso.',
]);
