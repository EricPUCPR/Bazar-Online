<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/app.php';

$in = json_decode(file_get_contents("php://input"), true);

if (!$in || empty($in["key"]) || empty($in["iv"]) || empty($in["data"])) {
    echo json_encode(["success" => false, "mensagem" => "Pacote criptografado inválido."]);
    exit;
}

$key = base64_decode($in["key"]);
$iv = base64_decode($in["iv"]);
$data = base64_decode($in["data"]);

$privateKeyPath = __DIR__ . '/keys/private.pem';

if (!is_readable($privateKeyPath)) {
    echo json_encode(["success" => false, "mensagem" => "Chave privada não encontrada no servidor."]);
    exit;
}

$priv = file_get_contents($privateKeyPath);

$ok = openssl_private_decrypt($key, $aes, $priv, OPENSSL_PKCS1_OAEP_PADDING);

if (!$ok) {
    echo json_encode(["success" => false, "mensagem" => "Não foi possível abrir a chave de sessão AES."]);
    exit;
}

$tag = substr($data, -16);
$ciphertext = substr($data, 0, -16);

$texto = openssl_decrypt($ciphertext, "aes-256-gcm", $aes, OPENSSL_RAW_DATA, $iv, $tag);

if ($texto === false) {
    echo json_encode(["success" => false, "mensagem" => "Falha ao descriptografar os dados do formulário."]);
    exit;
}

$usuarioSistema = get_current_user();
$hostname = gethostname();
error_log($usuarioSistema . ":" . $hostname . ">dados descriptografados: " . $texto);

$dadosForm = json_decode($texto, true);

if (empty($dadosForm['termos']) || $dadosForm['termos'] !== "on") {
    echo json_encode(["success" => false, "mensagem" => "Você precisa aceitar os termos de uso."]);
    exit;
}

if (!validar_recaptcha($dadosForm['g-recaptcha-response'] ?? '')) {
    echo json_encode(["success" => false, "mensagem" => "Falha na validação do reCAPTCHA."]);
    exit;
}

$conn = db_connect('DB_NAME_USUARIOS');

$stmtVerifica = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
$stmtVerifica->bind_param("s", $dadosForm['email']);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "mensagem" => "Este e-mail já está cadastrado no sistema."]);
    $stmtVerifica->close();
    exit;
}
$stmtVerifica->close();

$chaveEnv = env_value('CHAVE_SIMETRICA'); 
$chaveBd = hash('sha256', $chaveEnv, true);

$telefoneAberto = $dadosForm['telefone'];
$ivBd = random_bytes(16);
$telefoneCriptografadoBd = bin2hex($ivBd) . ':' . openssl_encrypt($telefoneAberto, 'aes-256-cbc', $chaveBd, 0, $ivBd);

$nome = $dadosForm['nome'];
$email = $dadosForm['email'];
$endereco = $dadosForm['endereco'];
$datanascimento = $dadosForm['datanascimento'];
$senhaHash = password_hash($dadosForm['senha'], PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO usuarios (nome, email, telefone, endereco, data_nascimento, senha, email_verificado) VALUES (?, ?, ?, ?, ?, ?, 1)");
$stmt->bind_param("ssssss", $nome, $email, $telefoneCriptografadoBd, $endereco, $datanascimento, $senhaHash);
$stmt->execute();
$idInserido = $stmt->insert_id; 
$stmt->close();

$stmtSelect = $conn->prepare("SELECT nome, telefone FROM usuarios WHERE id = ?");
$stmtSelect->bind_param("i", $idInserido);
$stmtSelect->execute();
$usuarioRecuperado = $stmtSelect->get_result()->fetch_assoc();
$stmtSelect->close();

$partesTelefone = explode(':', $usuarioRecuperado['telefone']);
$ivRecuperado = hex2bin($partesTelefone[0]);
$telefoneEncriptado = $partesTelefone[1];

$telefoneDescriptografado = openssl_decrypt($telefoneEncriptado, 'aes-256-cbc', $chaveBd, 0, $ivRecuperado);

$infoCadastro = "Nome: " . $usuarioRecuperado['nome'] . ", Telefone: " . $telefoneDescriptografado;
$mensagemConsole = "({$usuarioSistema}:{$hostname}>{$infoCadastro})";
error_log($mensagemConsole);

echo json_encode([
    "success" => true,
    "redirect" => "login.html?status=cadastro_sucesso"
]);
?>