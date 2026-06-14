<?php
/**
 * POST /api/roupas/cadastrar.php
 *
 * Cadastra uma nova roupa. Requer JWT de usuário autenticado.
 * Lógica extraída de CadastroRoupas.php.
 *
 * Header: Authorization: Bearer <token>
 * Body: multipart/form-data
 *   titulo, tipo, tamanho, sexo, estado, local_doacao, foto (arquivo)
 *
 * Resposta de sucesso:
 *   { "success": true, "mensagem": "Roupa cadastrada com sucesso!" }
 */

require_once __DIR__ . '/../../config/app.php';

app_cors();
header('Content-Type: application/json');
ini_set('display_errors', '0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

// Exige autenticação JWT (qualquer usuário logado)
$payload    = jwt_require(false);
$id_usuario = (int) $payload['sub'];

$conn = db_connect('DB_NAME');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Erro de conexão com o banco.']);
    exit;
}

db_ensure_roupa_schema($conn);

$titulo      = trim($_POST['titulo'] ?? '');
$tipo        = trim($_POST['tipo'] ?? '');
$tamanho     = $_POST['tamanho'] ?? '';
$sexo        = $_POST['sexo'] ?? '';
$estado      = $_POST['estado'] ?? '';
$local_doacao = trim($_POST['local_doacao'] ?? '');

if ($titulo === '' || $tipo === '' || $tamanho === '' || $sexo === '' || $estado === '' || $local_doacao === '') {
    echo json_encode(['success' => false, 'mensagem' => 'Preencha todos os campos da roupa.']);
    exit;
}

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'mensagem' => 'Envie uma foto da peça de roupa.']);
    exit;
}

$foto     = $_FILES['foto'];
$maxBytes = 5 * 1024 * 1024;

if ($foto['size'] > $maxBytes) {
    echo json_encode(['success' => false, 'mensagem' => 'A foto deve ter no máximo 5MB.']);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($foto['tmp_name']);
$extensoesPermitidas = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

if (!isset($extensoesPermitidas[$mime])) {
    echo json_encode(['success' => false, 'mensagem' => 'Envie uma foto JPG, PNG ou WEBP.']);
    exit;
}

$uploadDir = dirname(__DIR__, 2) . '/storage/uploads/roupas';
if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0777, true)) {
    echo json_encode(['success' => false, 'mensagem' => 'Não foi possível preparar a pasta de fotos.']);
    exit;
}

if (!is_writable($uploadDir)) {
    @chmod($uploadDir, 0777);
}

if (!is_writable($uploadDir)) {
    echo json_encode(['success' => false, 'mensagem' => 'A pasta de fotos não tem permissão de escrita.']);
    exit;
}

$nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensoesPermitidas[$mime];
$destino     = $uploadDir . '/' . $nomeArquivo;
// Caminho relativo para servir via endpoint /api/uploads/
$fotoPath    = 'uploads/roupas/' . $nomeArquivo;

if (!@move_uploaded_file($foto['tmp_name'], $destino)) {
    echo json_encode(['success' => false, 'mensagem' => 'Não foi possível salvar a foto.']);
    exit;
}

$stmt = $conn->prepare("CALL sp_cadastrar_roupa(?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    @unlink($destino);
    echo json_encode(['success' => false, 'mensagem' => 'Erro na preparação da query.']);
    exit;
}

$stmt->bind_param("sssssssi", $titulo, $tipo, $tamanho, $sexo, $estado, $local_doacao, $fotoPath, $id_usuario);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'mensagem' => 'Roupa cadastrada com sucesso!']);
} else {
    @unlink($destino);
    echo json_encode(['success' => false, 'mensagem' => 'Erro ao cadastrar roupa.']);
}

$stmt->close();
while ($conn->next_result()) { }
$conn->close();
