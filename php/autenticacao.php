<?php

//include('conexao.php');

//$conexao = new mysqli("localhost:3306", "root", "", "cloud");

    $usuario_email = trim($_POST("email"));
    $usuario_senha = $_POST("senha");

    $email = "usuario_email";
    $senha = "usuario_senha";
    $tabela = "usuario";

    
    $stmt = conexao -> prepare("SELECT $senha FROM $tabela where $email = ?"); //monta pesquisa no banco
    $stmt ->  bind_param("s", $usuario_email); // 
    $stmt -> execute();
    $senha = $stmt -> get_result();

    if ($usuario_senha == $senha){
        echo json_encode(["success" => true, "message" => "Autenticado com sucesso"]);
    } else {
        echo json_encode(["success" => false, "mensage" => "Falha na autenticação"]);
    }


?>