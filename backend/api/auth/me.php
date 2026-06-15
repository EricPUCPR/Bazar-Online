<?php
/**
 * GET /api/auth/me.php
 *
 * Retorna os dados do usuário autenticado.
 * O JWT é stateless (contém apenas sub + admin).
 * Nome e e-mail são buscados do banco a partir do ID.
 *
 * Header obrigatório: Authorization: Bearer <token>
 *
 * Resposta de sucesso:
 *   { "logado": true, "sub": 1, "nome": "João", "email": "...", "admin": false }
 * Resposta sem token (ou token inválido):
 *   { "logado": false }
 */

require_once __DIR__ . '/../../config/app.php';

app_cors();
header('Content-Type: application/json');

$token   = jwt_from_request();
$payload = $token ? jwt_verify($token) : null;

if ($payload === null) {
    echo json_encode(['logado' => false]);
    exit;
}

$userId  = (int) ($payload['sub'] ?? 0);
$isAdmin = (bool) ($payload['admin'] ?? false);

// Busca dados atualizados do banco
$conn = db_connect('DB_NAME');

if (!$conn->connect_error && $userId > 0) {
    if ($isAdmin) {
        $stmt = $conn->prepare("CALL sp_buscar_admin_por_id(?)");
    } else {
        $stmt = $conn->prepare("CALL sp_buscar_usuario_por_id(?)");
    }

    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        while ($conn->next_result()) { }
        $conn->close();

        if ($user) {
            echo json_encode([
                'logado' => true,
                'sub'    => $userId,
                'nome'   => $user['nome'],
                'email'  => $user['email'],
                'admin'  => $isAdmin,
            ]);
            exit;
        }
    }
}

// Fallback: usuário não encontrado no banco (conta deletada, etc.)
echo json_encode(['logado' => false]);
