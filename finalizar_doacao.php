<?php
session_start();
require_once __DIR__ . '/config/app.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/mailer/PHPMailer/src/Exception.php';
require 'vendor/mailer/PHPMailer/src/PHPMailer.php';
require 'vendor/mailer/PHPMailer/src/SMTP.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['usuario_nome'])) {
    header("Location: Login.html");
    exit;
}

$mensagem = "";
$erro = "";
$sucesso = false;

function ids_do_request(): array
{
    $raw = $_POST['ids'] ?? $_GET['ids'] ?? '';
    $ids = array_map('intval', explode(',', (string) $raw));
    $ids = array_filter($ids, fn($id) => $id > 0);
    return array_values(array_unique($ids));
}

function buscar_roupas(mysqli $conn, array $ids): array
{
    if (count($ids) === 0) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("
        SELECT id, titulo, tipo, tamanho, sexo, estado, local_doacao, foto_path, id_usuario, pausado
        FROM roupas
        WHERE id IN ($placeholders)
        ORDER BY id ASC
    ");

    if (!$stmt) {
        return [];
    }

    $tipos = str_repeat('i', count($ids));
    $stmt->bind_param($tipos, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();
    $roupas = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $roupas[] = $row;
        }
    }

    $stmt->close();
    return $roupas;
}

function buscar_usuario(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT id, nome, email, telefone FROM usuarios WHERE id = ? LIMIT 1");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $usuario ?: null;
}

function descricao_roupa(array $roupa): string
{
    $titulo = $roupa['titulo'] ?: $roupa['tipo'];
    return $titulo . " - " .
        "tipo: " . ($roupa['tipo'] ?: "não informado") . ", " .
        "tamanho: " . ($roupa['tamanho'] ?: "não informado") . ", " .
        "sexo: " . ($roupa['sexo'] ?: "não informado") . ", " .
        "estado: " . ($roupa['estado'] ?: "não informado") . ", " .
        "local: " . ($roupa['local_doacao'] ?: "não informado");
}

$ids = ids_do_request();
$connRoupas = db_connect('DB_NAME_ROUPAS');
$connUsuarios = db_connect('DB_NAME_USUARIOS');
$roupas = [];
$usuarioInteressado = null;

