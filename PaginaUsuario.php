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
body {
    margin: 0;
    font-family: Arial;
    background-color: #7fa6d6;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    display: flex;
    justify-content: center;
    align-items: center;
}

.card {
    background-color: #0d6efd;
    padding: 40px;
    border-radius: 10px;
    width: 320px;
    text-align: center;
    color: white;
}

.card h2 {
    margin-bottom: 20px;
}

.info {
    background-color: rgba(255, 255, 255, 0.2);
    padding: 10px;
    margin: 10px 0;
    border-radius: 5px;
}

button {
    width: 150px;
    padding: 10px;
    margin: 15px auto 0 auto;
    display: block;
    border: none;
    border-radius: 5px;
    background-color: white;
    color: black;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background-color: #e6e6e6;
}
</style>
</head>

<body>

<div class="container">
    <div class="card">
        <h2>Perfil do Usuário</h2>

        <div class="info">
            <strong>Nome:</strong> <?php echo $user['nome']; ?>
        </div>

        <div class="info">
            <strong>Email:</strong> <?php echo $user['email']; ?>
        </div>

        <form action="excluir.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
            <button type="submit">Excluir Conta</button>
        </form>

    </div>
</div>

</body>
</html>