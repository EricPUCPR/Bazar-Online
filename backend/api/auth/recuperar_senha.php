<?php
/**
 * POST /api/auth/recuperar_senha.php
 *
 * Ação 1 (action=solicitar): valida a nova senha, gera código de 6 dígitos,
 *   guarda código+hash da nova senha no banco (campo recuperacao_token)
 *   e envia o código por e-mail. Cooldown de 1 minuto por e-mail.
 *
 * Ação 2 (action=redefinir): recebe email + código, verifica no banco,
 *   aplica a nova senha e emite JWT para login imediato.
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
    if ($convertido !== false) $texto = $convertido;
    return preg_replace('/[^a-z0-9 ]/', '', $texto);
}

$conn = db_connect('DB_NAME');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Erro de conexão com o banco.']);
    exit;
}

$action = trim($_POST['action'] ?? 'solicitar');

// ═══════════════════════════════════════════════════════════════
// AÇÃO 1 — Solicitar código de recuperação
// ═══════════════════════════════════════════════════════════════
if ($action === 'solicitar') {
    $email     = trim($_POST['email'] ?? '');
    $novaSenha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
        echo json_encode(['success' => false, 'mensagem' => 'E-mail inválido.']);
        exit;
    }

    $senhaForte = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/";
    $regexSeq   = "/(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/";

    if ($novaSenha !== $confirmar) {
        echo json_encode(['success' => false, 'mensagem' => 'As senhas não coincidem.']);
        exit;
    }
    if (!preg_match($senhaForte, $novaSenha)) {
        echo json_encode(['success' => false, 'mensagem' => 'Senha fraca. Use letras maiúsculas, minúsculas, número e símbolo.']);
        exit;
    }
    if (preg_match($regexSeq, $novaSenha)) {
        echo json_encode(['success' => false, 'mensagem' => 'A senha não pode conter sequência numérica.']);
        exit;
    }

    $stmt = $conn->prepare("CALL sp_buscar_usuario_por_email(?)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    while ($conn->next_result()) { }

    // Não revela se o e-mail existe (proteção contra enumeração)
    if (!$user) {
        echo json_encode(['success' => true, 'mensagem' => 'Se o e-mail estiver cadastrado, você receberá o código.']);
        exit;
    }

    // Valida se a senha contém partes do nome
    $senhaNorm  = normalizarTexto($novaSenha);
    foreach (array_filter(preg_split('/\s+/', normalizarTexto($user['nome'])), fn($p) => strlen($p) >= 3) as $parte) {
        if (strpos($senhaNorm, $parte) !== false) {
            echo json_encode(['success' => false, 'mensagem' => 'A senha não pode conter o seu nome.']);
            exit;
        }
    }

    // Cooldown de 1 minuto
    if (!empty($user['recuperacao_expira'])) {
        $enviadoEm   = strtotime($user['recuperacao_expira']) - 300;
        $cooldownEnd = $enviadoEm + 60;
        $rem = $cooldownEnd - time();
        if ($rem > 0) {
            echo json_encode(['success' => false, 'mensagem' => "Aguarde mais {$rem} segundo(s) para solicitar novo código."]);
            exit;
        }
    }

    $codigo    = sprintf('%06d', mt_rand(0, 999999));
    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    // Formato: "codigo:tentativas:hash_senha"
    $tokenComposto = $codigo . ':0:' . $senhaHash;
    $expira        = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    $update = $conn->prepare("CALL sp_definir_token_recuperacao(?, ?, ?)");
    $update->bind_param("sss", $email, $tokenComposto, $expira);
    $update->execute();
    $update->close();
    while ($conn->next_result()) { }

    $mail = new PHPMailer(true);
    try {
        configure_mailer($mail);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Código de Recuperação de Senha - Bazar Online';
        $mail->Body = "
            <html><head><meta charset='UTF-8'></head>
            <body style='font-family:sans-serif;'>
                <h2 style='color:#1f6f9f;'>Recuperação de Senha</h2>
                <p>Seu código para redefinição de senha é:</p>
                <p style='font-size:32px;font-weight:bold;letter-spacing:6px;color:#263238;'>{$codigo}</p>
                <p style='color:#666;font-size:13px;'>Este código expira em 5 minutos. Não compartilhe com ninguém.</p>
            </body></html>
        ";
        $mail->AltBody = "Código de recuperação do Bazar Online: {$codigo}. Expira em 5 minutos.";
        $mail->send();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'mensagem' => 'Não foi possível enviar o e-mail de recuperação.']);
        exit;
    }

    echo json_encode(['success' => true, 'mensagem' => 'Código de verificação enviado ao e-mail!']);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// AÇÃO 2 — Verificar código e redefinir senha
// ═══════════════════════════════════════════════════════════════
if ($action === 'redefinir') {
    $email  = trim($_POST['email']  ?? '');
    $codigo = trim($_POST['codigo'] ?? '');

    if ($email === '' || $codigo === '') {
        echo json_encode(['success' => false, 'mensagem' => 'Dados incompletos.']);
        exit;
    }

    $stmt = $conn->prepare("CALL sp_buscar_usuario_por_email(?)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    while ($conn->next_result()) { }

    if (!$user) {
        echo json_encode(['success' => false, 'mensagem' => 'Usuário não encontrado.']);
        exit;
    }

    $recToken = $user['recuperacao_token'] ?? '';
    $recExp   = $user['recuperacao_expira'] ?? '';

    if (!$recToken || !$recExp || strtotime($recExp) < time()) {
        echo json_encode(['success' => false, 'mensagem' => 'Código expirado. Solicite uma nova redefinição.']);
        exit;
    }

    $partes      = explode(':', $recToken, 3);
    $savedCode   = $partes[0] ?? '';
    $tentativas  = (int) ($partes[1] ?? 0);
    $senhaHash   = $partes[2] ?? '';

    if ($tentativas >= 3) {
        $stmtClear = $conn->prepare("CALL sp_definir_token_recuperacao(?, NULL, NULL)");
        $stmtClear->bind_param("s", $email);
        $stmtClear->execute();
        $stmtClear->close();
        while ($conn->next_result()) { }
        echo json_encode(['success' => false, 'mensagem' => 'Código invalidado por excesso de tentativas. Solicite uma nova redefinição.']);
        exit;
    }

    if ($codigo !== $savedCode) {
        $tentativas++;
        if ($tentativas >= 3) {
            $stmtClear = $conn->prepare("CALL sp_definir_token_recuperacao(?, NULL, NULL)");
            $stmtClear->bind_param("s", $email);
            $stmtClear->execute();
            $stmtClear->close();
            while ($conn->next_result()) { }
            echo json_encode(['success' => false, 'mensagem' => 'Código invalidado por excesso de tentativas. Solicite uma nova redefinição.']);
        } else {
            $newToken = $savedCode . ':' . $tentativas . ':' . $senhaHash;
            $stmtUpd  = $conn->prepare("CALL sp_definir_token_recuperacao(?, ?, ?)");
            $stmtUpd->bind_param("sss", $email, $newToken, $recExp);
            $stmtUpd->execute();
            $stmtUpd->close();
            while ($conn->next_result()) { }
            $restantes = 3 - $tentativas;
            echo json_encode(['success' => false, 'mensagem' => "Código incorreto. Você tem mais {$restantes} tentativa(s)."]);
        }
        exit;
    }

    // Código correto — aplica a nova senha
    $stmtUpd = $conn->prepare("CALL sp_redefinir_senha(?, ?)");
    $stmtUpd->bind_param("ss", $email, $senhaHash);
    $ok = $stmtUpd->execute();
    $stmtUpd->close();
    while ($conn->next_result()) { }

    if ($ok) {
        app_log_event('Redefinição de senha', 'Usuário redefiniu a senha via código de e-mail.', $user['id'], null, $user['nome'], $user['email']);

        // JWT stateless: apenas sub (id) e admin
        $token = jwt_generate([
            'sub'   => (int) $user['id'],
            'admin' => false,
        ]);

        echo json_encode([
            'success'  => true,
            'token'    => $token,
            'nome'     => $user['nome'],
            'admin'    => false,
            'mensagem' => 'Senha atualizada e login realizado com sucesso!',
        ]);
    } else {
        echo json_encode(['success' => false, 'mensagem' => 'Erro ao redefinir a senha.']);
    }
    exit;
}

echo json_encode(['success' => false, 'mensagem' => 'Ação inválida.']);
