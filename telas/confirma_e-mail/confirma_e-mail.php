<?php
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Confirmar E-mail</title>
    <link rel="stylesheet" href="style.css">
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

        <button type="submit">Enviar Link de Confirmação</button>

    </form>

    <div class="footer-link">
        Errou o e-mail? 
        <a href="../cadastro_usuario.html">Voltar para o cadastro</a>
    </div>

</div>

<script src="confirma_e-mail.js"></script>

</body>
</html>