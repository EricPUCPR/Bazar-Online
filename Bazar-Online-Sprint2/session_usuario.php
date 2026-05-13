<?php
session_start();
header('Content-Type: application/json');

$adminLogado = isset($_SESSION['usuario_id'], $_SESSION['admin_logado'])
    && $_SESSION['admin_logado'] === true;

if (isset($_SESSION['usuario_id'], $_SESSION['usuario_nome'])) {
    echo json_encode([
        "logado" => true,
        "nome" => $_SESSION['usuario_nome'],
        "admin" => $adminLogado
    ]);
    exit;
}

echo json_encode([
    "logado" => false,
    "admin" => false
]);
?>
