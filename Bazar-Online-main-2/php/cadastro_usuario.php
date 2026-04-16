<?php 
//inicia sessao pho e informa o retorno ao js como json
session_start();
header('Content-Type: application/json');

//importa conexao com o banco de outro arquivo
include('conexao.php');


if ($_SERVER["REQUEST_METHOD"] === "POST"){
    $usuario_nome = $_POST["nome"];
    $usuario_email = $_POST["email"];
    $usuario_nascimento = $_POST["nascimento"];
    $usuario_telefone = $_POST["telefone"];
    $usuario_cpf = $_POST["cpf"];
    $usuario_senha = $_POST["senha1"];

    $regexNome = '/^[a-zA-ZÀ-ÿ\s]+$/';
    $regexEmail = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
    $regexNascimento = '/^\d{2}[-\/]?\d{2}[-\/]?\d{4}$/';
    $regexTelefone = '/^(\+?\d{2,3}\s?|\(\d{2,3}\)\s?)?(\(\d{2}\)\s?|\d{2}\s?)?9\s?\d{4}-?\d{4}$/';
    $regexCpf = '/^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/';
    $regexSenha = '/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#]).{8,}$/';

    if (!preg_match($regexNome, $usuario_nome) || 
        !preg_match($regexEmail, $usuario_email) || 
        !preg_match($regexNascimento, $usuario_nascimento) ||
        !preg_match($regexTelefone, $usuario_telefone) || 
        !preg_match($regexCpf, $usuario_cpf) || 
        !preg_match($regexSenha, $usuario_senha)) {
        
        echo json_encode([
            "success" => false,
            "mensage" => "Dados inválidos. Por favor, preencha o formulário corretamente."    
        ]);
        exit;
    }

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
        $cria_usuario = "INSERT INTO usuario (usuario_nome, usuario_email, usuario_nascimento, usuario_telefone, usuario_cpf, usuario_senha) VALUES (?, ?, ?, ?, ?, ?)"; 
        $stmt = $conexao -> prepare($cria_usuario);
        $stmt -> bind_param("ssssss", $usuario_nome, $usuario_email, $usuario_nascimento, $usuario_telefone, $usuario_cpf, $hash_senha);

        if($stmt -> execute()){
            //guarda dados em sessão local
            $_SESSION["usuario_nome"] = $usuario_nome;
            $_SESSION["usuario_email"] = $usuario_email;
            $_SESSION["usuario_cpf"] = $usuario_cpf;
            $_SESSION["usuario_telefone"] = $usuario_telefone;

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