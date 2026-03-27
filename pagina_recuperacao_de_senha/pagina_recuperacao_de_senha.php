<?php
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperação de senha</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="card">
        
        <h2>Cadastre sua nova senha:</h2>
        <p>Digite abaixo sua nova senha:</p>
        <form action="processar_recuperacao.php" method="POST">
            <div class="input-group">
                <label for="password"> Senha:</label>
                <input type="password" name ="senha"id="password" required placeholder="Digite a sua senha:">
                <span id ="erro-regex" style ="color: red; display: none;">A sequencia 123 não é permitida.</span>
               
            </div>
            <div class="input-group">
                <label for="confirmar_senha"> Confirmacao de senha:</label>
                <input type="password" name ="confirmar_senha"id="confirmar_senha" required placeholder="confirme a sua senha:">

            </div>
            
            <button type="submit">CADASTRAR NOVA SENHA</button>
        </form>

        <div class="footer-link">
            Errou a senha? <a href="../cadastro_usuario.html">Voltar para o cadastro</a>
        </div>
        <script src ="script.js"></script>
    </div>

</body>
</html>
