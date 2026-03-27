<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $senha = $_POST['senha'];
    $confirmar = $_POST['confirmar_senha'];


    if ($_senha !== $confimar){
        $erro = "As senhas não coincidem. Por favor, tente novamente.";
    } else {
        echo "Senha alterada com sucesso";

    }
    
}
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
        <?php if(isset($mensagem))echo $mensagem; ?>
        <p>Digite abaixo sua nova senha:</p>

        <form method="POST">
            <div class="input-group">
                <label for="password"> Senha:</label>
                <input type="password" id="password" required placeholder="digite a sua senha:">
               
            </div>
            <div class="input-group">
                <label for="confirmar_senha"> Confirmacao de senha:</label>
                <input type="password" id="confirmar_senha" required placeholder="confirme a sua senha:">



            </div>

            <button type="submit">CADASTRAR NOVA SENHA</button>
        </form>

        <div class="footer-link">
            Errou a senha? <a href="../cadastro_usuario.html">Voltar para o cadastro</a>
        </div>
    </div>

</body>
</html>
