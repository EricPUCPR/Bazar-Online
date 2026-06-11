<?php
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

$conn = db_connect('DB_NAME_ROUPAS');

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Erro ao conectar no banco de roupas",
        "roupas" => []
    ]);
    exit;
}

db_ensure_roupa_schema($conn);

$sql = "
    SELECT id, titulo, tipo, tamanho, sexo, estado, local_doacao, foto_path, criado_em
    FROM view_roupas_ativas
    ORDER BY id DESC
";

$result = $conn->query($sql);
$roupas = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $roupas[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "roupas" => $roupas
]);
?>
