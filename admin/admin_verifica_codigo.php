<?php
session_start();
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json');

$codigo = trim($_POST['codigo'] ?? '');

if (!isset(
    $_SESSION['admin_2fa_user_id'],
    $_SESSION['admin_2fa_user_nome'],
    $_SESSION['admin_2fa_user_email'],
    $_SESSION['admin_2fa_codigo'],
    $_SESSION['admin_2fa_expira']
)) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Sessão de login admin expirada. Faça login novamente."
    ]);
    exit;
}

if (time() > (int) $_SESSION['admin_2fa_expira']) {
    unset(
        $_SESSION['admin_2fa_user_id'],
        $_SESSION['admin_2fa_user_nome'],
        $_SESSION['admin_2fa_user_email'],
        $_SESSION['admin_2fa_codigo'],
        $_SESSION['admin_2fa_expira']
    );

    echo json_encode([
        "success" => false,
        "mensagem" => "Código expirado. Faça login novamente."
    ]);
    exit;
}

if (!preg_match('/^\d{6}$/', $codigo)) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Digite um código válido com 6 dígitos."
    ]);
    exit;
}

if ($codigo !== (string) $_SESSION['admin_2fa_codigo']) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Código de verificação inválido."
    ]);
    exit;
}

$_SESSION['usuario_id'] = $_SESSION['admin_2fa_user_id'];
$_SESSION['usuario_nome'] = $_SESSION['admin_2fa_user_nome'];
$_SESSION['usuario_email'] = $_SESSION['admin_2fa_user_email'];
$_SESSION['admin_logado'] = true;

app_log_event(
    'Login admin',
    'Login admin validado por código de e-mail.',
    (int) $_SESSION['usuario_id'],
    $_SESSION['usuario_nome'],
    $_SESSION['admin_2fa_user_email'] ?? null
);

unset(
    $_SESSION['admin_2fa_user_id'],
    $_SESSION['admin_2fa_user_nome'],
    $_SESSION['admin_2fa_user_email'],
    $_SESSION['admin_2fa_codigo'],
    $_SESSION['admin_2fa_expira']
);

echo json_encode([
    "success" => true,
    "mensagem" => "Login admin realizado com sucesso."
]);
?>
