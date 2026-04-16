<?php
// conexao com o banco
$mysqli = new mysqli("localhost", "root", "", "sistema_usuarios");

// ppega o token da URL
$token = $_GET['token'] ?? '';
$erro_token = false;
$sucesso = false;

if (empty($token)) {
    $erro_token = "Token ausente.";
} else {
    // verifica se o token existe e não expirou
    $stmt = $mysqli->prepare("SELECT usuario_email FROM usuario WHERE recuperacao_token = ? AND recuperacao_expira > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $erro_token = "Este link expirou ou é inválido.";
    }
}

// se o formulario for enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$erro_token) {
    $nova_senha = $_POST["senha"];
    
    // cria o hash da senha
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    // atualiza a senha e limpa o token
    $update = $mysqli->prepare("UPDATE usuario SET usuario_senha = ?, recuperacao_token = NULL, recuperacao_expira = NULL WHERE recuperacao_token = ?");
    $update->bind_param("ss", $senha_hash, $token);
    
    if ($update->execute()) {
        $sucesso = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperação de Senha</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            min-height: calc(100vh - 60px);
            background: #b7c7d9;
            padding: 20px;
        }

        .box {
            width: 400px;
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
            color: #666;
            margin-bottom: 20px;
        }

        .regras-senha {
            background: #eef2f6;
            padding: 15px;
            border-radius: 8px;
            text-align: left;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .regras-senha ul {
            margin-top: 5px;
            padding-left: 20px;
        }

        .invalid { color: #b0b0b0; }
        .invalid::marker { content: "✖ "; }
        .valid { color: #28a745; font-weight: bold; }
        .valid::marker { content: "✔ "; }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .box input {
            width: 100%;
            padding: 10px;
            margin: 6px 0;
            border-radius: 6px;
            border: none;
            background: #eef2f6;
            font-size: 14px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #1f6f9f;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 15px;
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
            margin-top: 15px;
            text-decoration: none;
            color: #1f6f9f;
            font-weight: bold;
            font-size: 14px;
        }

        #erro-regex, #erro-match {
            display: none;
            color: red;
            font-size: 12px;
            margin-top: -4px;
            margin-bottom: 10px;
            text-align: left;
        }
    </style>
</head>

<body>

<div class="header">
    <a href="../Login.html" class="login">Login</a>
    <a href="../index.html" class="voltar">Voltar</a>
</div>

<div class="main">
    <div class="box">
        <h2>Nova Senha</h2>
        <p>Digite abaixo sua nova senha:</p>

        <div class="regras-senha">
            <strong>Sua senha deve conter:</strong>
            <ul>
                <li id="req-length" class="invalid">No mínimo 8 caracteres</li>
                <li id="req-seq" class="invalid">Não pode conter sequência 123</li>
                <li id="req-case" class="invalid">Letras maiúsculas e minúsculas</li>
                <li id="req-especial" class="invalid">Caractere especial</li>
            </ul>
        </div>

        <form id="formSenha" method="POST">
            
            <div class="password-wrapper">
                <input type="password" name="senha" id="password" required placeholder="Nova senha">
                <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
            </div>
            <span id="erro-regex">A sequência 123 não é permitida.</span>

            <div class="password-wrapper">
                <input type="password" name="confirmar_senha" id="confirmar_senha" required placeholder="Confirme a senha">
                <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('confirmar_senha', this)"></i>
            </div>
            <span id="erro-match">As senhas não coincidem.</span>

            <button type="submit">Atualizar Senha</button>
        </form>

        <a href="Login.html" class="link">Voltar para o Login</a>
    </div>
</div>

<script>
function togglePassword(id, el) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
        el.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = "password";
        el.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
<script src="script.js"></script>

</body>
</html>