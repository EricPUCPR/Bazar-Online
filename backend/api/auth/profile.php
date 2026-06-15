<?php
/**
 * GET/POST /api/auth/profile.php
 *
 * Endpoint unificado para gerenciamento do perfil do usuário logado via JWT.
 * Substitui a lógica de PaginaUsuario.php.
 */

require_once __DIR__ . '/../../config/app.php';

app_cors();
header('Content-Type: application/json');

// Autentica o usuário via JWT
$payload = jwt_require();
$userId  = (int) $payload['sub'];

$conn = db_connect('DB_NAME');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Erro ao conectar com o banco de dados.']);
    exit;
}

db_ensure_usuario_schema($conn);

// ── GET: Retorna os dados do perfil ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("CALL sp_buscar_usuario_por_id(?)");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    while ($conn->next_result()) { }

    if (!$user) {
        http_response_code(444);
        echo json_encode(['success' => false, 'mensagem' => 'Usuário não encontrado.']);
        exit;
    }

    $roupas = [];
    $stmtRoupas = $conn->prepare("CALL sp_listar_roupas_por_usuario(?)");
    if ($stmtRoupas) {
        $stmtRoupas->bind_param("i", $userId);
        $stmtRoupas->execute();
        $resRoupas = $stmtRoupas->get_result();
        if ($resRoupas) {
            while ($row = $resRoupas->fetch_assoc()) {
                $roupas[] = [
                    'id'           => (int) $row['id'],
                    'titulo'       => $row['titulo'],
                    'tipo'         => $row['tipo'],
                    'tamanho'      => $row['tamanho'],
                    'sexo'         => $row['sexo'],
                    'estado'       => $row['estado'],
                    'local_doacao' => $row['local_doacao'],
                    'foto_path'    => $row['foto_path'],
                    'status'       => $row['status'] ?? 'disponivel',
                    'criado_em'    => $row['criado_em']
                ];
            }
        }
        $stmtRoupas->close();
        while ($conn->next_result()) { }
    }

    echo json_encode([
        'success' => true,
        'perfil'  => [
            'nome'              => $user['nome'],
            'email'             => $user['email'],
            'telefone'          => $user['telefone'] ?? '',
            'endereco'          => $user['endereco'] ?? '',
            'data_nascimento'   => $user['data_nascimento'] ?? '',
            'telegram_chat_id'  => $user['telegram_chat_id'] ?? '',
            'pergunta_seguranca'=> $user['pergunta_seguranca'] ?? '',
            'is_admin'          => (int) ($user['is_admin'] ?? 0) === 1
        ],
        'roupas'  => $roupas
    ]);
    exit;
}

// ── POST: Ações de atualização/exclusão ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Atualizar Telegram Chat ID (Disponível para Admin)
    if ($action === 'atualizar_telegram') {
        $telegramChatId = trim($_POST['telegram_chat_id'] ?? '');

        $stmt = $conn->prepare("CALL sp_atualizar_telegram(?, ?)");
        $stmt->bind_param("is", $userId, $telegramChatId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'mensagem' => 'Telegram atualizado com sucesso.']);
        } else {
            echo json_encode(['success' => false, 'mensagem' => 'Erro ao atualizar Telegram.']);
        }
        $stmt->close();
        while ($conn->next_result()) { }
        exit;
    }

    // 2. Atualizar Pergunta de Segurança (Disponível para Admin)
    if ($action === 'atualizar_pergunta') {
        $pergunta = trim($_POST['pergunta_seguranca'] ?? '');
        $resposta = trim($_POST['resposta_seguranca'] ?? '');

        if ($pergunta === '' || $resposta === '') {
            echo json_encode(['success' => false, 'mensagem' => 'Preencha a pergunta e a resposta.']);
            exit;
        }

        $respostaHash = password_hash(strtolower($resposta), PASSWORD_DEFAULT);

        $stmt = $conn->prepare("CALL sp_atualizar_pergunta(?, ?, ?)");
        $stmt->bind_param("iss", $userId, $pergunta, $respostaHash);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'mensagem' => 'Pergunta de segurança configurada com sucesso.']);
        } else {
            echo json_encode(['success' => false, 'mensagem' => 'Erro ao salvar a pergunta de segurança.']);
        }
        $stmt->close();
        while ($conn->next_result()) { }
        exit;
    }

    // 3. Excluir campos opcionais (telefone, endereco, data_nascimento)
    if ($action === 'excluir_campo') {
        $campo = $_POST['campo'] ?? '';
        $camposPermitidos = ['telefone', 'endereco', 'data_nascimento'];

        if (!in_array($campo, $camposPermitidos)) {
            echo json_encode(['success' => false, 'mensagem' => 'Campo inválido.']);
            exit;
        }

        $stmt = $conn->prepare("CALL sp_excluir_campo_perfil(?, ?)");
        $stmt->bind_param("is", $userId, $campo);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'mensagem' => "Campo '$campo' excluído com sucesso."]);
        } else {
            echo json_encode(['success' => false, 'mensagem' => "Erro ao excluir campo."]);
        }
        $stmt->close();
        while ($conn->next_result()) { }
        exit;
    }

    // 4. Excluir conta
    if ($action === 'excluir_conta') {
        // Busca dados para registrar log antes de apagar
        $stmt = $conn->prepare("CALL sp_buscar_usuario_por_id(?)");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        while ($conn->next_result()) { }

        if (!$user) {
            echo json_encode(['success' => false, 'mensagem' => 'Usuário não encontrado.']);
            exit;
        }

        if ((int) ($user['is_admin'] ?? 0) === 1) {
            echo json_encode(['success' => false, 'mensagem' => 'Contas de administrador não podem ser excluídas por esta via.']);
            exit;
        }

        // Registra evento no sistema
        app_log_event(
            'Exclusão de conta',
            'Usuário excluiu a própria conta via API.',
            $userId,
            $user['nome'],
            $user['email']
        );

        $stmt = $conn->prepare("CALL sp_excluir_conta(?)");
        $stmt->bind_param("i", $userId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'mensagem' => 'Sua conta foi excluída com sucesso.']);
        } else {
            echo json_encode(['success' => false, 'mensagem' => 'Erro ao excluir a conta.']);
        }
        $stmt->close();
        while ($conn->next_result()) { }
        exit;
    }

    echo json_encode(['success' => false, 'mensagem' => 'Ação inválida.']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'mensagem' => 'Método não permitido.']);
exit;
