<?php
require_once __DIR__ . '/../../config/app.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/mailer/PHPMailer/src/Exception.php';
require __DIR__ . '/../../vendor/mailer/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../../vendor/mailer/PHPMailer/src/SMTP.php';

$conn = db_connect('DB_NAME_USUARIOS');

if ($conn->connect_error) {
    header("Location: ../login.html?status=erro");
    exit;
}

db_ensure_usuario_schema($conn);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../login.html?status=email_invalido");
        exit;
    }

    $token  = bin2hex(random_bytes(32));
    $expira = date("Y-m-d H:i:s", strtotime('+1 hour'));

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $emailExiste = $result && $result->num_rows > 0;
    $stmt->close();

    if (!$emailExiste) {
        header("Location: ../login.html?status=nao_encontrado");
        exit;
    }

    $updateToken = $conn->prepare("UPDATE usuarios SET recuperacao_token = ?, recuperacao_expira = ? WHERE email = ?");
    if (!$updateToken) {
        header("Location: ../login.html?status=erro");
        exit;
    }
    $updateToken->bind_param("sss", $token, $expira, $email);
    $updateToken->execute();
    $updateToken->close();

    $mail = new PHPMailer(true);
    try {
        configure_mailer($mail);
        $mail->addAddress($email);

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $link = $protocol . "://" . $host . $path . "/pagina_recuperacao_de_senha.php?token=" . $token;

        $mail->isHTML(true);
        $mail->Subject = "Recuperação de Senha";
        $mail->Body = "
            <html>
            <head><meta charset='UTF-8'></head>
            <body>
                <p>Você solicitou a redefinição da sua senha.</p>
                <p>Use o link abaixo para criar uma nova senha:</p>
                <p>
                    <a href='$link'>Redefinir senha</a>
                </p>
                <p>Este link expira em 1 hora.</p>
            </body>
            </html>
        ";
        $mail->AltBody = "Você solicitou a redefinição da sua senha. Use este link: $link . Este link expira em 1 hora.";

        $mail->send();

        header("Location: ../login.html?status=ok");
        exit;

    } catch (Exception $e) {
        header("Location: ../login.html?status=erro");
        exit;
    }
}
?>
