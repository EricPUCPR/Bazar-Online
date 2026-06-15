<?php
/**
 * Configuração central do backend.
 * Caminho: backend/config/app.php
 *
 * dirname(__DIR__) => backend/
 * dirname(__DIR__, 2) => Bazar-Online2/  (raiz do projeto, onde fica o .env)
 */

function app_load_env(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $loaded = true;
    $path = dirname(__DIR__) . '/.env';

    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, 'export ') === 0) {
            $line = trim(substr($line, 7));
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        if (!preg_match('/^[A-Z0-9_]+$/i', $key)) {
            continue;
        }

        $value = trim($parts[1]);
        $quote = substr($value, 0, 1);
        if (($quote === '"' || $quote === "'") && substr($value, -1) === $quote) {
            $value = substr($value, 1, -1);
            if ($quote === '"') {
                $value = stripcslashes($value);
            }
        }

        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function env_value(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }

    return (string) $value;
}

function env_bool(string $key, bool $default = false): bool
{
    $value = strtolower(env_value($key, $default ? 'true' : 'false'));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function app_configure_errors(): void
{
    error_reporting(E_ALL);

    $debug = env_bool('APP_DEBUG', false);
    ini_set('display_errors', $debug ? '1' : '0');
    ini_set('display_startup_errors', $debug ? '1' : '0');
    ini_set('log_errors', '1');
}

function db_connect(string $databaseKey = 'DB_NAME'): mysqli
{
    mysqli_report(MYSQLI_REPORT_OFF);

    $port   = env_value('DB_PORT');
    $socket = env_value('DB_SOCKET');
    $database = env_value($databaseKey);

    $conn = @new mysqli(
        env_value('DB_HOST'),
        env_value('DB_USER'),
        env_value('DB_PASS'),
        $database,
        $port === '' ? null : (int) $port,
        $socket === '' ? null : $socket
    );

    if ($conn->connect_error && strpos($conn->connect_error, 'Unknown database') !== false && $database !== '') {
        $serverConn = @new mysqli(
            env_value('DB_HOST'),
            env_value('DB_USER'),
            env_value('DB_PASS'),
            '',
            $port === '' ? null : (int) $port,
            $socket === '' ? null : $socket
        );

        if (!$serverConn->connect_error) {
            $databaseSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $database);
            $serverConn->query("CREATE DATABASE IF NOT EXISTS `$databaseSafe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $serverConn->select_db($databaseSafe);
            $conn = $serverConn;
        }
    }

    if (!$conn->connect_error) {
        $conn->set_charset('utf8mb4');
    } else {
        error_log('Erro ao conectar no MySQL: ' . $conn->connect_error);
    }

    return $conn;
}

function db_column_exists(mysqli $conn, string $table, string $column): bool
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $stmt  = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();

    return $exists;
}

function db_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare("SHOW TABLES LIKE ?");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $table);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();

    return $exists;
}

function db_ensure_column(mysqli $conn, string $table, string $column, string $definition): void
{
    if (!db_column_exists($conn, $table, $column)) {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $conn->query("ALTER TABLE `$table` ADD COLUMN $definition");
    }
}

function db_ensure_usuario_schema(mysqli $conn): void
{
    // Schema is initialized by banco.sql. No-op for security under least privilege.
}

function db_ensure_log_schema(mysqli $conn): void
{
    // Schema is initialized by banco.sql. No-op for security under least privilege.
}

/**
 * Registra um evento de auditoria no banco.
 *
 * @param string   $acao        Nome da ação (ex: 'Login', 'Criação de conta')
 * @param string   $detalhes    Descrição livre
 * @param int|null $usuarioId   ID do usuário comum (null para admins ou sistema)
 * @param int|null $adminId     ID do admin (null para usuários comuns ou sistema)
 * @param string|null $nome     Nome para exibição no log
 * @param string|null $email    E-mail para exibição no log
 */
function app_log_event(
    string  $acao,
    string  $detalhes   = '',
    ?int    $usuarioId  = null,
    ?int    $adminId    = null,
    ?string $nome       = null,
    ?string $email      = null
): void {
    $conn = db_connect('DB_NAME');

    if ($conn->connect_error) {
        return;
    }

    $stmt = $conn->prepare("CALL sp_salvar_log(?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        $conn->close();
        return;
    }

    $stmt->bind_param("iissss", $usuarioId, $adminId, $nome, $email, $acao, $detalhes);
    $stmt->execute();
    $stmt->close();
    while ($conn->next_result()) { }
    $conn->close();
}

