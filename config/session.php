<?php

// Configura os parâmetros do cookie de sessão ANTES de iniciar a sessão
// lifetime = 0 faz com que o cookie expire ao fechar o navegador
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// Gerenciamento de Timeout (1 hora)
$timeout_duration = 3600;

if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $timeout_duration) {
        // Sessão expirou
        session_unset();
        session_destroy();
        session_start(); // Inicia uma nova sessão limpa
    }
}

$_SESSION['last_activity'] = time();

/**
 * Função para proteger páginas que exigem login.
 * Se o usuário não estiver logado, redireciona para a página de login.
 */
function require_login()
{
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: Login.html");
        exit;
    }
}
