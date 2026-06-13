<?php
/**
 * POST /api/auth/cadastro.php
 *
 * Cria um novo usuário ou reenvia o e-mail de confirmação para um cadastro pendente.
 * Lógica extraída de CadastroUsuarios.php.
 *
 * Body: multipart/form-data
 *   nome, email, datanascimento, telefone, endereco, senha, confirmar_senha,
 *   termos (presente = aceito), g-recaptcha-response
 *
 * Resposta de sucesso:
 *   { "success": true, "mensagem": "E-mail de confirmação enviado." }
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

$conn = db_connect('DB_NAME');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Erro de conexão com o banco.']);
    exit;
}

db_ensure_usuario_schema($conn);

$nome           = $_POST['nome'] ?? '';
$email          = trim($_POST['email'] ?? '');
$telefone       = $_POST['telefone'] ?? '';
$endereco       = $_POST['endereco'] ?? '';
$datanascimento = $_POST['datanascimento'] ?? '';
$senha          = $_POST['senha'] ?? '';
$confirmar      = $_POST['confirmar_senha'] ?? '';
$aceitouTermos  = isset($_POST['termos']);
$recaptcha      = $_POST['g-recaptcha-response'] ?? '';

// Validações
$senhaForte  = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/";
$regexSeq    = "/(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/";
$senhaNorm   = normalizarTexto($senha);

$partesNome = [];
foreach (preg_split('/\s+/', normalizarTexto($nome)) as $parte) {
    if (strlen($parte) >= 3) {
        $partesNome[] = $parte;
    }
}
$contemNome = false;
foreach ($partesNome as $parte) {
    if (strpos($senhaNorm, $parte) !== false) {
        $contemNome = true;
        break;
    }
}

$dataAtual  = new DateTime();
$dataNascObj = DateTime::createFromFormat('Y-m-d', $datanascimento);
$idade       = $dataNascObj ? $dataNascObj->diff($dataAtual)->y : -1;

if (!$aceitouTermos) {
    echo json_encode(['success' => false, 'mensagem' => 'Aceite os termos de uso.']);
    exit;
}
if (!preg_match('/^[a-zA-ZÀ-ÿ\s]{8,}$/u', $nome)) {
    echo json_encode(['success' => false, 'mensagem' => 'O nome deve conter apenas letras e ter no mínimo 8 caracteres.']);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9._]+@[a-zA-Z]+(\.[a-zA-Z]+)+$/', $email)) {
    echo json_encode(['success' => false, 'mensagem' => 'O formato do e-mail é inválido.']);
    exit;
}
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
if ($contemNome) {
    echo json_encode(['success' => false, 'mensagem' => 'A senha não pode conter o seu nome.']);
    exit;
}
if (!validar_recaptcha($recaptcha)) {
    echo json_encode(['success' => false, 'mensagem' => 'Por favor, confirme que você não é um robô.']);
    exit;
}

// Verifica e-mail existente
$idExistente = null;
$jaVerificado = 0;
$stmtBusca = $conn->prepare("SELECT id, email_verificado FROM usuarios WHERE email = ? LIMIT 1");
if ($stmtBusca) {
    $stmtBusca->bind_param("s", $email);
    $stmtBusca->execute();
    $resBusca = $stmtBusca->get_result();
    if ($resBusca && $resBusca->num_rows > 0) {
        $existente    = $resBusca->fetch_assoc();
        $idExistente  = (int) $existente['id'];
        $jaVerificado = (int) ($existente['email_verificado'] ?? 0);
    }
    $stmtBusca->close();
}

if ($idExistente && $jaVerificado === 1) {
    echo json_encode(['success' => false, 'mensagem' => 'E-mail já cadastrado. Faça login.']);
    exit;
}

$senhaHash          = password_hash($senha, PASSWORD_DEFAULT);
$tokenConfirmacao   = bin2hex(random_bytes(32));
$expiraConfirmacao  = date('Y-m-d H:i:s', strtotime('+24 hours'));
$idCadastro         = $idExistente;
$cadastroNovo       = !$idExistente;

// URL de confirmação (frontend serve a página; o frontend chama o backend com o token)
$frontendOrigin = env_value('FRONTEND_ORIGIN', 'http://localhost:8000');
$linkConfirmacao = $frontendOrigin . '/pages/confirmar-email.html?token=' . $tokenConfirmacao;

if ($idExistente && $jaVerificado === 0) {
    // Reenvio para cadastro pendente
    $stmt = $conn->prepare("
        UPDATE usuarios
        SET nome = ?, telefone = ?, endereco = ?, data_nascimento = ?,
            senha = ?, confirmacao_token = ?, confirmacao_expira = ?
        WHERE id = ?
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'mensagem' => 'Erro ao preparar atualização.']);
        exit;
    }
    $stmt->bind_param("sssssssi", $nome, $telefone, $endereco, $datanascimento, $senhaHash, $tokenConfirmacao, $expiraConfirmacao, $idExistente);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'mensagem' => 'Erro ao atualizar cadastro pendente.']);
        exit;
    }
    $stmt->close();
} else {
    // Novo cadastro
    $stmt = $conn->prepare("
        INSERT INTO usuarios (nome, email, telefone, endereco, data_nascimento, senha, email_verificado, confirmacao_token, confirmacao_expira)
        VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'mensagem' => 'Erro ao preparar cadastro.']);
        exit;
    }
    $stmt->bind_param("ssssssss", $nome, $email, $telefone, $endereco, $datanascimento, $senhaHash, $tokenConfirmacao, $expiraConfirmacao);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'mensagem' => 'Erro ao cadastrar. ' . $stmt->error]);
        exit;
    }
    $idCadastro = (int) $conn->insert_id;
    $stmt->close();
}

app_log_event(
    $cadastroNovo ? 'Criação de conta' : 'Atualização de cadastro pendente',
    $cadastroNovo ? 'Usuário iniciou cadastro.' : 'Cadastro pendente atualizado.',
    $idCadastro,
    $nome,
    $email
);

// Envia e-mail de confirmação
$mail = new PHPMailer(true);
try {
    configure_mailer($mail);
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Valide seu e-mail - Bazar Online';
    $mail->Body = "
        <html><head><meta charset='UTF-8'></head>
        <body>
            <p>Seu cadastro foi criado. Falta apenas validar seu e-mail.</p>
            <p><a href='{$linkConfirmacao}'>Validar e-mail</a></p>
            <p>Este link expira em 24 horas.</p>
        </body></html>
    ";
    $mail->AltBody = "Valide seu e-mail: {$linkConfirmacao}. Expira em 24 horas.";
    $mail->send();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensagem' => 'Não foi possível enviar o e-mail de validação.']);
    exit;
}

echo json_encode(['success' => true, 'mensagem' => 'Cadastro realizado! Verifique seu e-mail para confirmar.']);
