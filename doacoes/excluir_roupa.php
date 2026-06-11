<?php
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (
    !isset($_SESSION['usuario_id'], $_SESSION['admin_logado'])
    || $_SESSION['admin_logado'] !== true
) {
    echo json_encode([
        "success" => false,
        "message" => "Apenas o admin pode excluir anúncios."
    ]);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Anúncio inválido."
    ]);
    exit;
}

$conn = db_connect('DB_NAME_ROUPAS');

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Erro ao conectar no banco."
    ]);
    exit;
}

db_ensure_roupa_schema($conn);

$fotoPath = null;
$stmtFoto = $conn->prepare("SELECT foto_path FROM roupas WHERE id = ? LIMIT 1");
if ($stmtFoto) {
    $stmtFoto->bind_param("i", $id);
    $stmtFoto->execute();
    $resultFoto = $stmtFoto->get_result();
    $foto = $resultFoto ? $resultFoto->fetch_assoc() : null;
    $fotoPath = $foto['foto_path'] ?? null;
    $stmtFoto->close();
}

$stmt = $conn->prepare("DELETE FROM roupas WHERE id = ?");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Erro ao preparar exclusão."
    ]);
    exit;
}

$stmt->bind_param("i", $id);
$ok = $stmt->execute();
$afetadas = $stmt->affected_rows;
$stmt->close();

if ($ok && $afetadas > 0 && $fotoPath) {
    $caminhoFoto = __DIR__ . '/../' . ltrim($fotoPath, '/');
    if (is_file($caminhoFoto)) {
        @unlink($caminhoFoto);
    }
}

if ($ok && $afetadas > 0) {
    app_log_event(
        'Exclusão de anúncio',
        'Admin excluiu um anúncio de roupa.',
        (int) $_SESSION['usuario_id'],
        $_SESSION['usuario_nome'] ?? null,
        $_SESSION['usuario_email'] ?? null
    );
}

echo json_encode([
    "success" => $ok && $afetadas > 0,
    "message" => $ok && $afetadas > 0 ? "Anúncio excluído." : "Anúncio não encontrado."
]);
?>
