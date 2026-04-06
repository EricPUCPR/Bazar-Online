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
    $usuario_senha = $_POST["senha1"];

    //procura email no banco de dados
    $stmt = $conexao -> prepare("SELECT * FROM usuario where usuario_email = ?"); //monta pesquisa no banco
    $stmt ->  bind_param("s", $usuario_email); // adciona entrada do usuario a busca negando sqli
    $stmt -> execute();
    $usuario = $stmt -> get_result();

    //checa se usuario encontrado
    if($usuario = $usuario -> fetch_assoc()){
        echo json_encode([
            "success" => false,
            "mensage" => "Usuario já cadastrado, redirecionando para a pagina de login"    
        ]);
    } else {

        //cria hash da senha com salt
        $hash_senha = password_hash($usuario_senha, PASSWORD_DEFAULT);


        //prepara post dos dados no banco
        $cria_usuario = "INSIRT INTO usuario (usuario_nome, usuario_email, usuario_telefone, usuario_cep, usuario_senha) VALUES (?, ?, ?, ?, ?)"; 
        $stmt = $conexao -> prepare($cria_usuario);
        $stmt -> bind_param("sssss", $usuario_nome, $usuario_email, $usuario_telefone, $usuario_cep, $hash_senha);

        if($stmt -> execute()){
            //guarda dados em sessão local
            $_SESSION["usuario_nome"] = $usuario_nome;
            $_SESSION["usuario_email"] = $usuario_email;
            $_SESSION["usuario_cep"] = $usuario_cep;
            $_SESSION["usuario_telefne"] = $usuario_telefone;

            //responde sucesso ao js 
            echo json_encode ([
            "success" => true,
            "mensage" => "Cadastro realizado com sucesso! Bem-vindo, $usuario_nome."
        ]);
        } else{
            //responde erro ao js 
            echo json_encode ([
            "success" => false,
            "mensage" => "Falha ao salvar dados, tente novamente mais tarde ou entre em contato com o suporte"
        ]);
        }

        
    }

    
}
?>