if ($connRoupas->connect_error || $connUsuarios->connect_error) {
    $erro = "Erro de conexão com o banco.";
} else {
    db_ensure_roupa_schema($connRoupas);
    db_ensure_usuario_schema($connUsuarios);
    $roupas = buscar_roupas($connRoupas, $ids);
    $usuarioInteressado = buscar_usuario($connUsuarios, (int) $_SESSION['usuario_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erro) {
    if (!isset($_POST['aceite_compartilhamento'])) {
        $erro = "Você precisa aceitar o compartilhamento do e-mail e telefone.";
    } elseif (!$usuarioInteressado) {
        $erro = "Não foi possível encontrar seus dados de contato.";
    } else {
        $ativas = array_values(array_filter($roupas, fn($roupa) => (int) $roupa['pausado'] !== 1));

        if (count($ativas) === 0) {
            $erro = "Nenhuma roupa ativa foi encontrada para finalizar.";
        }

        $grupos = [];
        foreach ($ativas as $roupa) {
            $anuncianteId = (int) ($roupa['id_usuario'] ?? 0);
            if ($anuncianteId <= 0) {
                $erro = "Uma das roupas selecionadas não possui anunciante vinculado.";
                break;
            }
            $grupos[$anuncianteId][] = $roupa;
        }

        $idsEnviados = [];

        if (!$erro) {
            foreach ($grupos as $anuncianteId => $roupasDoAnunciante) {
                $anunciante = buscar_usuario($connUsuarios, (int) $anuncianteId);

                if (!$anunciante || empty($anunciante['email'])) {
                $erro = "Não foi possível encontrar o e-mail de um anunciante.";
                    break;
                }

                $itensHtml = "";
                $itensTexto = "";

                foreach ($roupasDoAnunciante as $roupa) {
                    $descricao = descricao_roupa($roupa);
                    $itensHtml .= "<li>" . e($descricao) . "</li>";
                    $itensTexto .= "- " . $descricao . "\n";
                }

                $telefone = $usuarioInteressado['telefone'] ?: "Não informado";
                $nomeInteressado = $usuarioInteressado['nome'] ?: $_SESSION['usuario_nome'];
                $emailInteressado = $usuarioInteressado['email'] ?: "Não informado";

                $mail = new PHPMailer(true);

                try {
                    configure_mailer($mail);
                    $mail->addAddress($anunciante['email']);
                    $mail->isHTML(true);
                    $mail->Subject = "Interesse em doação - Bazar Online";
                    $mail->Body = "
                        <html>
                        <head><meta charset='UTF-8'></head>
                        <body>
                            <p>Olá, " . e($anunciante['nome'] ?? 'anunciante') . ".</p>
                            <p>Uma pessoa demonstrou interesse nas suas peças:</p>
                            <ul>$itensHtml</ul>
                            <p>Dados de contato compartilhados:</p>
                            <p>
                                Nome: " . e($nomeInteressado) . "<br>
                                E-mail: " . e($emailInteressado) . "<br>
                                Telefone: " . e($telefone) . "
                            </p>
                            <p>Entre em contato para combinar a doação.</p>
                        </body>
                        </html>
                    ";
                    $mail->AltBody =
                        "Olá, " . ($anunciante['nome'] ?? 'anunciante') . ".\n\n" .
                        "Uma pessoa demonstrou interesse nas suas peças:\n" .
                        $itensTexto . "\n" .
                        "Dados de contato compartilhados:\n" .
                        "Nome: $nomeInteressado\n" .
                        "E-mail: $emailInteressado\n" .
                        "Telefone: $telefone\n\n" .
                        "Entre em contato para combinar a doação.";
                    $mail->send();

                    foreach ($roupasDoAnunciante as $roupa) {
                        $idsEnviados[] = (int) $roupa['id'];
                    }
                } catch (Exception $e) {
                    $erro = "Não foi possível enviar o e-mail para um dos anunciantes.";
                    break;
                }
            }
        }

        if (!$erro && count($idsEnviados) > 0) {
            $placeholders = implode(',', array_fill(0, count($idsEnviados), '?'));
            $stmtPausa = $connRoupas->prepare("UPDATE roupas SET pausado = 1 WHERE id IN ($placeholders)");

            if ($stmtPausa) {
                $tipos = str_repeat('i', count($idsEnviados));
                $stmtPausa->bind_param($tipos, ...$idsEnviados);
                $stmtPausa->execute();
                $stmtPausa->close();
            }

            $sucesso = true;
            $mensagem = "Doação finalizada. Os anunciantes receberam seus dados de contato por e-mail.";
            $roupas = buscar_roupas($connRoupas, $ids);
            app_log_event(
                'Finalização de doação',
                'Usuário finalizou uma doação e compartilhou dados de contato.',
                (int) $_SESSION['usuario_id'],
                $_SESSION['usuario_nome'],
                $usuarioInteressado['email'] ?? null
            );
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Finalizar Doação</title>
<link rel="stylesheet" href="assets/css/app.css">
</head>

<body class="auth-page">

<div class="app-header">
    <a href="index.html" class="header-link btn-primary">Voltar</a>
    <a href="logout.php" class="header-link btn-light logout-icon" title="Sair" aria-label="Sair">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <path d="M16 17l5-5-5-5"></path>
            <path d="M21 12H9"></path>
        </svg>
    </a>
</div>

<div class="page-main">
    <div class="box donation-box">
        <h2>Finalizar Doação</h2>

        <?php if ($erro): ?>
            <p class="erro"><?= e($erro) ?></p>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <p class="sucesso"><?= e($mensagem) ?></p>
            <a href="index.html" class="form-link">Voltar para o bazar</a>
        <?php elseif (count($roupas) === 0): ?>
            <p>Nenhuma roupa selecionada.</p>
            <a href="index.html" class="form-link">Voltar para o bazar</a>
        <?php else: ?>
            <p>Assim que a doação for finalizada, será enviado um e-mail para o anunciante das peças para que ele entre em contato com você.</p>

            <div class="donation-summary">
                <?php foreach ($roupas as $roupa): ?>
                    <div class="donation-summary-item">
                        <strong><?= e($roupa['titulo'] ?: $roupa['tipo']) ?></strong>
                        <small><?= e(descricao_roupa($roupa)) ?></small>
                        <?php if ((int) $roupa['pausado'] === 1): ?>
                            <span class="admin-badge">Anúncio pausado</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST">
                <input type="hidden" name="ids" value="<?= e(implode(',', $ids)) ?>">

                <div class="terms-check">
                    <label>
                        <input type="checkbox" name="aceite_compartilhamento" value="1" required>
                        Eu aceito que o meu e-mail e meu telefone sejam compartilhados
                    </label>
                </div>

                <button type="submit" class="btn-block">Finalizar a doação</button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
