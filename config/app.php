<?php

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

    $value = (string) $value;

    if (strpos($value, 'ENC:') === 0) {
        return decrypt_env_value(substr($value, 4));
    }

    return $value;
}

function decrypt_env_value(string $valorCriptografado): string
{
    $chaveBase64 = $_SERVER['ENV_AES_KEY'] ?? $_ENV['ENV_AES_KEY'] ?? getenv('ENV_AES_KEY') ?? false;

    if (!$chaveBase64) {
        die('Chave ENV_AES_KEY não configurada no Apache e nem no .env.');
    }

    $chave = base64_decode($chaveBase64);
    $dados = base64_decode($valorCriptografado);

    $iv = substr($dados, 0, 12);
    $tag = substr($dados, 12, 16);
    $criptografado = substr($dados, 28);

    $valorOriginal = openssl_decrypt(
        $criptografado,
        'aes-256-gcm',
        $chave,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($valorOriginal === false) {
        die('Erro ao descriptografar variável de ambiente. Verifique se a ENV_AES_KEY está correta.');
    }

    return $valorOriginal;
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

function db_connect(string $databaseKey = 'DB_NAME_USUARIOS'): mysqli
{
    mysqli_report(MYSQLI_REPORT_OFF);

    $port = env_value('DB_PORT');
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
    $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");

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
    if (!db_table_exists($conn, 'usuarios')) {
        $conn->query("
            CREATE TABLE usuarios (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                telefone VARCHAR(20) DEFAULT NULL,
                endereco VARCHAR(200) DEFAULT NULL,
                data_nascimento DATE DEFAULT NULL,
                senha VARCHAR(255) NOT NULL,
                criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                recuperacao_token VARCHAR(255) DEFAULT NULL,
                recuperacao_expira DATETIME DEFAULT NULL,
                email_verificado TINYINT(1) NOT NULL DEFAULT 0,
                is_admin TINYINT(1) NOT NULL DEFAULT 0,
                confirmacao_token VARCHAR(128) DEFAULT NULL,
                confirmacao_expira DATETIME DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    db_ensure_column($conn, 'usuarios', 'recuperacao_token', 'recuperacao_token VARCHAR(255) DEFAULT NULL');
    db_ensure_column($conn, 'usuarios', 'recuperacao_expira', 'recuperacao_expira DATETIME DEFAULT NULL');
    db_ensure_column($conn, 'usuarios', 'email_verificado', 'email_verificado TINYINT(1) NOT NULL DEFAULT 0');
    db_ensure_column($conn, 'usuarios', 'is_admin', 'is_admin TINYINT(1) NOT NULL DEFAULT 0');
    db_ensure_column($conn, 'usuarios', 'confirmacao_token', 'confirmacao_token VARCHAR(128) DEFAULT NULL');
    db_ensure_column($conn, 'usuarios', 'confirmacao_expira', 'confirmacao_expira DATETIME DEFAULT NULL');
    $conn->query("UPDATE usuarios SET is_admin = 1 WHERE id = 1");
}

function db_ensure_log_schema(mysqli $conn): void
{
    if (!db_table_exists($conn, 'logs_sistema')) {
        $conn->query("
            CREATE TABLE logs_sistema (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT DEFAULT NULL,
                nome VARCHAR(100) DEFAULT NULL,
                email VARCHAR(150) DEFAULT NULL,
                acao VARCHAR(80) NOT NULL,
                detalhes VARCHAR(255) DEFAULT NULL,
                criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    db_ensure_column($conn, 'logs_sistema', 'usuario_id', 'usuario_id INT DEFAULT NULL');
    db_ensure_column($conn, 'logs_sistema', 'nome', 'nome VARCHAR(100) DEFAULT NULL');
    db_ensure_column($conn, 'logs_sistema', 'email', 'email VARCHAR(150) DEFAULT NULL');
    db_ensure_column($conn, 'logs_sistema', 'acao', 'acao VARCHAR(80) NOT NULL DEFAULT ""');
    db_ensure_column($conn, 'logs_sistema', 'detalhes', 'detalhes VARCHAR(255) DEFAULT NULL');
    db_ensure_column($conn, 'logs_sistema', 'criado_em', 'criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
}

function app_log_event(string $acao, string $detalhes = '', ?int $usuarioId = null, ?string $nome = null, ?string $email = null): void
{
    $conn = db_connect('DB_NAME_USUARIOS');

    if ($conn->connect_error) {
        return;
    }

    db_ensure_log_schema($conn);

    $stmt = $conn->prepare("
        INSERT INTO logs_sistema (usuario_id, nome, email, acao, detalhes)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        return;
    }

    $usuarioIdLog = $usuarioId;
    $nomeLog = $nome;
    $emailLog = $email;
    $acaoLog = $acao;
    $detalhesLog = $detalhes;

    $stmt->bind_param("issss", $usuarioIdLog, $nomeLog, $emailLog, $acaoLog, $detalhesLog);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

function db_ensure_roupa_schema(mysqli $conn): void
{
    if (!db_table_exists($conn, 'roupas')) {
        $conn->query("
            CREATE TABLE roupas (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(120) DEFAULT NULL,
                tipo VARCHAR(100) NOT NULL,
                tamanho VARCHAR(10) DEFAULT NULL,
                sexo ENUM('Masculino','Feminino','Unissex') DEFAULT NULL,
                estado ENUM('Novo','Semi-Novo','Usado') DEFAULT NULL,
                local_doacao VARCHAR(180) DEFAULT NULL,
                foto_path VARCHAR(255) DEFAULT NULL,
                pausado TINYINT(1) NOT NULL DEFAULT 0,
                id_usuario INT DEFAULT NULL,
                criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    db_ensure_column($conn, 'roupas', 'titulo', 'titulo VARCHAR(120) DEFAULT NULL');
    db_ensure_column($conn, 'roupas', 'tipo', 'tipo VARCHAR(100) NOT NULL DEFAULT ""');
    db_ensure_column($conn, 'roupas', 'tamanho', 'tamanho VARCHAR(10) DEFAULT NULL');
    db_ensure_column($conn, 'roupas', 'sexo', "sexo ENUM('Masculino','Feminino','Unissex') DEFAULT NULL");
    db_ensure_column($conn, 'roupas', 'estado', "estado ENUM('Novo','Semi-Novo','Usado') DEFAULT NULL");
    db_ensure_column($conn, 'roupas', 'local_doacao', 'local_doacao VARCHAR(180) DEFAULT NULL');
    db_ensure_column($conn, 'roupas', 'foto_path', 'foto_path VARCHAR(255) DEFAULT NULL');
    db_ensure_column($conn, 'roupas', 'pausado', 'pausado TINYINT(1) NOT NULL DEFAULT 0');
    db_ensure_column($conn, 'roupas', 'id_usuario', 'id_usuario INT DEFAULT NULL');
    db_ensure_column($conn, 'roupas', 'criado_em', 'criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
}

function configure_mailer($mail, ?string $fromName = null): void
{
    $mail->isSMTP();
    $mail->Host = env_value('SMTP_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = env_value('SMTP_USERNAME');
    $mail->Password = env_value('SMTP_PASSWORD');
    $mail->SMTPSecure = env_value('SMTP_SECURE', 'tls');
    $mail->Port = (int) env_value('SMTP_PORT', '587');
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

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

    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    $dados = [
        'chat_id' => $chatId,
        'text' => $mensagem
    ];

    $opcoes = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($dados),
            'timeout' => 10
        ]
    ];

    $contexto = stream_context_create($opcoes);

    return @file_get_contents($url, false, $contexto) !== false;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

app_load_env();
app_configure_errors();

function validar_recaptcha(string $token_resposta): bool
{
    $secret = env_value('RECAPTCHA_SECRET_KEY');
    
    if (empty($token_resposta)) {
        return false;
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secret,
        'response' => $token_resposta
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context  = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) {
        return false;
    }

    $json = json_decode($result);
    return $json->success ?? false;
}
