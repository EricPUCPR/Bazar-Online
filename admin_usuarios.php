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

$mensagem = "";
$mensagemClasse = "feedback-success";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idRemover = (int) ($_POST['remover_id'] ?? 0);
    $idPromover = (int) ($_POST['promover_id'] ?? 0);

    if ($idPromover > 0) {
        $stmtUsuario = $conn->prepare("SELECT nome, email, is_admin FROM usuarios WHERE id = ? LIMIT 1");
        $usuarioPromovido = null;

        if ($stmtUsuario) {
            $stmtUsuario->bind_param("i", $idPromover);
            $stmtUsuario->execute();
            $resultUsuario = $stmtUsuario->get_result();
            $usuarioPromovido = $resultUsuario ? $resultUsuario->fetch_assoc() : null;
            $stmtUsuario->close();
        }

        if (!$usuarioPromovido) {
            $mensagem = "Usuário não encontrado.";
            $mensagemClasse = "feedback-error";
        } elseif ((int) ($usuarioPromovido['is_admin'] ?? 0) === 1) {
            $mensagem = "Este usuário já é admin.";
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET is_admin = 1 WHERE id = ?");

            if ($stmt) {
                $stmt->bind_param("i", $idPromover);
                if ($stmt->execute()) {
                    $mensagem = "Usuário promovido a admin com sucesso.";
                    app_log_event(
                        'Promoção admin',
                        'Usuário promovido a admin pelo painel.',
                        $idPromover,
                        $usuarioPromovido['nome'] ?? null,
                        $usuarioPromovido['email'] ?? null
                    );
                } else {
                    $mensagem = "Não foi possível promover o usuário.";
                    $mensagemClasse = "feedback-error";
                }
                $stmt->close();
            } else {
                $mensagem = "Erro ao preparar promoção do usuário.";
                $mensagemClasse = "feedback-error";
            }
        }
    } elseif ($idRemover <= 0) {
        $mensagem = "Usuário inválido.";
        $mensagemClasse = "feedback-error";
    } else {
        $stmtAdmin = $conn->prepare("SELECT is_admin FROM usuarios WHERE id = ? LIMIT 1");
        $usuarioAdmin = 0;
        if ($stmtAdmin) {
            $stmtAdmin->bind_param("i", $idRemover);
            $stmtAdmin->execute();
            $resultAdmin = $stmtAdmin->get_result();
            $rowAdmin = $resultAdmin ? $resultAdmin->fetch_assoc() : null;
            $usuarioAdmin = (int) ($rowAdmin['is_admin'] ?? 0);
            $stmtAdmin->close();
        }

        if ($usuarioAdmin === 1) {
            $mensagem = "Contas admin não podem ser removidas por esta página.";
            $mensagemClasse = "feedback-error";
        } else {
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");

            if ($stmt) {
                $stmt->bind_param("i", $idRemover);
                if ($stmt->execute()) {
                    $mensagem = "Usuário removido com sucesso.";
                    app_log_event(
                        'Remoção de usuário',
                        'Admin removeu um usuário pelo painel.',
                        $idRemover,
                        null,
                        null
                    );
                } else {
                    $mensagem = "Não foi possível remover o usuário.";
                    $mensagemClasse = "feedback-error";
                }
                $stmt->close();
            } else {
                $mensagem = "Erro ao preparar remoção do usuário.";
                $mensagemClasse = "feedback-error";
            }
        }
    }
}

$usuarios = [];
$resultado = $conn->query("SELECT id, nome, email, is_admin FROM usuarios ORDER BY id ASC");

if ($resultado) {
    while ($usuario = $resultado->fetch_assoc()) {
        $usuarios[] = $usuario;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Gerenciar Usuários</title>
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
            <h2>Gerenciar Usuários</h2>

            <?php if ($mensagem): ?>
                <p class="feedback <?= e($mensagemClasse) ?>"><?= e($mensagem) ?></p>
            <?php endif; ?>

            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>E-mail</th>
                            <th>Perfil</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($usuarios) === 0): ?>
                            <tr>
                                <td colspan="4" class="admin-empty">Nenhum usuário cadastrado.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($usuarios as $usuario): ?>
                            <?php $isAdmin = (int) ($usuario['is_admin'] ?? 0) === 1; ?>
                            <tr>
                                <td><?= e($usuario['id']) ?></td>
                                <td><?= e($usuario['email']) ?></td>
                                <td>
                                    <?php if ($isAdmin): ?>
                                        <span class="admin-badge">Admin</span>
                                    <?php else: ?>
                                        <span class="admin-badge">Usuário</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$isAdmin): ?>
                                        <div class="admin-actions-row">
                                            <form method="POST" class="inline-form" onsubmit="return confirm('Promover este usuário a admin?')">
                                                <input type="hidden" name="promover_id" value="<?= e($usuario['id']) ?>">
                                                <button type="submit" class="btn-small">Promover para admin</button>
                                            </form>

                                            <form method="POST" class="inline-form" onsubmit="return confirm('Remover este usuário?')">
                                                <input type="hidden" name="remover_id" value="<?= e($usuario['id']) ?>">
                                                <button type="submit" class="btn-danger btn-small">Remover</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="admin-badge">Protegido</span>
                                    <?php endif; ?>
                                </td>
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
