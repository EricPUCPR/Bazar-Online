<?php
/**
 * POST /api/auth/login.php
 *
 * Etapa 1 do login de usuário comum.
 * Valida e-mail + senha. Se correto, gera código 2FA e retorna um
 * "pending_token" (JWT de curta duração que identifica a sessão de
 * verificação). O cliente usa esse token para chamar /api/auth/verifica_2fa.php.
 *
 * Resposta de sucesso (two_factor pending):
 *   { "success": true, "two_factor": true, "pending_token": "<jwt>", "mensagem": "..." }
 *
 * Resposta de erro:
 *   { "success": false, "mensagem": "..." }
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

$conn = db_connect('DB_NAME');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Erro no banco de dados.']);
    exit;
}

db_ensure_usuario_schema($conn);

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
    echo json_encode(['success' => false, 'mensagem' => 'E-mail ou senha inválidos.']);
    exit;
}

$stmt = $conn->prepare("CALL sp_buscar_usuario_por_email(?)");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result ? $result->fetch_assoc() : null;
$stmt->close();
while ($conn->next_result()) { }

// Bloqueia admin de usar o login comum
if ($user && (int) ($user['is_admin'] ?? 0) === 1) {
    echo json_encode(['success' => false, 'mensagem' => 'Conta admin. Use a URL exclusiva do administrador.']);
    exit;
}

if (!$user || !password_verify($senha, $user['senha'])) {
    echo json_encode(['success' => false, 'mensagem' => 'E-mail ou senha inválidos.']);
    exit;
}

if ((int) $user['email_verificado'] !== 1) {
    echo json_encode(['success' => false, 'mensagem' => 'E-mail não verificado. Cheque sua caixa de entrada.']);
    exit;
}

// Gera código 2FA
$codigo2fa = (string) random_int(100000, 999999);
$expiraEm  = time() + 300; // 5 minutos

// Gera "pending_token" — JWT que NÃO autentica o usuário, só identifica a sessão 2FA
$pendingToken = jwt_generate([
    'tipo'   => '2fa_pendente',
    'sub'    => (int) $user['id'],
    'nome'   => $user['nome'],
    'email'  => $user['email'],
    'codigo' => $codigo2fa,
], 300);

// Envia código por e-mail
$mail = new PHPMailer(true);
try {
    configure_mailer($mail);
    $mail->addAddress($user['email']);
    $mail->isHTML(true);
    $mail->Subject = 'Código de verificação - Bazar Online';
    $mail->Body    = "
        <html><head><meta charset='UTF-8'></head>
        <body>
            <p>Seu código de verificação é:</p>
            <p><strong>{$codigo2fa}</strong></p>
            <p>Este código expira em 5 minutos.</p>
        </body></html>
    ";
    $mail->AltBody = "Seu código de verificação é: {$codigo2fa}. Este código expira em 5 minutos.";
    $mail->send();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensagem' => 'Não foi possível enviar o código de verificação.']);
    exit;
}

echo json_encode([
    'success'       => true,
    'two_factor'    => true,
    'pending_token' => $pendingToken,
    'mensagem'      => 'Código de verificação enviado para seu e-mail.',
]);
