<?php
    require "PHPMailer/src/PHPMailer.php";
    require "PHPMailer/src/Exception.php";
    require "PHPMailer/src/SMTP.php";

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $mail = new PHPMailer();

    //Configuração
    $mail->Mailer = "smtp";
    $mail->IsSMTP();
    $mail->CharSet = "UTF-8";
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true;
    $mail->Host = 'smtp.gmail.com'
    $mail->Port = 465;

    //Detalhes do envio de E-Mail
    $mail->Username = "bazaronline.expcri@gmail.com";
    $mail->Password = "nyrj lbvw rvgb oqzt"
    $mail->SetForm('bazaronline.expcri@gmail.com', "Doe Amor");
    $mail->addAddress("bazaronline.expcri@gmail.com", "");
    $mail->Subject = "Light";
    $mail->msgHTML("<h1></h1>");

    //Chamar biblioteca com $mail->send();
?>
