<?php
session_start();
require_once __DIR__ . '/config/app.php';

$conn = db_connect('DB_NAME_USUARIOS');

if ($conn->connect_error) {
    http_response_code(500);
    exit("Erro de conexão com o banco.");
}

// Se a função existir na sua config, garante o schema
if (function_exists('db_ensure_usuario_schema')) {
    db_ensure_usuario_schema($conn);
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: auth/Login.html");
    exit;
}

$id = (int) $_SESSION['usuario_id'];

if (isset($_GET['excluir_campo'])) {
    $campoParaApagar = $_GET['excluir_campo'];
    $camposPermitidos = ['telefone', 'endereco', 'data_nascimento']; 

    if (in_array($campoParaApagar, $camposPermitidos)) {
        $stmt = $conn->prepare("UPDATE usuarios SET $campoParaApagar = NULL WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }
}

$query = "SELECT nome, email, telefone, endereco, data_nascimento, is_admin, telegram_chat_id, pergunta_seguranca FROM usuarios WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Erro no banco de dados. Você executou o 'ALTER TABLE' para adicionar as colunas is_admin, telegram_chat_id, etc.? Erro técnico: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

$telefoneExibicao = 'Não informado';

if ($user && !empty($user['telefone'])) {
    $partesTelefone = explode(':', $user['telefone']);
    
    // Verifica se está no formato criptografado (IV:Criptograma)
    if (count($partesTelefone) === 2) {
        $chaveEnv = env_value('CHAVE_SIMETRICA'); 
        $chaveBd = hash('sha256', $chaveEnv, true);
        
        $ivRecuperado = hex2bin($partesTelefone[0]);
        $telefoneEncriptado = $partesTelefone[1];
        
        $telefoneDescriptografado = openssl_decrypt($telefoneEncriptado, 'aes-256-cbc', $chaveBd, 0, $ivRecuperado);
        
        if ($telefoneDescriptografado !== false) {
            $telefoneExibicao = $telefoneDescriptografado;
        } else {
            $telefoneExibicao = 'Erro ao ler telefone';
        }
    } else {
        // Caso o banco tenha um número antigo que não foi criptografado
        $telefoneExibicao = $user['telefone'];
    }
}

$isAdminAccount = (int) ($user['is_admin'] ?? 0) === 1;
$isAdmin = $isAdminAccount && !empty($_SESSION['admin_logado']);

if ($isAdminAccount && !$isAdmin) {
    session_destroy();
    header("Location: admin/admin_login.php");
    exit;
}

$mensagemPerfil = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['telegram_chat_id'])) {
    $telegramChatId = trim($_POST['telegram_chat_id']);

    if ($telegramChatId === '') {
        $stmt = $conn->prepare("UPDATE usuarios SET telegram_chat_id = NULL WHERE id = ?");
        $stmt->bind_param("i", $id);
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET telegram_chat_id = ? WHERE id = ?");
        $stmt->bind_param("si", $telegramChatId, $id);
    }

    if ($stmt && $stmt->execute()) {
        $mensagemPerfil = "Telegram atualizado com sucesso.";
    } else {
        $mensagemPerfil = "Não foi possível atualizar o Telegram.";
    }
    if ($stmt) $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pergunta_seguranca'], $_POST['resposta_seguranca'])) {
    $pergunta = trim($_POST['pergunta_seguranca']);
    $resposta = trim($_POST['resposta_seguranca']);

    if ($pergunta === '' || $resposta === '') {
        $mensagemPerfil = "Preencha a pergunta e a resposta.";
    } else {
        $respostaHash = password_hash(strtolower($resposta), PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE usuarios SET pergunta_seguranca = ?, resposta_seguranca_hash = ? WHERE id = ?");
        
        if ($stmt) {
            $stmt->bind_param("ssi", $pergunta, $respostaHash, $id);
            if ($stmt->execute()) {
                $mensagemPerfil = "Pergunta de segurança salva com sucesso.";
            } else {
                $mensagemPerfil = "Não foi possível salvar a pergunta.";
            }
            $stmt->close();
        } else {
            $mensagemPerfil = "Erro de banco ao tentar salvar a pergunta.";
        }
    }
}

if (isset($_GET['excluir'])) {
    if ($isAdmin) {
        $mensagemPerfil = "A conta admin não pode ser excluída por esta página.";
    } else {
        if (function_exists('app_log_event')) {
            app_log_event('Exclusão de conta', 'Usuário excluiu a própria conta.', $id, $user['nome'] ?? null, $user['email'] ?? null);
        }

        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        session_destroy();
        header("Location: auth/Login.html");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Meu Perfil</title>
<link rel="stylesheet" href="assets/css/app.css">
<style>
    .profile-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding: 10px;
        background-color: #f0f4f8; 
        border-radius: 6px;
    }
    .btn-deletar-campo {
        background: none;
        border: none;
        color: #ff4d4d;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        padding-left: 10px;
    }
    .btn-deletar-campo:hover {
        color: #ff0000;
        font-weight: bold;
    }
</style>
</head>

<body class="profile-page">

<div class="app-header">
    <a href="index.html" class="header-link btn-primary">Voltar</a>
    <a href="auth/logout.php" class="header-link btn-light logout" onclick="return confirm('Tem certeza que deseja sair?')">
        <svg viewBox="0 0 24 24" aria-hidden="true" width="20" height="20">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" fill="none" stroke="currentColor" stroke-width="2"></path>
            <path d="M16 17l5-5-5-5" fill="none" stroke="currentColor" stroke-width="2"></path>
            <path d="M21 12H9" fill="none" stroke="currentColor" stroke-width="2"></path>
        </svg>
        Sair
    </a>
</div>

<div class="page-main">
<div class="box">

<h2>Meu Perfil</h2>

<?php if ($user): ?>
    <?php if ($mensagemPerfil): ?>
        <p class="erro" style="color: red; font-weight: bold; margin-bottom: 15px;"><?= e($mensagemPerfil) ?></p>
    <?php endif; ?>

    <div class="profile-info">
        <span><b>Nome:</b> <?= e($user['nome'] ?? '') ?></span>
    </div>

    <div class="profile-info">
        <span><b>E-mail:</b> <?= e($user['email'] ?? '') ?></span>
    </div>

    <div class="profile-info">
        <span><b>Telefone:</b> <?= e($telefoneExibicao) ?></span>
        <?php if (!empty($user['telefone'])): ?>
            <a href="?excluir_campo=telefone" class="btn-deletar-campo" onclick="return confirm('Tem certeza que deseja excluir seu telefone?')">❌</a>
        <?php endif; ?>
    </div>

    <div class="profile-info">
        <span><b>Endereço:</b> <?= e($user['endereco'] ?? 'Não informado') ?></span>
        <?php if (!empty($user['endereco'])): ?>
            <a href="?excluir_campo=endereco" class="btn-deletar-campo" onclick="return confirm('Tem certeza que deseja excluir seu endereço?')">❌</a>
        <?php endif; ?>
    </div>

    <div class="profile-info">
        <span><b>Nascimento:</b> <?= e($user['data_nascimento'] ?? 'Não informado') ?></span>
        <?php if (!empty($user['data_nascimento'])): ?>
            <a href="?excluir_campo=data_nascimento" class="btn-deletar-campo" onclick="return confirm('Tem certeza que deseja excluir sua data de nascimento?')">❌</a>
        <?php endif; ?>
    </div>

    <?php if ($isAdmin): ?>
        <hr>
        <h3>Área Administrativa</h3>
        <div class="profile-info">
            <span><b>Telegram Chat ID:</b> <?= e($user['telegram_chat_id'] ?? 'Não vinculado') ?></span>
        </div>

        <form method="POST" style="margin-bottom: 20px;">
            <label for="telegram_chat_id">Vincular Telegram</label>
            <input type="text" id="telegram_chat_id" name="telegram_chat_id" placeholder="Digite seu Chat ID do Telegram" value="<?= e($user['telegram_chat_id'] ?? '') ?>">
            <button type="submit" class="btn btn-block" style="margin-top: 10px;">Salvar Telegram</button>
        </form>

        <form method="POST">
            <label for="pergunta_seguranca">Pergunta de segurança</label>
            <select id="pergunta_seguranca" name="pergunta_seguranca" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                <option value="">Selecione uma pergunta</option>
                <option value="Qual o nome do seu primeiro animal?">Qual o nome do seu primeiro animal?</option>
                <option value="Qual a cidade onde você nasceu?">Qual a cidade onde você nasceu?</option>
                <option value="Qual o nome da sua escola de infância?">Qual o nome da sua escola de infância?</option>
                <option value="Qual o nome da sua mãe?">Qual o nome da sua mãe?</option>
                <option value="Qual foi seu primeiro videogame?">Qual foi seu primeiro videogame?</option>
            </select>

            <label for="resposta_seguranca">Resposta</label>
            <input type="password" id="resposta_seguranca" name="resposta_seguranca" placeholder="Digite a resposta" style="width: 100%; padding: 8px; margin-bottom: 10px;">
            <button type="submit" class="btn btn-block">Salvar pergunta</button>
        </form>
    <?php endif; ?>

    <br>

    <?php if (!$isAdmin): ?>
        <a class="btn btn-block btn-danger" href="?excluir=1" onclick="return confirm('Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.')" style="background-color: #ff4d4d; color: white; text-align: center; display: block; padding: 10px; text-decoration: none; border-radius: 5px;">
           Excluir minha conta
        </a>
    <?php endif; ?>

<?php else: ?>
    <p>Usuário não encontrado.</p>
<?php endif; ?>

</div>
</div>

</body>
</html>