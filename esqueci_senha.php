<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
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
            color: #666;
            margin-bottom: 20px;
            line-height: 1.4;
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

        .link {
            display: block;
            margin-top: 15px;
            text-decoration: none;
            color: #1f6f9f;
            font-weight: bold;
            font-size: 14px;
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
        <h2>Recuperar Senha</h2>
        <p>Insira seu e-mail cadastrado e enviaremos um link para você criar uma nova senha.</p>

        <form action="processar_solicitacao.php" method="POST">
            <input type="email" name="email" placeholder="Digite seu e-mail" required>
            <button type="submit">Enviar Link de Recuperação</button>
        </form>

        <a href="Login.html" class="link">Lembrei minha senha</a>
    </div>
</div>

</body>
</html>