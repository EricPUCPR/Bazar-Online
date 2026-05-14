<?php
session_start();
require_once __DIR__ . '/config/app.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/mailer/PHPMailer/src/Exception.php';
require 'vendor/mailer/PHPMailer/src/PHPMailer.php';
require 'vendor/mailer/PHPMailer/src/SMTP.php';

header('Content-Type: application/json');

$conn = db_connect('DB_NAME_USUARIOS');

if ($conn->connect_error) {
    echo json_encode(["success" => false, "mensagem" => "Erro no banco"]);
    exit;
}

db_ensure_usuario_schema($conn);

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
    echo json_encode([
        "success" => false,
        "mensagem" => "E-mail ou senha inválidos."
    ]);
    exit;
}

// Busca usuário por email.
$stmt = $conn->prepare("SELECT id, nome, email, senha, email_verificado, is_admin FROM usuarios WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$userEncontrado = $result ? $result->fetch_assoc() : null;
$stmt->close();

if ($userEncontrado && (int) ($userEncontrado['is_admin'] ?? 0) === 1) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Conta admin. Acesse pela URL exclusiva do administrador."
    ]);
    exit;
}

if ($userEncontrado && password_verify($senha, $userEncontrado['senha'])) {
    if ((int)$userEncontrado['email_verificado'] !== 1) {
        echo json_encode([
            "success" => false,
            "mensagem" => "E-mail não validado. Verifique seu e-mail antes de entrar."
        ]);
        exit;
    }

    $userId   = $userEncontrado['id'];
    $userNome  = $userEncontrado['nome'];
    $userEmail = $userEncontrado['email'];

    $codigo2fa = (string) random_int(100000, 999999);
    $expiraEm  = time() + (5 * 60);

    $mail = new PHPMailer(true);
    try {
        configure_mailer($mail);
        $mail->addAddress($userEmail);
        $mail->isHTML(true);
        $mail->Subject = "Código de verificação - Bazar Online";
        $mail->Body = "
            <html>
            <head><meta charset='UTF-8'></head>
            <body>
                <p>Seu código de verificação é:</p>
                <p>$codigo2fa</p>
                <p>Este código expira em 5 minutos.</p>
            </body>
            </html>
        ";
        $mail->AltBody = "Seu código de verificação é: $codigo2fa. Este código expira em 5 minutos.";
        $mail->send();
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "mensagem" => "Não foi possível enviar o código de verificação."
        ]);
        exit;
    }

    unset(
        $_SESSION['usuario_id'],
        $_SESSION['usuario_nome'],
        $_SESSION['usuario_email'],
        $_SESSION['admin_logado'],
        $_SESSION['admin_2fa_user_id'],
        $_SESSION['admin_2fa_user_nome'],
        $_SESSION['admin_2fa_user_email'],
        $_SESSION['admin_2fa_codigo'],
        $_SESSION['admin_2fa_expira']
    );
    $_SESSION['2fa_user_id']   = $userId;
    $_SESSION['2fa_user_nome'] = $userNome;
    $_SESSION['2fa_user_email'] = $userEmail;
    $_SESSION['2fa_codigo']    = $codigo2fa;
    $_SESSION['2fa_expira']    = $expiraEm;

    echo json_encode([
        "success"    => true,
        "two_factor" => true,
        "mensagem"   => "Código de verificação enviado para seu e-mail."
    ]);

} else {
    log_atividade(null, "Tentativa de login falhou para o e-mail: $email");
    echo json_encode([
        "success"  => false,
        "mensagem" => "E-mail ou senha inválidos."
    ]);
}
?>
