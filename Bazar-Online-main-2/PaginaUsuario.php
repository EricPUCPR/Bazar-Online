<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$conn = new mysqli("localhost", "root", "", "sistema_usuarios");

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$id_usuario = 1;

$sql = "SELECT * FROM usuarios WHERE id = $id_usuario";
$result = $conn->query($sql);

if (!$result) {
    die("Erro na query: " . $conn->error);
}

$user = $result->fetch_assoc();

if (!$user) {
    die("Usuário não encontrado no banco!");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Perfil</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial;
}

body {
    background: #e6e6e6;
}

.header {
    height: 60px;
    background: #1f6f9f;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 0 20px;
}

.voltar {
    background: #ffffff;
    color: #1f6f9f;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
}

.voltar:hover {
    background: #e6e6e6;
}

.main {
    display: flex;
    height: calc(100vh - 60px);
}

.sidebar {
    width: 80px;
    background: #1f6f9f;
}

.content {
    flex: 1;
    padding: 30px;
    background: #b7c7d9;
    display: flex;
    justify-content: center;
    align-items: center;
}

.card {
    background: #ffffff;
    padding: 30px;
    border-radius: 12px;
    width: 350px;
    text-align: center;
}

.card h2 {
    margin-bottom: 20px;
    color: #1f6f9f;
}

.info {
    background: #eef2f6;
    padding: 10px;
    margin: 10px 0;
    border-radius: 6px;
    text-align: left;
}

button {
    width: 100%;
    padding: 10px;
    margin-top: 15px;
    border: none;
    border-radius: 6px;
    background: #1f6f9f;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #0d4f73;
}
</style>
</head>

<body>

<div class="header">
    <a href="index.html" class="voltar">Voltar</a>
</div>

<div class="main">
    <div class="sidebar"></div>
    <div class="content">

        <div class="card">
            <h2>Perfil do Usuário</h2>

            <div class="info">
                <strong>Nome:</strong> <?php echo $user['nome']; ?>
            </div>

            <div class="info">
                <strong>Email:</strong> <?php echo $user['email']; ?>
            </div>

            <div class="info">
                <strong>Telefone:</strong> <?php echo $user['telefone']; ?>
            </div>

            <div class="info">
                <strong>Endereço:</strong> <?php echo $user['endereco']; ?>
            </div>

            <div class="info">
                <strong>Data de Nascimento:</strong> <?php echo $user['datanascimento']; ?>
            </div>

            <form action="excluir.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                <button type="submit">Excluir Conta</button>
            </form>

        </div>

    </div>

</div>

</body>
</html>