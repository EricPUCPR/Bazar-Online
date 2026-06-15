<?php
/**
 * POST /api/auth/cadastro.php
 *
 * ETAPA 1 — Envia o código por e-mail e devolve o JWT Temporário.
 * Stateless: Não usa banco de dados para salvar o usuário ainda e NÃO usa $_SESSION.
 */

require_once __DIR__ . '/../../config/app.php';
app_cors();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$vendorBase = __DIR__ . '/PHPMailer/';
require $vendorBase . 'Exception.php';
require $vendorBase . 'PHPMailer.php';
require $vendorBase . 'SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

function normalizarTexto(string $texto): string
{
    $texto = trim($texto);
    $texto = mb_strtolower($texto, 'UTF-8');
    $convertido = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    if ($convertido !== false) {
        $texto = $convertido;
    }
    return preg_replace('/[^a-z0-9 ]/', '', $texto);
}

// ── Coleta dos dados
$nome           = trim($_POST['nome'] ?? '');
$email          = trim($_POST['email'] ?? '');
$telefone       = trim($_POST['telefone'] ?? '');
$endereco       = trim($_POST['endereco'] ?? '');
$datanascimento = trim($_POST['datanascimento'] ?? '');
$senha          = $_POST['senha'] ?? '';
$confirmar      = $_POST['confirmar_senha'] ?? '';
$aceitouTermos  = isset($_POST['termos']);
$recaptcha      = $_POST['g-recaptcha-response'] ?? '';

// ── Validações
$senhaForte = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/";
$regexSeq   = "/(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/";

if (!$aceitouTermos) {
    echo json_encode(['success' => false, 'mensagem' => 'Aceite os termos de uso.']);
    exit;
}
if (!preg_match('/^[a-zA-ZÀ-ÿ\s]{3,}$/u', $nome)) {
    echo json_encode(['success' => false, 'mensagem' => 'O nome deve conter apenas letras e ter no mínimo 3 caracteres.']);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
    echo json_encode(['success' => false, 'mensagem' => 'O formato do e-mail é inválido.']);
    exit;
}

$dataAtual   = new DateTime();
$dataNascObj = DateTime::createFromFormat('Y-m-d', $datanascimento);
$idade       = $dataNascObj ? $dataNascObj->diff($dataAtual)->y : -1;

if (!$dataNascObj || $idade < 18 || $idade > 120 || $dataNascObj > $dataAtual) {
    echo json_encode(['success' => false, 'mensagem' => 'A idade deve ser entre 18 e 120 anos.']);
    exit;
}
if ($senha !== $confirmar) {
    echo json_encode(['success' => false, 'mensagem' => 'As senhas não coincidem.']);
    exit;
}
if (!preg_match($senhaForte, $senha)) {
    echo json_encode(['success' => false, 'mensagem' => 'Senha fraca. Use letras maiúsculas, minúsculas, número e símbolo.']);
    exit;
}
if (preg_match($regexSeq, $senha)) {
    echo json_encode(['success' => false, 'mensagem' => 'A senha não pode conter sequência numérica.']);
    exit;
}

$senhaNorm  = normalizarTexto($senha);
$partesNome = array_filter(preg_split('/\s+/', normalizarTexto($nome)), fn($p) => strlen($p) >= 3);
foreach ($partesNome as $parte) {
    if (strpos($senhaNorm, $parte) !== false) {
        echo json_encode(['success' => false, 'mensagem' => 'A senha não pode conter o seu nome.']);
        exit;
    }
}

if (!validar_recaptcha($recaptcha)) {
    echo json_encode(['success' => false, 'mensagem' => 'Por favor, confirme que você não é um robô.']);
    exit;
}

// ── Verifica e-mail já cadastrado
$conn = db_connect('DB_NAME');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'mensagem' => 'Erro de conexão com o banco.']);
    exit;
}

$stmt = $conn->prepare("CALL sp_buscar_usuario_por_email(?)");
$stmt->bind_param("s", $email);
$stmt->execute();
$existente = $stmt->get_result()->fetch_assoc();
$stmt->close();
while ($conn->next_result()) { }

if ($existente) {
    echo json_encode(['success' => false, 'mensagem' => 'E-mail já cadastrado. Faça login.']);
    exit;
}

// ── Gera o Código, os Hashes e o JWT Temporário
$codigo    = sprintf('%06d', mt_rand(0, 999999));
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);
$mfaHash   = hash('sha256', $codigo);

// Aqui criamos a "Sessão JWT" que vai pro Frontend
$pendingToken = jwt_generate([
    'scope'          => 'pending_register',
    'email'          => $email,
    'nome'           => $nome,
    'telefone'       => $telefone,
    'endereco'       => $endereco,
    'datanascimento' => $datanascimento,
    'senha_hash'     => $senhaHash,
    'mfa_hash'       => $mfaHash
], 300); // Expira em 5 minutos (300 segundos)

// ── Envia o código por e-mail
$mail = new PHPMailer(true);
try {
    configure_mailer($mail);
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Código de Verificação - Bazar Online';
    $mail->Body = "
        <html><head><meta charset='UTF-8'></head>
        <body style='font-family:sans-serif;'>
            <h2 style='color:#1f6f9f;'>Confirmação de Cadastro</h2>
            <p>Seu código de verificação é:</p>
            <p style='font-size:32px;font-weight:bold;letter-spacing:6px;color:#263238;'>{$codigo}</p>
            <p style='color:#666;font-size:13px;'>Este código expira em 5 minutos. Não compartilhe com ninguém.</p>
        </body></html>
    ";
    $mail->AltBody = "Seu código de verificação do Bazar Online: {$codigo}. Expira em 5 minutos.";
    $mail->send();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensagem' => 'Não foi possível enviar o e-mail de validação.']);
    exit;
}

// Devolve o token pendente na resposta
echo json_encode([
    'success'       => true,
    'pending_token' => $pendingToken, 
    'mensagem'      => 'Código de verificação enviado ao e-mail!'
]);