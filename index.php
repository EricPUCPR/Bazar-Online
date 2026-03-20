<!DOCTYPE html>
<html lang="pt-br">
<head>
    <link rel="stylesheet" href="css/style.css">
    <meta charset="UTF-8">
    <title>Cadastro</title>
</head>
<body>

<?php
$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $telefone = $_POST["telefone"];
    $endereco = $_POST["endereco"];
    $senha = $_POST["senha"];
    $confirmar = $_POST["confirmar_senha"];

    if ($senha !== $confirmar) {
        $erro = "As senhas não coincidem!";
    } else {
        $sucesso = "Usuário cadastrado com sucesso!";
    }
}
?>

<div class="container">
    <h2>Cadastro de Usuário</h2>

    <?php if ($erro != "") echo "<p class='erro'>$erro</p>"; ?>
    <?php if ($sucesso != "") echo "<p class='sucesso'>$sucesso</p>"; ?>

    <form method="POST">
        <input type="text" name="nome" placeholder="Nome" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="telefone" placeholder="Telefone" required>
        <input type="text" name="endereco" placeholder="Endereço" required>
        <input type="password" name="senha" placeholder="Senha" required>
        <input type="password" name="confirmar_senha" placeholder="Confirmar Senha" required>

        <button type="submit">Cadastrar</button>
    </form>

    <a href="esqueceu_senha.php">Esqueceu sua senha?</a>
</div>

</body>
</html>