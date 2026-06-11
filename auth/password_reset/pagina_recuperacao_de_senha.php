<?php
require_once __DIR__ . '/../../config/app.php';

$mysqli = db_connect('DB_NAME_USUARIOS');

function normalizarTexto($texto) {
    $texto = trim((string)$texto);
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = preg_replace('/[^a-z0-9 ]/', '', $texto);
    return $texto;
}

$regexSequenciaNumerica = "/(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/";

if ($mysqli->connect_error) {
    http_response_code(500);
    exit("Erro de conexão com o banco.");
}

db_ensure_usuario_schema($mysqli);

$token = $_GET['token'] ?? '';
$erro_token = false;
$sucesso = false;
$nome_usuario_token = "";

if (empty($token)) {
    $erro_token = "Token ausente.";
} else {
    $stmt = $mysqli->prepare("SELECT email, nome FROM usuarios WHERE recuperacao_token = ? AND recuperacao_expira > NOW() LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $erro_token = "Este link expirou ou é inválido.";
        } else {
            $usuario = $result->fetch_assoc();
            $nome_usuario_token = $usuario["nome"] ?? "";
        }
        $stmt->close();
    } else {
        $erro_token = "Erro interno ao validar o token.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$erro_token) {
    $nova_senha = $_POST["senha"] ?? "";
    $confirmar_senha = $_POST["confirmar_senha"] ?? "";
    $nome_referencia = $_POST["nome_referencia"] ?? $nome_usuario_token;
    $senhaForte = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/";
    $senhaNormalizada = normalizarTexto($nova_senha);
    $partesNome = [];
    foreach (preg_split('/\s+/', normalizarTexto($nome_referencia)) as $parte) {
        if (strlen($parte) >= 3) {
            $partesNome[] = $parte;
        }
    }
    $contemNome = false;

    foreach ($partesNome as $parte) {
        if (strpos($senhaNormalizada, $parte) !== false) {
            $contemNome = true;
            break;
        }
    }

    if ($nova_senha === "" || $confirmar_senha === "") {
        $erro_token = "Preencha as senhas corretamente.";
    } elseif ($nova_senha !== $confirmar_senha) {
        $erro_token = "As senhas não coincidem!";
    } elseif (!preg_match($senhaForte, $nova_senha)) {
        $erro_token = "Senha fraca!";
    } elseif (preg_match($regexSequenciaNumerica, $nova_senha)) {
        $erro_token = "A senha não pode conter sequência de números.";
    } elseif ($contemNome) {
        $erro_token = "A senha não pode conter o seu nome.";
    } else {
        $erro_token = false;
    }

    if (!$erro_token) {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        $update = $mysqli->prepare("
            UPDATE usuarios
            SET senha = ?,
                recuperacao_token = NULL,
                recuperacao_expira = NULL
            WHERE recuperacao_token = ?
        ");
        if ($update) {
            $update->bind_param("ss", $senha_hash, $token);
            if ($update->execute()) {
                if ($update->affected_rows > 0) {
                    $sucesso = true;
                } else {
                    $erro_token = "Erro ao atualizar a senha.";
                }
            } else {
                $erro_token = "Erro ao atualizar a senha.";
            }
            $update->close();
        } else {
            $erro_token = "Erro interno ao atualizar a senha.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperação de Senha</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/app.css">
</head>

<body class="auth-page">

<div class="app-header">
    <a href="../login.html" class="header-link btn-light">Login</a>
    <a href="../../index.html" class="header-link btn-primary">Voltar</a>
</div>

<div class="page-main">
    <div class="box">
        <?php if ($erro_token): ?>
            <p class="msg msg-erro"><?= e($erro_token) ?></p>
        <?php elseif ($sucesso): ?>
            <p class="msg msg-ok">Senha atualizada com sucesso! Faça login novamente.</p>
        <?php else: ?>
            <h2>Nova senha</h2>
            <p>Digite abaixo sua nova senha:</p>
            <div class="regras-senha">
                <strong>Sua senha deve conter:</strong>
                <ul>
                    <li id="req-length" class="invalid">No mínimo 8 caracteres</li>
                    <li id="req-upper" class="invalid">1 Letra maiúscula</li>
                    <li id="req-lower" class="invalid">1 Letra minúscula</li>
                    <li id="req-number" class="invalid">1 Número</li>
                    <li id="req-special" class="invalid">1 Símbolo</li>
                    <li id="req-no-seq" class="invalid">Não é permitida sequência numérica</li>
                    <li id="req-no-name" class="invalid">Não é permitido nome próprio</li>
                </ul>
            </div>

            <form id="formSenha" method="POST">
                <input type="hidden" id="nome_referencia" name="nome_referencia" value="<?= e($nome_usuario_token) ?>">
                
                <div class="password-wrapper">
                    <input type="password" name="senha" id="password" required placeholder="Nova senha">
                    <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                </div>
                <span id="erro-regex">Senha fraca!</span>

                <div class="password-wrapper">
                    <input type="password" name="confirmar_senha" id="confirmar_senha" required placeholder="Confirme a senha">
                    <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('confirmar_senha', this)"></i>
                </div>
                <span id="erro-match">As senhas não coincidem.</span>

                <button type="submit" class="btn-block">Atualizar senha</button>
            </form>
        <?php endif; ?>

        <a href="../login.html" class="form-link">Voltar para o Login</a>
    </div>
</div>

<script>
function togglePassword(id, el) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
        el.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = "password";
        el.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
<script src="password_reset.js"></script>

</body>
</html>
