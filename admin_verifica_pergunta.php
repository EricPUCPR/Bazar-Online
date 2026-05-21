<?php
session_start();
require_once __DIR__ . '/config/app.php';

header('Content-Type: application/json');

$resposta = trim($_POST['resposta_seguranca'] ?? '');

if (!isset(
    $_SESSION['admin_pergunta_user_id'],
    $_SESSION['admin_pergunta_user_nome'],
    $_SESSION['admin_pergunta_user_email'],
    $_SESSION['admin_pergunta_hash']
)) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Sessão de pergunta expirada. Faça login novamente."
    ]);
    exit;
}

if ($resposta === '') {
    echo json_encode([
        "success" => false,
        "mensagem" => "Digite a resposta de segurança."
    ]);
    exit;
}

if (!password_verify(strtolower($resposta), $_SESSION['admin_pergunta_hash'])) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Resposta incorreta."
    ]);
    exit;
}

session_regenerate_id(true);

$_SESSION['usuario_id'] = $_SESSION['admin_pergunta_user_id'];
$_SESSION['usuario_nome'] = $_SESSION['admin_pergunta_user_nome'];
$_SESSION['usuario_email'] = $_SESSION['admin_pergunta_user_email'];
$_SESSION['admin_logado'] = true;

unset(
    $_SESSION['admin_pergunta_user_id'],
    $_SESSION['admin_pergunta_user_nome'],
    $_SESSION['admin_pergunta_user_email'],
    $_SESSION['admin_pergunta_hash']
);

echo json_encode([
    "success" => true,
    "mensagem" => "Login admin realizado com sucesso."
]);
?>