<?php 

//include('conexao.php');

//$conexao = new mysqli("localhost:3306", "root", "", "cloud");

if ($_SERVER["REQUEST_METHOD"] === "POST"){
    $usuario_nome = $_POST["nome"];
    $usuario_email = $_POST["email"];
    $usuario_telefone = $_POST["telefone"];
    $usuario_cep = $_POST["cep"];
    $usuario_senha1 = $_POST["senha1"];
    $usuario_senha2 = $_POST["senha2"];

    $email_cadastrado = false;

    $email = "usuario_email";
    $tabela = "usuario";

    //procura email no banco de dados
    $stmt = conexao -> prepare("SELECT * FROM $tabela where $email = ?"); //monta pesquisa no banco
    $stmt ->  bind_param("s", $usuario_email); // adciona entrada do usuario a busca negando sqli
    $stmt -> execute();
    $email = $stmt -> get_result();
    if ($email == $usuario_email){
        $email_cadastrado = true;
    }

    if (!$email_cadastrado){
        echo json.encode(["success" => false, "mensagem" => "Usuario já existente"]);
    } else if ($usuario_senha1 !== $usuario_senha2 ){
        echo json.encode(["success" => false, "mensagem" =>"As senhas não conhecidem."]);
    } else {
         echo json.encode(["success" => true, "mensagem" =>"Proceguindo com o cadastrAs senhas não conhecidem.o..."]); 
    }// o programa apenas grava informações ao confirmar email.
}
?>