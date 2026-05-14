<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/app.php'; // necessário para log_atividade em todos os fluxos
header('Content-Type: application/json');

$codigo = trim($_POST['codigo'] ?? '');

// Sessão 2FA inexistente — sem id de usuário para logar
if (!isset($_SESSION['2fa_user_id'], $_SESSION['2fa_user_nome'], $_SESSION['2fa_codigo'], $_SESSION['2fa_expira'])) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Sessão de verificação expirada. Faça login novamente."
    ]);
    exit;
}

$userId2fa = (int) $_SESSION['2fa_user_id'];

// Código expirado
if (time() > (int) $_SESSION['2fa_expira']) {
    log_atividade($userId2fa, "Código 2FA expirado durante tentativa de login.");
    unset($_SESSION['2fa_user_id'], $_SESSION['2fa_user_nome'], $_SESSION['2fa_codigo'], $_SESSION['2fa_expira']);
    echo json_encode([
        "success" => false,
        "mensagem" => "Código expirado. Faça login novamente."
    ]);
    exit;
}

// Formato inválido (não são 6 dígitos numéricos)
if (!preg_match('/^\d{6}$/', $codigo)) {
    log_atividade($userId2fa, "Código 2FA com formato inválido informado durante login.");
    echo json_encode([
        "success" => false,
        "mensagem" => "Digite um código válido com 6 dígitos."
    ]);
    exit;
}

// Código incorreto
if ($codigo !== (string) $_SESSION['2fa_codigo']) {
    log_atividade($userId2fa, "Código 2FA incorreto informado durante tentativa de login.");
    echo json_encode([
        "success" => false,
        "mensagem" => "Código de verificação inválido."
    ]);
    exit;
}

// Sucesso — promove sessão e registra login
$_SESSION['usuario_id']   = $_SESSION['2fa_user_id'];
$_SESSION['usuario_nome'] = $_SESSION['2fa_user_nome'];
$_SESSION['usuario_email'] = $_SESSION['2fa_user_email'] ?? null;
unset($_SESSION['admin_logado']);

log_atividade($_SESSION['usuario_id'], "Login realizado com sucesso via 2FA.");

unset($_SESSION['2fa_user_id'], $_SESSION['2fa_user_nome'], $_SESSION['2fa_user_email'], $_SESSION['2fa_codigo'], $_SESSION['2fa_expira']);

echo json_encode([
    "success" => true,
    "mensagem" => "Login realizado com sucesso."
]);
?>
