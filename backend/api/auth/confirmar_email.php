<?php
/**
 * POST /api/auth/confirmar_email.php
 *
 * ETAPA 2 — Valida o código do e-mail usando o JWT Temporário.
 * Insere no banco e devolve o JWT definitivo.
 */

require_once __DIR__ . '/../../config/app.php';

app_cors();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$pendingToken = trim($_POST['pending_token'] ?? '');
$codigo       = trim($_POST['codigo'] ?? '');

if ($pendingToken === '' || $codigo === '') {
    echo json_encode(['success' => false, 'mensagem' => 'Dados incompletos. Reinicie o cadastro.']);
    exit;
}

// ── 1. Abre e Valida o JWT Temporário
$payload = jwt_verify($pendingToken);

// Se o token expirou (passou de 5 min) ou foi adulterado, bloqueia.
if (!$payload || ($payload['scope'] ?? '') !== 'pending_register') {
    echo json_encode(['success' => false, 'mensagem' => 'Sessão expirada ou inválida. Inicie o cadastro novamente.']);
    exit;
}

// ── 2. Valida o Código de 6 dígitos
$hashDigitado = hash('sha256', $codigo);

// Compara o hash do código que o usuário digitou com o hash que guardamos no token
if (!hash_equals($payload['mfa_hash'], $hashDigitado)) {
    echo json_encode(['success' => false, 'mensagem' => 'Código incorreto.']);
    exit;
}

// ── 3. Insere o Usuário no Banco (Código Correto!)
$conn = db_connect('DB_NAME');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'mensagem' => 'Erro de conexão com o banco.']);
    exit;
}

// Garante que não foi cadastrado nos últimos 5 minutos por outra aba
$stmtCheck = $conn->prepare("CALL sp_buscar_usuario_por_email(?)");
$stmtCheck->bind_param("s", $payload['email']);
$stmtCheck->execute();
$jaExiste = $stmtCheck->get_result()->num_rows > 0;
$stmtCheck->close();
while ($conn->next_result()) { }

if ($jaExiste) {
    echo json_encode(['success' => false, 'mensagem' => 'E-mail já cadastrado. Faça login.']);
    exit;
}

$stmt = $conn->prepare("CALL sp_inserir_usuario(?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'mensagem' => 'Erro interno ao criar conta.' . $conn->error]);
    exit;
}

// Pega os dados direto do Payload do Token
$stmt->bind_param(
    "ssssss",
    $payload['nome'],
    $payload['email'],
    $payload['telefone'],
    $payload['endereco'],
    $payload['datanascimento'],
    $payload['senha_hash']
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'mensagem' => 'Erro ao criar a conta no banco.']);
    exit;
}

// Pega o ID que acabou de ser gerado no banco
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$idCadastro = $row ? (int) $row['id'] : 0;
$stmt->close();
while ($conn->next_result()) { }

// Registra a ação no log
app_log_event('Criação de conta', 'Usuário confirmou o e-mail e a conta foi criada.', $idCadastro, null, $payload['nome'], $payload['email']);

// ── 4. Emite o JWT Definitivo para o usuário entrar no site
$token = jwt_generate([
    'sub'   => $idCadastro,
    'nome'  => $payload['nome'],
    'admin' => false,
]);

echo json_encode([
    'success'  => true,
    'token'    => $token,
    'nome'     => $payload['nome'],
    'admin'    => false,
    'mensagem' => 'Cadastro realizado e login efetuado com sucesso!'
]);