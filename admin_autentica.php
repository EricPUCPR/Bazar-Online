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
    echo json_encode([
        "success" => false,
        "mensagem" => "Erro no banco"
    ]);
    exit;
}

db_ensure_usuario_schema($conn);

$email = trim($_POST['email'] ?? '');
$metodo = $_POST['metodo'] ?? 'telegram';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Digite um e-mail válido."
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        id,
        nome,
        email,
        email_verificado,
        is_admin,
        telegram_chat_id,
        pergunta_seguranca,
        resposta_seguranca_hash
    FROM usuarios
    WHERE email = ? AND is_admin = 1
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Erro ao preparar autenticação admin."
    ]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$admin) {
    echo json_encode([
        "success" => false,
        "mensagem" => "E-mail não autorizado para login admin."
    ]);
    exit;
}

if ((int) $admin['email_verificado'] !== 1) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Valide o e-mail da conta admin antes de entrar."
    ]);
    exit;
}

if ($metodo === 'pergunta') {
    if (empty($admin['pergunta_seguranca']) || empty($admin['resposta_seguranca_hash'])) {
        echo json_encode([
            "success" => false,
            "mensagem" => "Este admin não possui pergunta de segurança cadastrada."
        ]);
        exit;
    }

    $_SESSION['admin_pergunta_user_id'] = (int) $admin['id'];
    $_SESSION['admin_pergunta_user_nome'] = $admin['nome'];
    $_SESSION['admin_pergunta_user_email'] = $admin['email'];
    $_SESSION['admin_pergunta_hash'] = $admin['resposta_seguranca_hash'];

    echo json_encode([
        "success" => true,
        "pergunta" => true,
        "texto_pergunta" => $admin['pergunta_seguranca'],
        "mensagem" => "Responda a pergunta de segurança."
    ]);
    exit;
}

$codigo = (string) random_int(100000, 999999);
$expiraEm = time() + (5 * 60);

if ($metodo === 'telegram') {

    if (empty($admin['telegram_chat_id'])) {
        echo json_encode([
            "success" => false,
            "mensagem" => "Este admin não possui Telegram cadastrado."
        ]);
        exit;
    }

    $enviado = enviar_telegram(
        $admin['telegram_chat_id'],
        "Código de login admin - Bazar Online: {$codigo}\nEste código expira em 5 minutos."
    );

    if (!$enviado) {
        echo json_encode([
            "success" => false,
            "mensagem" => "Não foi possível enviar o código pelo Telegram."
        ]);
        exit;
    }

} else {

    $mail = new PHPMailer(true);

    try {
        configure_mailer($mail);
        $mail->addAddress($admin['email']);
        $mail->isHTML(true);
        $mail->Subject = "Código de login admin - Bazar Online";
        $mail->Body = "
            <html>
            <head><meta charset='UTF-8'></head>
            <body>
                <p>Seu código de login admin é:</p>
                <p>$codigo</p>
                <p>Este código expira em 5 minutos.</p>
            </body>
            </html>
        ";

        $mail->AltBody = "Seu código de login admin é: $codigo. Este código expira em 5 minutos.";
        $mail->send();

    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "mensagem" => "Não foi possível enviar o código por e-mail."
        ]);
        exit;
    }
}

unset(
    $_SESSION['usuario_id'],
    $_SESSION['usuario_nome'],
    $_SESSION['usuario_email'],
    $_SESSION['admin_logado'],
    $_SESSION['2fa_user_id'],
    $_SESSION['2fa_user_nome'],
    $_SESSION['2fa_user_email'],
    $_SESSION['2fa_codigo'],
    $_SESSION['2fa_expira']
);

$_SESSION['admin_2fa_user_id'] = (int) $admin['id'];
$_SESSION['admin_2fa_user_nome'] = $admin['nome'];
$_SESSION['admin_2fa_user_email'] = $admin['email'];
$_SESSION['admin_2fa_codigo'] = $codigo;
$_SESSION['admin_2fa_expira'] = $expiraEm;

echo json_encode([
    "success" => true,
    "mensagem" => "Código enviado para o e-mail,s se cadastrado."
]);
?>