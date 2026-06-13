<?php
/**
 * POST /api/admin/login.php
 *
 * Etapa 1 do login admin — sem senha, apenas e-mail.
 * Envia código via Telegram, e-mail, ou pergunta de segurança.
 * Lógica extraída de admin_autentica.php.
 *
 * Retorna um "admin_pending_token" JWT de 5 min.
 *
 * Body: email, metodo (telegram|email|pergunta)
 */

require_once __DIR__ . '/../../config/app.php';
app_cors();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$vendorBase = dirname(__DIR__) . '/auth/PHPMailer/';
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

$email  = trim($_POST['email'] ?? '');
$metodo = $_POST['metodo'] ?? 'telegram';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'mensagem' => 'Digite um e-mail válido.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, nome, email, email_verificado, is_admin,
           telegram_chat_id, pergunta_seguranca, resposta_seguranca_hash
    FROM usuarios
    WHERE email = ? AND is_admin = 1
    LIMIT 1
");

if (!$stmt) {
    echo json_encode(['success' => false, 'mensagem' => 'Erro ao preparar autenticação admin.']);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$admin  = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$admin) {
    echo json_encode(['success' => false, 'mensagem' => 'E-mail não autorizado para login admin.']);
    exit;
}

if ((int) $admin['email_verificado'] !== 1) {
    echo json_encode(['success' => false, 'mensagem' => 'Valide o e-mail da conta admin antes de entrar.']);
    exit;
}

// Método: pergunta de segurança
if ($metodo === 'pergunta') {
    if (empty($admin['pergunta_seguranca']) || empty($admin['resposta_seguranca_hash'])) {
        echo json_encode(['success' => false, 'mensagem' => 'Este admin não possui pergunta de segurança cadastrada.']);
        exit;
    }

    // pending_token com o hash da resposta (nunca expõe a resposta em texto)
    $pendingToken = jwt_generate([
        'tipo'         => 'admin_pergunta_pendente',
        'sub'          => (int) $admin['id'],
        'nome'         => $admin['nome'],
        'email'        => $admin['email'],
        'resposta_hash'=> $admin['resposta_seguranca_hash'],
    ], 300);

    echo json_encode([
        'success'       => true,
        'pergunta'      => true,
        'texto_pergunta'=> $admin['pergunta_seguranca'],
        'pending_token' => $pendingToken,
        'mensagem'      => 'Responda a pergunta de segurança.',
    ]);
    exit;
}

// Código numérico (Telegram ou e-mail)
$codigo   = (string) random_int(100000, 999999);
$enviado  = false;

if ($metodo === 'telegram') {
    if (empty($admin['telegram_chat_id'])) {
        echo json_encode(['success' => false, 'mensagem' => 'Este admin não possui Telegram cadastrado.']);
        exit;
    }

    $enviado = enviar_telegram(
        $admin['telegram_chat_id'],
        "Código de login admin - Bazar Online: {$codigo}\nExpira em 5 minutos."
    );

    if (!$enviado) {
        echo json_encode(['success' => false, 'mensagem' => 'Não foi possível enviar o código pelo Telegram.']);
        exit;
    }
} else {
    $mail = new PHPMailer(true);
    try {
        configure_mailer($mail);
        $mail->addAddress($admin['email']);
        $mail->isHTML(true);
        $mail->Subject = 'Código de login admin - Bazar Online';
        $mail->Body    = "<html><head><meta charset='UTF-8'></head><body><p>Código admin: <strong>{$codigo}</strong></p><p>Expira em 5 minutos.</p></body></html>";
        $mail->AltBody = "Código admin: {$codigo}. Expira em 5 minutos.";
        $mail->send();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'mensagem' => 'Não foi possível enviar o código por e-mail.']);
        exit;
    }
}

// pending_token com o código embutido
$pendingToken = jwt_generate([
    'tipo'   => 'admin_2fa_pendente',
    'sub'    => (int) $admin['id'],
    'nome'   => $admin['nome'],
    'email'  => $admin['email'],
    'codigo' => $codigo,
], 300);

echo json_encode([
    'success'       => true,
    'pending_token' => $pendingToken,
    'mensagem'      => 'Código enviado.',
]);
