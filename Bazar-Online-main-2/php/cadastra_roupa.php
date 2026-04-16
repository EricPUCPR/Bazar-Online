<?php 
//inicia sessao pho e informa o retorno ao js como json
session_start();
header('Content-Type: application/json');

//importa conexao com o banco de outro arquivo
include('conexao.php');


if ($_SERVER["REQUEST_METHOD"] === "POST"){
    $roupa_tipo = $_POST["tipo"];
    $roupa_tamanho = $_POST["tamanho"];
    $roupa_sexo = $_POST["sexo"];

    if($_SESSION['email']){
        $usuario_email = $_SESSION['email'];
    

    //prepara post dos dados no banco
        $cria_roupa = "INSIRT INTO roupa (roupa_tipo, roupa_tamanho, roupa_sexo, usuario_email) VALUES (?, ?, ?, ?)"; 
        $stmt = $conexao -> prepare($cria_roupa);
        $stmt -> bind_param("ssss", $roupa_tipo, $roupa_tamanho, $roupa_sexo, $usuario_email, );

        if($stmt -> execute()){
            //responde sucesso ao js 
            echo json_encode ([
            "success" => true,
            "mensage" => "Cadastro  da roupa realizado com sucesso!"
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