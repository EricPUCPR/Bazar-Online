<?php
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Confirmar E-Mail</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="card">
        <h2>Confirme seu acesso</h2>
        <p>Digite o e-mail utilizado no cadastro para receber o link de ativação.</p>

        <form>
            <div class="input-group">
                <label for="email">E-mail de confirmação:</label>
                <input type="email" id="email" required placeholder="exemplo@gmail.com">
            </div>

            <button type="submit">Enviar Link de Confirmação</button>
        </form>

        <div class="footer-link">
            Errou o e-mail? <a href="#">Voltar para o cadastro</a>
        </div>
    </div>

</body>
</html>