<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Cadastro</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial;
}

body {
    background: #e6e6e6;
}

.header {
    height: 60px;
    background: #1f6f9f;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 0 20px;
    gap: 10px;
}

.header a {
    text-decoration: none;
    padding: 8px 14px;
    border-radius: 6px;
    font-weight: bold;
}

.login {
    background: #ffffff;
    color: #1f6f9f;
}

.voltar {
    background: #0d4f73;
    color: white;
}

.main {
    display: flex;
    justify-content: center;
    align-items: center;
    height: calc(100vh - 60px);
    background: #b7c7d9;
}

.box {
    width: 340px;
    background: white;
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.box h2 {
    margin-bottom: 15px;
    color: #1f6f9f;
}

.box input {
    width: 100%;
    padding: 10px;
    margin: 6px 0;
    border-radius: 6px;
    border: none;
    background: #eef2f6;
}

button {
    width: 100%;
    padding: 10px;
    margin-top: 12px;
    background: #1f6f9f;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
}

button:hover {
    background: #0d4f73;
}

.erro {
    color: red;
    font-size: 14px;
    margin-bottom: 10px;
}

.sucesso {
    color: green;
    font-size: 14px;
    margin-bottom: 10px;
}

.link {
    display: block;
    margin-top: 12px;
    text-decoration: none;
    color: #1f6f9f;
    font-weight: bold;
}
</style>
</head>

<body>

<div class="header">
    <a href="Login.html" class="login">Login</a>
    <a href="index.html" class="voltar">Voltar</a>
</div>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "sistema_usuarios");

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $telefone = $_POST["telefone"];
    $endereco = $_POST["endereco"];
    $data_nascimento = $_POST["datanascimento"];
    $senha = $_POST["senha"];
    $confirmar = $_POST["confirmar_senha"];

    if ($senha !== $confirmar) {
        $erro = "As senhas não coincidem!";
    } else {

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nome, email, telefone, endereco, data_nascimento, senha)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $nome, $email, $telefone, $endereco, $data_nascimento, $senhaHash);

        if ($stmt->execute()) {
            $sucesso = "Usuário cadastrado com sucesso!";
        } else {
            $erro = "Erro ao cadastrar!";
        }
    }
}
?>

<div class="main">
    <div class="box">
        <h2>Cadastro</h2>

        <?php if ($erro != "") echo "<p class='erro'>$erro</p>"; ?>
        <?php if ($sucesso != "") echo "<p class='sucesso'>$sucesso</p>"; ?>

        <form method="POST">
            <input type="text" name="nome" placeholder="Nome" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="datanascimento" placeholder="Data de Nascimento" required>
            <input type="text" name="telefone" placeholder="Telefone" required>
            <input type="text" name="endereco" placeholder="Endereço" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <input type="password" name="confirmar_senha" placeholder="Confirmar Senha" required>

            <button type="submit">Cadastrar</button>
        </form>

        <a href="Login.html" class="link">Já tenho cadastro</a>
    </div>
</div>

</body>
</html>