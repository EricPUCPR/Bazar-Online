
<?php
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperação de senha</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

<div class="card">
    <h2>Cadastre sua nova senha:</h2>
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

    <form id="formSenha" action="processar_recuperacao.php" method="POST">

        <div class="input-group">
            <label for="password">Senha:</label>

            <div class="password-wrapper">
                <input type="password" name="senha" id="password" required placeholder="Digite a sua senha">
                <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
            </div>

            <span id="erro-regex" style="display: none; color:red">A sequência 123 não é permitida.</span>
        </div>

        <div class="input-group">
            <label for="confirmar_senha">Confirmação:</label>

            <div class="password-wrapper">
                <input type="password" name="confirmar_senha" id="confirmar_senha" required placeholder="Confirme a senha">
                <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('confirmar_senha', this)"></i>
            </div>

            <span id="erro-match">As senhas não coincidem.</span>
        </div>

        <button type="submit">CADASTRAR NOVA SENHA</button>
    </form>

    <div class="footer-link">
        Errou a senha?
        <a href="../cadastro_usuario.html">Voltar para o cadastro</a>
    </div>

</div>

<script src="script.js"></script>

</body>
</html>
