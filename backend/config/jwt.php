<?php
/**
 * JWT Helper — implementação HS256 sem biblioteca externa.
 *
 * Funções públicas:
 *   jwt_generate(array $payload, int $ttlSeconds): string
 *   jwt_verify(string $token): array|null   — retorna payload ou null se inválido/expirado
 *   jwt_from_request(): string|null         — extrai token do header Authorization: Bearer <token>
 */

function _jwt_secret(): string
{
    $secret = env_value('JWT_SECRET');

    if ($secret === '') {
        // Fallback seguro: nunca deve chegar em produção sem a variável configurada.
        error_log('[JWT] JWT_SECRET não configurado no .env!');
        $secret = 'INSECURE_FALLBACK_CHANGE_IN_ENV';
    }

    return $secret;
}

function _jwt_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function _jwt_base64url_decode(string $data): string|false
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }

    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Gera um JWT assinado com HS256.
 *
 * @param array $payload  Dados a incluir no token (ex: ['sub' => 1, 'nome' => 'João', 'admin' => false])
 * @param int   $ttl      Tempo de vida em segundos (padrão: 8 horas)
 */
function jwt_generate(array $payload, int $ttl = 28800): string
{
    $header = _jwt_base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));

    $payload['iat'] = time();
    $payload['exp'] = time() + $ttl;

    $body = _jwt_base64url_encode(json_encode($payload));

    $signature = _jwt_base64url_encode(
        hash_hmac('sha256', "$header.$body", _jwt_secret(), true)
    );

    return "$header.$body.$signature";
}

/**
 * Verifica e decodifica um JWT.
 *
 * @return array|null  Payload decodificado, ou null se inválido ou expirado.
 */
function jwt_verify(string $token): array|null
{
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return null;
    }

    [$header, $body, $signature] = $parts;

    // Valida assinatura
    $expected = _jwt_base64url_encode(
        hash_hmac('sha256', "$header.$body", _jwt_secret(), true)
    );

    if (!hash_equals($expected, $signature)) {
        return null;
    }

    $payload = json_decode(_jwt_base64url_decode($body), true);

    if (!is_array($payload)) {
        return null;
    }

    // Valida expiração
    if (!isset($payload['exp']) || time() > (int) $payload['exp']) {
        return null;
    }

    return $payload;
}

/**
 * Extrai o token JWT do header HTTP Authorization: Bearer <token>
 *
 * @return string|null
 */
function jwt_from_request(): string|null
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

    if (str_starts_with($header, 'Bearer ')) {
        return trim(substr($header, 7));
    }

    return null;
}

/**
 * Middleware de autenticação: aborta com 401 se o token for inválido.
 * Retorna o payload do JWT.
 *
 * @param bool $requireAdmin  Se true, exige que o campo 'admin' seja true no payload.
 * @return array              Payload do JWT.
 */
function jwt_require(bool $requireAdmin = false): array
{
    $token = jwt_from_request();

    if ($token === null) {
        http_response_code(401);
        echo json_encode(['success' => false, 'mensagem' => 'Token não fornecido.']);
        exit;
    }

    $payload = jwt_verify($token);

    if ($payload === null) {
        http_response_code(401);
        echo json_encode(['success' => false, 'mensagem' => 'Token inválido ou expirado.']);
        exit;
    }

    if ($requireAdmin && empty($payload['admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'mensagem' => 'Acesso restrito a administradores.']);
        exit;
    }

    return $payload;
}
