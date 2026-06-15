<?php
/**
 * POST /api/roupas/editar.php
 *
 * Edita uma roupa do usuário autenticado:
 *   1. Marca a roupa original como "alterada" (pausado = 2).
 *   2. Cria uma nova roupa com os dados novos e a nova (ou mesma) foto.
 *
 * Header: Authorization: Bearer <token>
 * Body: multipart/form-data
 *   id_original, titulo, tipo, tamanho, sexo, estado, local_doacao, foto (arquivo — obrigatório)
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

$payload    = jwt_require(false);
$id_usuario = (int) $payload['sub'];

$id_original  = (int) ($_POST['id_original'] ?? 0);
$titulo       = trim($_POST['titulo'] ?? '');
$tipo         = trim($_POST['tipo'] ?? '');
$tamanho      = $_POST['tamanho'] ?? '';
$sexo         = $_POST['sexo'] ?? '';
$estado       = $_POST['estado'] ?? '';
$local_doacao = trim($_POST['local_doacao'] ?? '');

if ($id_original <= 0) {
    echo json_encode(['success' => false, 'mensagem' => 'Publicação original não informada.']);
    exit;
}

if ($titulo === '' || $tipo === '' || $tamanho === '' || $sexo === '' || $estado === '' || $local_doacao === '') {
    echo json_encode(['success' => false, 'mensagem' => 'Preencha todos os campos.']);
    exit;
}

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'mensagem' => 'Envie uma nova foto da peça.']);
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

$nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensoesPermitidas[$mime];
$destino     = $uploadDir . '/' . $nomeArquivo;
$fotoPath    = 'uploads/roupas/' . $nomeArquivo;

if (!@move_uploaded_file($foto['tmp_name'], $destino)) {
    echo json_encode(['success' => false, 'mensagem' => 'Não foi possível salvar a foto.']);
    exit;
}

$conn = db_connect('DB_NAME');
if ($conn->connect_error) {
    @unlink($destino);
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Erro ao conectar no banco.']);
    exit;
}

db_ensure_roupa_schema($conn);

// 1. Marcar a original como alterada (pausado = 2)
$stmtAltera = $conn->prepare("CALL sp_marcar_alterada(?, ?)");
$stmtAltera->bind_param("ii", $id_original, $id_usuario);
$stmtAltera->execute();
$resAltera  = $stmtAltera->get_result();
$rowAltera  = $resAltera ? $resAltera->fetch_assoc() : null;
$afetadas   = (int) ($rowAltera['afetadas'] ?? 0);
$stmtAltera->close();
while ($conn->next_result()) { }

if ($afetadas === 0) {
    @unlink($destino);
    $conn->close();
    echo json_encode(['success' => false, 'mensagem' => 'Publicação original não encontrada, já indisponível ou não pertence a você.']);
    exit;
}

// 2. Cadastrar nova roupa com os dados editados
$stmtNova = $conn->prepare("CALL sp_cadastrar_roupa(?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmtNova) {
    @unlink($destino);
    $conn->close();
    echo json_encode(['success' => false, 'mensagem' => 'Erro na preparação da query.']);
    exit;
}

$stmtNova->bind_param("sssssssi", $titulo, $tipo, $tamanho, $sexo, $estado, $local_doacao, $fotoPath, $id_usuario);

if (!$stmtNova->execute()) {
    @unlink($destino);
    $stmtNova->close();
    $conn->close();
    echo json_encode(['success' => false, 'mensagem' => 'Erro ao criar nova publicação.']);
    exit;
}

$stmtNova->close();
while ($conn->next_result()) { }
$conn->close();

echo json_encode(['success' => true, 'mensagem' => 'Roupa atualizada com sucesso! A versão anterior foi marcada como alterada.']);
