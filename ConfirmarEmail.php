<?php
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Confirmar E-mail</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            margin: 0;
            font-family: Arial;
            background-color: #7fa6d6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .card {
            background-color: #0d6efd;
            padding: 40px;
            border-radius: 10px;
            width: 320px;
            text-align: center;
        }

        h2 {
            color: white;
            margin-bottom: 15px;
        }

        p {
            color: white;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .input-group {
            text-align: left;
        }

        label {
            color: white;
            display: block;
            margin-bottom: 5px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: none;
            border-radius: 5px;
        }

        button {
            width: 150px;
            padding: 10px;
            margin: 10px auto 0 auto;
            display: block;
            background-color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        .footer-link {
            margin-top: 15px;
            font-size: 14px;
            color: white;
        }

        .footer-link a {
            color: white;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="card">

    <h2>Confirme seu e-mail</h2>
    <p>Digite o e-mail utilizado no cadastro para receber o link de confirmação.</p>

    <form action="../../mailer_test/mailer.php" method="POST">

        <div class="input-group">
            <label for="email">E-mail de confirmação:</label>
            <input 
                type="email" 
                name="email" 
                id="email" 
                required 
                placeholder="exemplo@gmail.com"
            >
        </div>

        <button type="submit">Enviar Link</button>

    </form>

    <div class="footer-link">
        Errou o e-mail? 
        <a href="../cadastro_usuario.html">Voltar para o cadastro</a>
    </div>

</div>

<script src="confirma_e-mail.js"></script>

</body>
</html>