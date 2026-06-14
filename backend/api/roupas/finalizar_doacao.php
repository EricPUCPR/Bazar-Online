<?php
/**
 * POST /api/roupas/finalizar_doacao.php
 *
 * Finaliza o processo de doação: envia e-mail para os anunciantes
 * com os dados de contato do interessado e pausa os anúncios.
 * Requer JWT de usuário autenticado.
 *
 * Header: Authorization: Bearer <token>
 * Body: ids (string csv de IDs), aceite_compartilhamento (1)
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

$payload    = jwt_require(false);
$userId     = (int) $payload['sub'];
$userNome   = $payload['nome'];
$userEmail  = $payload['email'] ?? '';

if (!isset($_POST['aceite_compartilhamento'])) {
    echo json_encode(['success' => false, 'mensagem' => 'Você precisa aceitar o compartilhamento do e-mail e telefone.']);
    exit;
}

// Parse de IDs
$raw = $_POST['ids'] ?? '';
$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $raw)), fn($id) => $id > 0)));

if (count($ids) === 0) {
    echo json_encode(['success' => false, 'mensagem' => 'Nenhuma roupa selecionada.']);
    exit;
}

$conn = db_connect('DB_NAME');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Erro de conexão com o banco.']);
    exit;
}

db_ensure_roupa_schema($conn);
db_ensure_usuario_schema($conn);

// Busca dados do interessado (telefone)
$stmtU = $conn->prepare("CALL sp_buscar_usuario_por_id(?)");
$stmtU->bind_param("i", $userId);
$stmtU->execute();
$resU     = $stmtU->get_result();
$userRow  = $resU ? $resU->fetch_assoc() : null;
$stmtU->close();
while ($conn->next_result()) { }
$telefone = $userRow['telefone'] ?? 'Não informado';

// Busca roupas
$roupas = [];
foreach ($ids as $id) {
    $stmtR = $conn->prepare("CALL sp_buscar_roupa_por_id(?)");
    $stmtR->bind_param("i", $id);
    $stmtR->execute();
    $resR = $stmtR->get_result();
    if ($resR && $row = $resR->fetch_assoc()) {
        $roupas[] = $row;
    }
    $stmtR->close();
    while ($conn->next_result()) { }
}

$ativas = array_values(array_filter($roupas, fn($r) => (int) $r['pausado'] !== 1));

if (count($ativas) === 0) {
    echo json_encode(['success' => false, 'mensagem' => 'Nenhuma roupa ativa encontrada.']);
    exit;
}

// Agrupa por anunciante
$grupos = [];
foreach ($ativas as $roupa) {
    $anuncianteId = (int) ($roupa['id_usuario'] ?? 0);
    if ($anuncianteId <= 0) {
        echo json_encode(['success' => false, 'mensagem' => 'Uma das roupas não possui anunciante vinculado.']);
        exit;
    }
    $grupos[$anuncianteId][] = $roupa;
}

$idsEnviados = [];

foreach ($grupos as $anuncianteId => $roupasDoAnunciante) {
    $stmtA = $conn->prepare("CALL sp_buscar_usuario_por_id(?)");
    $stmtA->bind_param("i", $anuncianteId);
    $stmtA->execute();
    $resA       = $stmtA->get_result();
    $anunciante = $resA ? $resA->fetch_assoc() : null;
    $stmtA->close();
    while ($conn->next_result()) { }

    if (!$anunciante || empty($anunciante['email'])) {
        echo json_encode(['success' => false, 'mensagem' => 'Não foi possível encontrar o e-mail de um anunciante.']);
        exit;
    }

    $itensHtml  = '';
    $itensTexto = '';
    foreach ($roupasDoAnunciante as $roupa) {
        $desc = ($roupa['titulo'] ?: $roupa['tipo']) . ' — tipo: ' . $roupa['tipo'] . ', tamanho: ' . $roupa['tamanho'] . ', estado: ' . $roupa['estado'];
        $itensHtml  .= '<li>' . e($desc) . '</li>';
        $itensTexto .= '- ' . $desc . "\n";
    }

    $mail = new PHPMailer(true);
    try {
        configure_mailer($mail);
        $mail->addAddress($anunciante['email']);
        $mail->isHTML(true);
        $mail->Subject = 'Interesse em doação - Bazar Online';
        $mail->Body = "
            <html><head><meta charset='UTF-8'></head>
            <body>
                <p>Olá, " . e($anunciante['nome'] ?? 'anunciante') . ".</p>
                <p>Uma pessoa demonstrou interesse nas suas peças:</p>
                <ul>{$itensHtml}</ul>
                <p><strong>Dados de contato do interessado:</strong></p>
                <p>Nome: " . e($userNome) . "<br>E-mail: " . e($userEmail) . "<br>Telefone: " . e($telefone) . "</p>
                <p>Entre em contato para combinar a doação.</p>
            </body></html>
        ";
        $mail->AltBody = "Olá, {$anunciante['nome']}.\n\nInteresse nas peças:\n{$itensTexto}\nContato:\nNome: {$userNome}\nE-mail: {$userEmail}\nTelefone: {$telefone}";
        $mail->send();

        foreach ($roupasDoAnunciante as $roupa) {
            $idsEnviados[] = (int) $roupa['id'];
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'mensagem' => 'Não foi possível enviar o e-mail para um dos anunciantes.']);
        exit;
    }
}

// Pausa roupas enviadas
foreach ($idsEnviados as $idEnv) {
    $stmtP = $conn->prepare("CALL sp_pausar_roupa(?)");
    $stmtP->bind_param("i", $idEnv);
    $stmtP->execute();
    $stmtP->close();
    while ($conn->next_result()) { }
}

$conn->close();

app_log_event('Finalização de doação', 'Usuário finalizou uma doação.', $userId, $userNome, $userEmail);

echo json_encode(['success' => true, 'mensagem' => 'Doação finalizada. Os anunciantes receberam seus dados de contato.']);
