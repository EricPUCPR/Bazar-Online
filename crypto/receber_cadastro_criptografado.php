<?php
header('Content-Type: application/json');

$in = json_decode(file_get_contents("php://input"), true);

if (!$in || empty($in["key"]) || empty($in["iv"]) || empty($in["data"])) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Pacote criptografado inválido."
    ]);
    exit;
}

$key = base64_decode($in["key"]);
$iv = base64_decode($in["iv"]);
$data = base64_decode($in["data"]);

$privateKeyPath = __DIR__ . '/keys/private.pem';

if (!is_readable($privateKeyPath)) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Chave privada não encontrada."
    ]);
    exit;
}

$priv = file_get_contents($privateKeyPath);

$ok = openssl_private_decrypt(
    $key,
    $aes,
    $priv,
    OPENSSL_PKCS1_OAEP_PADDING
);

if (!$ok) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Não foi possível abrir a chave AES."
    ]);
    exit;
}

$tag = substr($data, -16);
$ciphertext = substr($data, 0, -16);

$texto = openssl_decrypt(
    $ciphertext,
    "aes-256-gcm",
    $aes,
    OPENSSL_RAW_DATA,
    $iv,
    $tag
);

if ($texto === false) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Não foi possível descriptografar os dados."
    ]);
    exit;
}

$usuarioSistema = get_current_user();
$hostname = gethostname();

error_log($usuarioSistema . ":" . $hostname . ">dados descriptografados: " . $texto);

echo json_encode([
    "success" => true,
    "mensagem" => "Dados descriptografados com sucesso.",
    "dados" => json_decode($texto, true)
]);
?>