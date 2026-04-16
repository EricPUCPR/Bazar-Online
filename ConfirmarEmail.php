<?php
// Conexão com o banco
$conn = new mysqli("localhost", "root", "", "sistema_usuarios");

// Verifica se o token veio pela URL (Ex: ConfirmarEmail.php?token=123...)
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // 1. Procura o usuário com esse token e status 0 (pendente)
    $stmt = $conn->prepare("SELECT id FROM usuario WHERE token_confirmacao = ? AND status_confirmacao = 0");    $stmt->bind_param("s", $token);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        // 2. Se achou, ativa a conta e remove o token
        $update = $conn->prepare("UPDATE usuario SET status_confirmacao = 1, token_confirmacao = NULL WHERE token_confirmacao = ?");
        $update->bind_param("s", $token);
        
        if ($update->execute()) {
            $msg = "E-mail confirmado com sucesso! Você já pode logar.";
            $classe = "sucesso";
        }
    } else {
        $msg = "Token inválido ou link expirado.";
        $classe = "erro";
    }
} else {
    $msg = "Nenhum token fornecido.";
    $classe = "erro";
}
?>



<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Confirmação de email</title>
    <link rel="stylesheet" href="style.css"> </head>
<body>
    <div class="main">
        <div class="box">
            <h2>Status da Confirmação</h2>
            <p class="<?php echo $classe; ?>"><?php echo $msg; ?></p>
            <br>
            <a href="Login.html" class="link">Ir para o Login</a>
        </div>
    </div>
</body>
</html>


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
    margin-bottom: 10px;
    color: #1f6f9f;
}

.box p {
    font-size: 14px;
    margin-bottom: 15px;
    color: #333;
}

.box input {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 6px;
    border: none;
    background: #eef2f6;
}

button {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
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

.link {
    display: block;
    margin-top: 12px;
    font-size: 14px;
    color: #1f6f9f;
    text-decoration: none;
    font-weight: bold;
}
</style>
</head>

<body>

<div class="header">
    <a href="Login.html" class="login">Login</a>
    <a href="index.html" class="voltar">Voltar</a>
</div>

<div class="main">
    <div class="box">

        <h2>Confirme seu e-mail</h2>
        <p>Digite o e-mail utilizado no cadastro para receber o link de confirmação.</p>

        <form action="../../mailer_test/mailer.php" method="POST">
            <input 
                type="email" 
                name="email" 
                required 
                placeholder="exemplo@gmail.com"
            >

            <button type="submit">Enviar Link</button>
        </form>

        <a href="CadastroUsuarios.php" class="link">
            Errou o e-mail? Voltar para cadastro
        </a>

    </div>
</div>

<script src="confirma_e-mail.js"></script>

</body>
</html>