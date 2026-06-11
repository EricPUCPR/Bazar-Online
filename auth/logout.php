<?php
session_start();
require_once __DIR__ . '/../config/app.php';

if (isset($_SESSION['usuario_id'], $_SESSION['usuario_nome'])) {
    app_log_event(
        'Logout',
        'Usuário encerrou a sessão.',
        (int) $_SESSION['usuario_id'],
        $_SESSION['usuario_nome'],
        $_SESSION['usuario_email'] ?? null
    );
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: login.html");
exit;
?>