function db_ensure_roupa_schema(mysqli $conn): void
{
    // Schema is initialized by banco.sql. No-op for security under least privilege.
}

function configure_mailer($mail, ?string $fromName = null): void
{
    $mail->isSMTP();
    $mail->Host       = env_value('SMTP_HOST');
    $mail->SMTPAuth   = true;
    $mail->Username   = env_value('SMTP_USERNAME');
    $mail->Password   = env_value('SMTP_PASSWORD');
    $mail->SMTPSecure = env_value('SMTP_SECURE', 'tls');
    $mail->Port       = (int) env_value('SMTP_PORT', '587');
    $mail->CharSet    = 'UTF-8';
    $mail->Encoding   = 'base64';

    $mail->setFrom(
        env_value('SMTP_FROM_EMAIL', env_value('SMTP_USERNAME')),
        $fromName ?? env_value('SMTP_FROM_NAME', 'Bazar Online')
    );
}

function enviar_telegram(string $chatId, string $mensagem): bool
{
    $token = env_value('BOT_TOKEN');

    if ($token === '' || $chatId === '') {
        return false;
    }

    $url  = "https://api.telegram.org/bot{$token}/sendMessage";
    $dados = ['chat_id' => $chatId, 'text' => $mensagem];
    $opcoes = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($dados),
            'timeout' => 10
        ]
    ];

    return @file_get_contents($url, false, stream_context_create($opcoes)) !== false;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_load_env_encrypted(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $loaded = true;
    $path   = dirname(__DIR__) . '/.env.enc';

    if (!is_readable($path)) {
        app_load_env();
        return;
    }

    $chaveBase64 = getenv('ENV_AES_KEY');

    if (!$chaveBase64) {
        die('Chave AES não configurada.');
    }

    $chave        = base64_decode($chaveBase64);
    $dados        = base64_decode(file_get_contents($path));
    $iv           = substr($dados, 0, 12);
    $tag          = substr($dados, 12, 16);
    $criptografado = substr($dados, 28);

    $conteudo = openssl_decrypt($criptografado, 'aes-256-gcm', $chave, OPENSSL_RAW_DATA, $iv, $tag);

    if ($conteudo === false) {
        die('Erro ao descriptografar .env.enc');
    }

    foreach (explode("\n", $conteudo) as $line) {
        $line = trim($line);

        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        $parts = explode('=', $line, 2);

        if (count($parts) !== 2) {
            continue;
        }

        $key   = trim($parts[0]);
        $value = trim($parts[1]);

        $quote = substr($value, 0, 1);
        if (($quote === '"' || $quote === "'") && substr($value, -1) === $quote) {
            $value = substr($value, 1, -1);
            if ($quote === '"') {
                $value = stripcslashes($value);
            }
        }

        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function validar_recaptcha(string $token_resposta): bool
{
    $secret = env_value('RECAPTCHA_SECRET_KEY');

    if (empty($token_resposta)) {
        return false;
    }

    $url  = 'https://www.google.com/recaptcha/api/siteverify';
    $data = ['secret' => $secret, 'response' => $token_resposta];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $result = @file_get_contents($url, false, stream_context_create($options));

    if ($result === false) {
        return false;
    }

    $json = json_decode($result);
    return $json->success ?? false;
}

/**
 * Cabeçalhos CORS — permite chamadas do frontend (porta diferente).
 * Ajuste FRONTEND_ORIGIN no .env para o domínio/porta do seu frontend.
 */
function app_cors(): void
{
    $origin = env_value('FRONTEND_ORIGIN', 'http://localhost:8000');

    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: false');

    // Responde preflight OPTIONS sem processamento
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// Bootstrap automático
putenv('ENV_AES_KEY=NX6eQsCExOgAKC/v8BtDHcmMn2B/GtBqaG5mHhHVHlU=');
app_load_env_encrypted();
app_configure_errors();

require_once __DIR__ . '/jwt.php';
