<?php
/**
 * GET /api/roupas/listar.php
 *
 * Retorna todas as roupas ativas.
 * Não requer autenticação.
 *
 * Query params (opcionais):
 *   tamanho, sexo, tipo, q (busca por texto no título/tipo)
 *
 * Resposta:
 *   { "success": true, "roupas": [ {...}, ... ] }
 */

require_once __DIR__ . '/../../config/app.php';

app_cors();
header('Content-Type: application/json');

$conn = db_connect('DB_NAME');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Erro ao conectar no banco.', 'roupas' => []]);
    exit;
}

db_ensure_roupa_schema($conn);

// Filtros opcionais
$tamanho = trim($_GET['tamanho'] ?? '');
$sexo    = trim($_GET['sexo'] ?? '');
$tipo    = trim($_GET['tipo'] ?? '');
$q       = trim($_GET['q'] ?? '');

$stmt = $conn->prepare("CALL sp_listar_roupas(?, ?, ?, ?)");
$stmt->bind_param("ssss", $tamanho, $sexo, $tipo, $q);
$stmt->execute();
$result = $stmt->get_result();
$roupas = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $roupas[] = $row;
    }
}

$stmt->close();
while ($conn->next_result()) { }
$conn->close();

echo json_encode(['success' => true, 'roupas' => $roupas]);
