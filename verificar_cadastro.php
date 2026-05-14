<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/app.php';
header('Content-Type: application/json; charset=utf-8');

$codigo = trim($_POST['codigo'] ?? '');

if (!preg_match('/^\d{6}$/', $codigo)) {
    echo json_encode(['success' => false, 'mensagem' => 'Código inválido.']);
    exit;
}

// ── Lê dados da sessão — banco ainda não foi tocado ──────────────────────────
$pendente = $_SESSION['cadastro_pendente'] ?? null;

if (!$pendente) {
    echo json_encode(['success' => false, 'mensagem' => 'Sessão expirada. Refaça o cadastro.']);
    exit;
}

if (time() > (int) $pendente['expira']) {
    unset($_SESSION['cadastro_pendente']);
    echo json_encode(['success' => false, 'mensagem' => 'Código expirado. Solicite um novo.']);
    exit;
}

if ($codigo !== (string) $pendente['codigo']) {
    log_atividade(null, "Código de verificação de cadastro incorreto para e-mail: " . ($pendente['email'] ?? '?'));
    echo json_encode(['success' => false, 'mensagem' => 'Código incorreto. Tente novamente.']);
    exit;
}

// ── Código correto → insere no banco agora ────────────────────────────────────
$conn = db_connect('DB_NAME_USUARIOS');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'mensagem' => 'Falha ao criar conta. Tente novamente.']);
    exit;
}

$stmt = $conn->prepare("CALL proc_usuario_criar(?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    error_log("verificar_cadastro: falha ao preparar proc_usuario_criar: " . $conn->error);
    echo json_encode(['success' => false, 'mensagem' => 'Falha ao criar conta. Tente novamente.']);
    exit;
}

$stmt->bind_param(
    "ssssss",
    $pendente['nome'],
    $pendente['email'],
    $pendente['telefone'],
    $pendente['endereco'],
    $pendente['datanascimento'],
    $pendente['senha_hash']
);

if (!$stmt->execute()) {
    error_log("verificar_cadastro: erro ao executar proc_usuario_criar: " . $stmt->error);
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => false, 'mensagem' => 'Falha ao criar conta. Tente novamente.']);
    exit;
}

$result    = $stmt->get_result();
$row       = $result ? $result->fetch_assoc() : null;
$novoId    = $row ? (int) $row['id'] : null;
$stmt->close();
$conn->next_result();
$conn->close();

// ── Limpa a sessão de cadastro e registra log ─────────────────────────────────
unset($_SESSION['cadastro_pendente']);

if ($novoId) {
    log_atividade($novoId, "Conta criada e ativada via código de verificação por e-mail.");
}

echo json_encode(['success' => true, 'mensagem' => 'Conta criada com sucesso! Bem-vindo(a) ao Bazar Online.']);
