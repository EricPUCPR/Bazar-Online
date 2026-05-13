<?php
session_start();
require_once __DIR__ . '/config/app.php';

$conn = db_connect('DB_NAME_USUARIOS');

if ($conn->connect_error) {
    http_response_code(500);
    exit("Erro de conexão com o banco.");
}

db_ensure_usuario_schema($conn);

if (!isset($_SESSION['usuario_id'])) {
    header("Location: Login.html");
    exit;
}

$id = (int) $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT nome, email, telefone, endereco, data_nascimento, is_admin FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

$isAdminAccount = (int) ($user['is_admin'] ?? 0) === 1;
$isAdmin = $isAdminAccount && !empty($_SESSION['admin_logado']);

if ($isAdminAccount && !$isAdmin) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

$mensagemPerfil = "";

if (isset($_GET['excluir'])) {
    if ($isAdmin) {
        $mensagemPerfil = "A conta admin não pode ser excluída por esta página.";
    } else {
        app_log_event(
            'Exclusão de conta',
            'Usuário excluiu a própria conta.',
            $id,
            $user['nome'] ?? null,
            $user['email'] ?? null
        );

        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        session_destroy();
        header("Location: Login.html");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Meu Perfil</title>
<link rel="stylesheet" href="assets/css/app.css">
</head>

<body class="profile-page">

<div class="app-header">
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

<div class="page-main">

<div class="box">

<h2>Meu Perfil</h2>

<?php if ($user): ?>
<?php if ($mensagemPerfil): ?>
<p class="erro"><?= e($mensagemPerfil) ?></p>
<?php endif; ?>

<div class="profile-info"><b>Nome:</b> <?= e($user['nome'] ?? '') ?></div>
<div class="profile-info"><b>E-mail:</b> <?= e($user['email'] ?? '') ?></div>
<div class="profile-info"><b>Telefone:</b> <?= e($user['telefone'] ?? '') ?></div>
<div class="profile-info"><b>Endereço:</b> <?= e($user['endereco'] ?? '') ?></div>
<div class="profile-info"><b>Nascimento:</b> <?= e($user['data_nascimento'] ?? '') ?></div>

<?php if (!$isAdmin): ?>
    <a class="btn btn-block btn-danger"
       href="?excluir=1"
       onclick="return confirm('Tem certeza que deseja excluir sua conta?')">
       Excluir minha conta
    </a>
<?php endif; ?>

<?php else: ?>
<p>Usuário não encontrado.</p>
<?php endif; ?>

</div>

</div>

</body>
</html>
