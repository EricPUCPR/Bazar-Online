<?php
/**
 * POST /api/auth/recuperar_senha.php
 *
 * Solicita o link de recuperação de senha (Solicitacao.php original).
 * Também aceita POST com nova_senha + token para redefinir (pagina_recuperacao.php original).
 *
 * Ação 1 — solicitar link (action=solicitar):
 *   Body: email
 *   Resposta: { "success": true, "mensagem": "..." }
 *
 * Ação 2 — redefinir senha (action=redefinir):
 *   Body: token, senha, confirmar_senha
 *   Resposta: { "success": true, "mensagem": "..." }
 */

require_once __DIR__ . '/../../config/app.php';
app_cors();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$vendorBase = __DIR__ . '/PHPMailer/';
require $vendorBase . 'Exception.php';
require $vendorBase . 'PHPMailer.php';
require $vendorBase . 'SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$conn = db_connect('DB_NAME');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Erro de conexão com o banco.']);
    exit;
}

db_ensure_usuario_schema($conn);

$action = trim($_POST['action'] ?? 'solicitar');

// ─── AÇÃO 1: Solicitar link de recuperação ────────────────────────────────────
if ($action === 'solicitar') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'mensagem' => 'E-mail inválido.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $existe  = $result && $result->num_rows > 0;
    $stmt->close();

    // Não revela se o e-mail existe ou não (proteção contra enumeration)
    if (!$existe) {
        echo json_encode(['success' => true, 'mensagem' => 'Se o e-mail estiver cadastrado, você receberá o link.']);
        exit;
    }

    $token  = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $update = $conn->prepare("UPDATE usuarios SET recuperacao_token = ?, recuperacao_expira = ? WHERE email = ?");
    $update->bind_param("sss", $token, $expira, $email);
    $update->execute();
    $update->close();

    $frontendOrigin = env_value('FRONTEND_ORIGIN', 'http://localhost:8000');
    $link = $frontendOrigin . '/pages/nova-senha.html?token=' . $token;

    $mail = new PHPMailer(true);
    try {
        configure_mailer($mail);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Recuperação de Senha - Bazar Online';
        $mail->Body = "
            <html><head><meta charset='UTF-8'></head>
            <body>
                <p>Você solicitou a redefinição da sua senha.</p>
                <p><a href='{$link}'>Clique aqui para criar uma nova senha</a></p>
                <p>Este link expira em 1 hora.</p>
            </body></html>
        ";
        $mail->AltBody = "Redefina sua senha: {$link}. Expira em 1 hora.";
        $mail->send();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'mensagem' => 'Não foi possível enviar o e-mail.']);
        exit;
    }

    echo json_encode(['success' => true, 'mensagem' => 'Se o e-mail estiver cadastrado, você receberá o link.']);
    exit;
}

// ─── AÇÃO 2: Redefinir senha ──────────────────────────────────────────────────
if ($action === 'redefinir') {
    $token       = trim($_POST['token'] ?? '');
    $novaSenha   = $_POST['senha'] ?? '';
    $confirmar   = $_POST['confirmar_senha'] ?? '';

    if ($token === '') {
        echo json_encode(['success' => false, 'mensagem' => 'Token inválido.']);
        exit;
    }

    // Busca usuário pelo token
    $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE recuperacao_token = ? AND recuperacao_expira > NOW() LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result  = $stmt->get_result();
    $usuario = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$usuario) {
        echo json_encode(['success' => false, 'mensagem' => 'Link expirado ou inválido. Solicite um novo.']);
        exit;
    }

    // Validações de senha
    $senhaForte = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/";
    $regexSeq   = "/(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/";

    if ($novaSenha !== $confirmar) {
        echo json_encode(['success' => false, 'mensagem' => 'As senhas não coincidem.']);
        exit;
    }
    if (!preg_match($senhaForte, $novaSenha)) {
        echo json_encode(['success' => false, 'mensagem' => 'Senha fraca. Use letras maiúsculas, minúsculas, número e símbolo.']);
        exit;
    }
    if (preg_match($regexSeq, $novaSenha)) {
        echo json_encode(['success' => false, 'mensagem' => 'A senha não pode conter sequência numérica.']);
        exit;
    }

    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE usuarios SET senha = ?, recuperacao_token = NULL, recuperacao_expira = NULL WHERE recuperacao_token = ?");
    $update->bind_param("ss", $senhaHash, $token);
    $ok = $update->execute();
    $afetadas = $update->affected_rows;
    $update->close();

    if ($ok && $afetadas > 0) {
        echo json_encode(['success' => true, 'mensagem' => 'Senha atualizada com sucesso! Faça login.']);
    } else {
        echo json_encode(['success' => false, 'mensagem' => 'Erro ao atualizar a senha.']);
    }
    exit;
}

echo json_encode(['success' => false, 'mensagem' => 'Ação inválida.']);
