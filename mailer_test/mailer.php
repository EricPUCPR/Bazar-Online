<?php

require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/Exception.php";
require "PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if(isset($_POST['email'])){

    $email = $_POST['email'];

    $mail = new PHPMailer(true);

    try {

        //Configuração SMTP
        $mail->isSMTP();
        $mail->CharSet = "UTF-8";
        $mail->SMTPAuth = true;

        $mail->SMTPSecure = 'ssl';
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 465;

        //Login Gmail
        $mail->Username = "bazaronline.expcri@gmail.com";
        $mail->Password = "nyrj lbvw rvgb oqzt";

        //Remetente
        $mail->setFrom("bazaronline.expcri@gmail.com", "Doe Amor");

        //Destinatário
        $mail->addAddress($email);

        //Conteúdo do email
        $mail->isHTML(true);
        $mail->Subject = "Confirmação de conta";

        $mail->Body = "
        <h2>Confirme sua conta</h2>
        <p>Clique no link abaixo para confirmar seu e-mail:</p>
        <a href='http://localhost/bazar/index.html'>
        Confirmar e-mail
        </a>
        ";

        $mail->send();

        echo "<h2>Email enviado com sucesso!</h2>";
        echo "<p>Verifique sua caixa de entrada.</p>";

    } catch (Exception $e) {

        echo "Erro ao enviar email: {$mail->ErrorInfo}";

    }

}

?>