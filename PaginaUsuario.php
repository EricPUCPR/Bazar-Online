<?php
session_start();
require_once __DIR__ . '/config/app.php';

$conn = db_connect('DB_NAME_USUARIOS');

if ($conn->connect_error) {
    http_response_code(500);
    exit("Erro de conexão com o banco.");
}

db_ensure_usuario_schema($conn);

if (!isset($_SESSION['usuario_id'])) {
    header("Location: Login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['field']) && isset($_POST['value'])) {
    header('Content-Type: application/json');
    $field = $_POST['field'];
    $value = trim($_POST['value']);
    $id_usuario = (int) $_SESSION['usuario_id'];

    $allowed_fields = ['nome', 'telefone', 'endereco', 'data_nascimento'];
    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['success' => false, 'message' => 'Campo inválido.']);
        exit;
    }

    if ($value !== '') {
        if ($field === 'nome' && !preg_match('/^[a-zA-ZÀ-ÿ\s]{8,}$/u', $value)) {
            echo json_encode(['success' => false, 'message' => 'O nome deve conter apenas letras e ter no mínimo 8 caracteres.']);
            exit;
        }
        if ($field === 'data_nascimento') {
            $dataAtual = new DateTime();
            $dataNascObj = DateTime::createFromFormat('Y-m-d', $value);
            $idade = $dataNascObj ? $dataNascObj->diff($dataAtual)->y : -1;
            if (!$dataNascObj || $idade < 18 || $idade > 120 || $dataNascObj > $dataAtual) {
                echo json_encode(['success' => false, 'message' => 'A idade deve ser entre 18 e 120 anos.']);
                exit;
            }
        }
    }

    $val_to_save = ($value === '') ? NULL : $value;
    if ($field === 'nome' && $value === '') {
        $val_to_save = '';
    }

    $stmt = $conn->prepare("UPDATE usuarios SET {$field} = ? WHERE id = ?");
    $stmt->bind_param("si", $val_to_save, $id_usuario);
    
    if ($stmt->execute()) {
        if ($field === 'nome') {
            $_SESSION['usuario_nome'] = $value;
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar.']);
    }
    $stmt->close();
    exit;
}

$id = (int) $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT nome, email, telefone, endereco, data_nascimento, is_admin FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

$isAdminAccount = (int) ($user['is_admin'] ?? 0) === 1;
$isAdmin = $isAdminAccount && !empty($_SESSION['admin_logado']);

if ($isAdminAccount && !$isAdmin) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

$mensagemPerfil = "";

if (isset($_GET['excluir'])) {
    if ($isAdmin) {
        $mensagemPerfil = "A conta admin não pode ser excluída por esta página.";
    } else {
        app_log_event(
            'Exclusão de conta',
            'Usuário excluiu a própria conta.',
            $id,
            $user['nome'] ?? null,
            $user['email'] ?? null
        );

        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        session_destroy();
        header("Location: Login.html");
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
    .edit-btn { cursor: pointer; margin-left: 10px; font-size: 0.9em; }
    .edit-input { font-size: 1em; padding: 2px 5px; margin-left: 5px; border: 1px solid #ccc; border-radius: 4px; }
</style>
</head>

<body class="profile-page">

<div class="app-header">
    <a href="index.html" class="header-link btn-primary">Voltar</a>
    <a href="logout.php" class="header-link btn-light logout" onclick="return confirm('Tem certeza que deseja sair?')">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <path d="M16 17l5-5-5-5"></path>
            <path d="M21 12H9"></path>
        </svg>
        Sair
    </a>
</div>

<div class="page-main">

<div class="box">

<h2>Meu Perfil</h2>

<?php if ($user): ?>
<?php if ($mensagemPerfil): ?>
<p class="erro"><?= e($mensagemPerfil) ?></p>
<?php endif; ?>

<div class="profile-info"><b>Nome:</b> <span id="display-nome"><?= e($user['nome'] ?? '') ?></span> <span class="edit-btn" onclick="editField('nome')">📝</span></div>
<div class="profile-info"><b>E-mail:</b> <?= e($user['email'] ?? '') ?></div>
<div class="profile-info"><b>Telefone:</b> <span id="display-telefone"><?= e($user['telefone'] ?? '') ?></span> <span class="edit-btn" onclick="editField('telefone')">📝</span></div>
<div class="profile-info"><b>Endereço:</b> <span id="display-endereco"><?= e($user['endereco'] ?? '') ?></span> <span class="edit-btn" onclick="editField('endereco')">📝</span></div>
<div class="profile-info"><b>Nascimento:</b> <span id="display-data_nascimento"><?= e($user['data_nascimento'] ?? '') ?></span> <span class="edit-btn" onclick="editField('data_nascimento')">📝</span></div>

<?php if (!$isAdmin): ?>
    <a class="btn btn-block btn-danger"
       href="?excluir=1"
       onclick="return confirm('Tem certeza que deseja excluir sua conta?')">
       Excluir minha conta
    </a>
<?php endif; ?>

<?php else: ?>
<p>Usuário não encontrado.</p>
<?php endif; ?>

</div>

</div>

<script>
function editField(field) {
    const displaySpan = document.getElementById('display-' + field);
    if (displaySpan.querySelector('input')) return;

    const currentValue = displaySpan.innerText.trim();
    
    let inputType = 'text';
    if (field === 'data_nascimento') inputType = 'date';
    else if (field === 'telefone') inputType = 'tel';

    const input = document.createElement('input');
    input.type = inputType;
    input.value = currentValue;
    input.className = 'edit-input';
    
    if (field === 'telefone') {
        input.addEventListener("input", () => {
            let valor = input.value.replace(/\D/g, "").slice(0, 11);
            if (valor.length > 2) {
                valor = `(${valor.slice(0, 2)}) ${valor.slice(2)}`;
            }
            if (valor.length > 10) {
                valor = `${valor.slice(0, 10)}-${valor.slice(10)}`;
            }
            input.value = valor;
        });
    }

    displaySpan.innerHTML = '';
    displaySpan.appendChild(input);
    input.focus();

    input.addEventListener('keydown', async function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const value = input.value;
            const formData = new FormData();
            formData.append('field', field);
            formData.append('value', value);

            try {
                const response = await fetch('PaginaUsuario.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    displaySpan.textContent = value;
                } else {
                    alert(data.message);
                }
            } catch(err) {
                alert('Erro ao salvar as informações.');
            }
        }
    });
}
</script>

</body>
</html>