<?php
session_start();
require_once __DIR__ . '/config/app.php';

if (
    !isset($_SESSION['usuario_id'], $_SESSION['admin_logado'])
    || $_SESSION['admin_logado'] !== true
) {
    header("Location: admin_login.php");
    exit;
}

$conn = db_connect('DB_NAME_USUARIOS');

if ($conn->connect_error) {
    http_response_code(500);
    exit("Erro de conexão com o banco.");
}

db_ensure_usuario_schema($conn);
db_ensure_log_schema($conn);

$logs = [];
$resultado = $conn->query("
    SELECT usuario_id, nome, email, acao, detalhes, criado_em
    FROM logs_sistema
    ORDER BY criado_em DESC, id DESC
");

if ($resultado) {
    while ($log = $resultado->fetch_assoc()) {
        $logs[] = $log;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Logs do Sistema</title>
<link rel="stylesheet" href="assets/css/app.css">
</head>

<body class="store-page admin-page">

<div class="app-header">
    <span class="saudacao is-visible">Admin: <?= e($_SESSION['usuario_nome'] ?? 'Admin') ?></span>
    <a href="index.html" class="header-link btn-primary">Voltar</a>
    <a href="logout.php" class="header-link btn-light logout" onclick="return confirm('Tem certeza que deseja sair?')">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <path d="M16 17l5-5-5-5"></path>
            <path d="M21 12H9"></path>
        </svg>
        Sair
    </a>
</div>

<div class="app-main">
    <div class="sidebar">
        <a href="CadastroRoupas.php" class="user-icon" title="Cadastrar roupa" aria-label="Cadastrar roupa">👕</a>
        <a href="PaginaUsuario.php" class="user-icon" title="Perfil" aria-label="Perfil">👤</a>
        <a href="admin_logs.php" class="user-icon" title="Logs" aria-label="Logs">📋</a>
        <a href="admin_usuarios.php" class="user-icon" title="Gerenciar usuários" aria-label="Gerenciar usuários">👥</a>
    </div>

    <main class="content">
        <section class="admin-panel">
            <h2>Logs do Sistema</h2>

            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>E-mail</th>
                            <th>Ação</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) === 0): ?>
                            <tr>
                                <td colspan="6" class="admin-empty">Nenhum log registrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= e($log['criado_em']) ?></td>
                                <td><?= e($log['usuario_id'] ?? '-') ?></td>
                                <td><?= e($log['nome'] ?? '-') ?></td>
                                <td><?= e($log['email'] ?? '-') ?></td>
                                <td><?= e($log['acao']) ?></td>
                                <td><?= e($log['detalhes']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

</body>
</html>
