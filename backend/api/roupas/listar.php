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

$where  = ['pausado = 0'];
$params = [];
$types  = '';

if ($tamanho !== '') {
    $where[]  = 'tamanho = ?';
    $params[] = $tamanho;
    $types   .= 's';
}
if ($sexo !== '') {
    $where[]  = 'sexo = ?';
    $params[] = $sexo;
    $types   .= 's';
}
if ($tipo !== '') {
    $where[]  = 'tipo LIKE ?';
    $params[] = '%' . $tipo . '%';
    $types   .= 's';
}
if ($q !== '') {
    $where[]  = '(titulo LIKE ? OR tipo LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $types   .= 'ss';
}

$sql = "SELECT id, titulo, tipo, tamanho, sexo, estado, local_doacao, foto_path, criado_em
        FROM roupas
        WHERE " . implode(' AND ', $where) . "
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$roupas = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $roupas[] = $row;
    }
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'roupas' => $roupas]);
