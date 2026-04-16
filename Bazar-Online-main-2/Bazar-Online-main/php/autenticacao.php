<?php
//inicia sessao pho e informa o retorno ao js como json
session_start();
header("Content-Type: application/json");

//importa conexao com o banco de outro arquivo
include('conexao.php');

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $usuario_email = trim($_POST("email"));
    $usuario_senha = $_POST("senha");
    

    $stmt = $conexao -> prepare("SELECT usuario_senha, usuario_cep, usuario_nome, usuario_telefone FROM $tabela where $email = ?"); //monta pesquisa no banco
    $stmt ->  bind_param("s", $usuario_email); // adciona entrada do usuario a busca negando SQLI
    $stmt -> execute();
    $usuario = $stmt -> get_result();

    //checa se usuario encontrado e transforma resultado em lista digerivel pelo php
    if($usuario = $usuario -> fetch_assoc()){
        //se a senha encontrada for igual a senha digitada:
        if(password_verify($usuario_senha, $usuario["usuario_senha"])){
            //guarda dados em sessão
            $_SESSION["usuario_nome"] = $usuario["usuario_nome"];
            $_SESSION["usuario_email"] = $usuario["usuario_email"];
            $_SESSION["usuario_cep"] = $usuario["usuario_cep"];
            $_SESSION["usuario_telefne"] = $usuario["usuario_telefone"];

            //responde sucesso ao js 
            echo json_encode([
                "success" => true, 
                "mensagem" => "Autenticado com sucesso!"
            ]);
        } else {
            // responde erro ao js
            echo json_encode([
                "success" => false,
                "mensagem" => "Usuario ou senha incorretos!"
            ]);
        }
    } else{
        // responde erro ao js
        echo json_encode([
            "success" => false, 
            "mensagem" => "Usuario ou senha incorretos!"
        ]);

    }
    exit;
}
?> 