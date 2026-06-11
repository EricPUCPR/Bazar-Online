<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
    <link rel="stylesheet" href="../../assets/css/app.css">
</head>

<body class="auth-page">

<div class="app-header">
    <a href="../login.html" class="header-link btn-light">Login</a>
    <a href="../../index.html" class="header-link btn-primary">Voltar</a>
</div>

<div class="page-main">
    <div class="box">
        <h2>Recuperar Senha</h2>
        <p>Insira seu e-mail cadastrado e enviaremos um link para você criar uma nova senha.</p>

        <form action="solicitar_redefinicao.php" method="POST">
            <input type="email" name="email" placeholder="Digite seu e-mail" required>
            <button type="submit" class="btn-block">Enviar Link de Recuperação</button>
        </form>

        <a href="../login.html" class="form-link">Voltar</a>
    </div>
</div>

</body>
</html>
