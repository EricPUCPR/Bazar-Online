<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro</title>

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

        .container {
            background-color: #0d6efd;
            padding: 40px;
            border-radius: 10px;
            width: 320px;
            text-align: center;
        }

        h2 {
            color: white;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: none;
            border-radius: 5px;
        }

        /* BOTÃO CENTRALIZADO */
        button {
            width: 150px;
            padding: 10px;
            margin: 15px auto 0 auto;
            display: block;
            background-color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        .erro {
            color: #ffcccc;
            font-size: 14px;
        }

        .sucesso {
            color: #ccffcc;
            font-size: 14px;
        }

        a {
            display: block;
            margin-top: 15px;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>

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
            $erro = "Erro ao cadastrar: " . $conn->error;
        }
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
        <input type="text" name="datanascimento" placeholder="Data de Nascimento" required>
        <input type="text" name="telefone" placeholder="Telefone" required>
        <input type="text" name="endereco" placeholder="Endereço" required>
        <input type="password" name="senha" placeholder="Senha" required>
        <input type="password" name="confirmar_senha" placeholder="Confirmar Senha" required>

        <button type="submit">Cadastrar</button>
    </form>

    <a href="Login.html">Já tenho cadastro</a>
</div>

</body>
</html>