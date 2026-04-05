<?php 
//inicia sessao pho e informa o retorno ao js como json
session_start();
header('Content-Type: application/json');

//importa conexao com o banco de outro arquivo
include('conexao.php');


if ($_SERVER["REQUEST_METHOD"] === "POST"){
    $usuario_nome = $_POST["nome"];
    $usuario_email = $_POST["email"];
    $usuario_telefone = $_POST["telefone"];
    $usuario_cep = $_POST["cep"];
    $usuario_senha1 = $_POST["senha1"];
    $usuario_senha2 = $_POST["senha2"];

    //valida sintaxe do email
    if (!preg_match("/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i", $email)) {
            echo json_encode(["success" => false, "mensage" => "E-mail inválido!"]);
            exit;
    }

    //guarda dados em sessão local
    $_SESSION["usuario_nome"] = $usuario_nome;
    $_SESSION["usuario_email"] = $usuario_email;
    $_SESSION["usuario_cep"] = $usuario_cep;
    $_SESSION["usuario_telefne"] = $usuario_telefone;
}
    //mensagem de sucesso ao js
    echo json_encode([
        "success" => true, 
        "mensage" => "Cadastro realizado com sucesso! Bem-vindo, $usuario_nome."
    ]);

/*


//$conexao = new mysqli("localhost:3306", "root", "", "cloud");



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
*/
?>