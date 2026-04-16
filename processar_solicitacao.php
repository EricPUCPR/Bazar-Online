<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'mailer_test/PHPMailer/src/Exception.php';
require 'mailer_test/PHPMailer/src/PHPMailer.php';
require 'mailer_test/PHPMailer/src/SMTP.php';

$conn = new mysqli("localhost", "root", "", "sistema_usuarios");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];

    $stmt = $conn->prepare("SELECT usuario_email FROM usuario WHERE usuario_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expira = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $update = $conn->prepare("UPDATE usuario SET recuperacao_token = ?, recuperacao_expira = ? WHERE usuario_email = ?");
        $update->bind_param("sss", $token, $expira, $email);
        $update->execute();

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username = "bazaronline.expcri@gmail.com";
            $mail->Password = "nyrj lbvw rvgb oqzt";
        
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom("bazaronline.expcri@gmail.com", "Doe Amor");
            $mail->addAddress($email);

            $link = "http://localhost/projetodoscria/pagina_recuperacao_de_senha/pagina_recuperacao_de_senha.php?token=" . $token;

            $mail->isHTML(true);
            $mail->Subject = 'Recuperacao de Senha - Doacao de Roupas';
            $mail->Body    = "Para redefinir sua senha, clique no link abaixo:<br><br>
                              <a href='$link'>Redefinir Senha</a><br><br>
                              O link expira em 1 hora.";

            $mail->send();
            echo "Sucesso! Verifique seu e-mail para recuperar a senha.";
        } catch (Exception $e) {
            echo "Erro ao enviar: {$mail->ErrorInfo}";
        }
    } else {
        echo "Este e-mail não está cadastrado.";
    }
}