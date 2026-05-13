<?php
session_start();
require_once __DIR__ . '/config/app.php';

$isPost = $_SERVER["REQUEST_METHOD"] === "POST";
	
if (!isset($_SESSION['usuario_id'])) {
    if ($isPost) {
        header('Content-Type: application/json');
        echo json_encode([
            "success" => false,
            "message" => "Faça login para cadastrar uma roupa."
        ]);
        exit;
    }

    header("Location: Login.html");
    exit;
}

if ($isPost) {
	
    header('Content-Type: application/json');
    ini_set('display_errors', '0');
	
    $conn = db_connect('DB_NAME_ROUPAS');

    if ($conn->connect_error) {
        echo json_encode([
            "success" => false,
            "message" => "Erro de conexão com o banco"
        ]);
        exit;
    }

    db_ensure_roupa_schema($conn);
	
    $titulo = trim($_POST['titulo'] ?? '');
    $tipo   = trim($_POST['tipo'] ?? '');
    $tamanho = $_POST['tamanho'] ?? '';
    $sexo   = $_POST['sexo'] ?? '';
    $estado  = $_POST['estado'] ?? '';
    $local_doacao = trim($_POST['local_doacao'] ?? '');
    $id_usuario = (int) ($_SESSION['usuario_id'] ?? 0);
	
    if ($titulo === '' || $tipo === '' || $tamanho === '' || $sexo === '' || $estado === '' || $local_doacao === '') {
        echo json_encode([
            "success" => false,
            "message" => "Preencha todos os campos da roupa."
        ]);
        exit;
    }

    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            "success" => false,
            "message" => "Envie uma foto da peça de roupa."
        ]);
        exit;
    }

    $foto = $_FILES['foto'];
    $maxBytes = 5 * 1024 * 1024;

    if ($foto['size'] > $maxBytes) {
        echo json_encode([
            "success" => false,
            "message" => "A foto deve ter no máximo 5MB."
        ]);
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($foto['tmp_name']);
    $extensoesPermitidas = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($extensoesPermitidas[$mime])) {
        echo json_encode([
            "success" => false,
            "message" => "Envie uma foto JPG, PNG ou WEBP"
        ]);
        exit;
    }

    $uploadDir = __DIR__ . '/assets/uploads/roupas';
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0777, true)) {
        echo json_encode([
            "success" => false,
            "message" => "Não foi possível preparar a pasta de fotos."
        ]);
        exit;
    }

    if (!is_writable($uploadDir)) {
        @chmod($uploadDir, 0777);
    }

    if (!is_writable($uploadDir)) {
        echo json_encode([
            "success" => false,
            "message" => "A pasta de fotos não tem permissão de escrita."
        ]);
        exit;
    }

    $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensoesPermitidas[$mime];
    $destino = $uploadDir . '/' . $nomeArquivo;
    $fotoPath = 'assets/uploads/roupas/' . $nomeArquivo;

    if (!@move_uploaded_file($foto['tmp_name'], $destino)) {
        echo json_encode([
            "success" => false,
            "message" => "Não foi possível salvar a foto."
        ]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO roupas (titulo, tipo, tamanho, sexo, estado, local_doacao, foto_path, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        if (is_file($destino)) {
            unlink($destino);
        }

        echo json_encode([
            "success" => false,
            "message" => "Erro na preparação da query: " . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("sssssssi", $titulo, $tipo, $tamanho, $sexo, $estado, $local_doacao, $fotoPath, $id_usuario);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Roupa cadastrada com sucesso!"
        ]);
    } else {
        if (is_file($destino)) {
            unlink($destino);
        }

        echo json_encode([
            "success" => false,
            "message" => "Erro ao cadastrar roupa: " . $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Publicar Roupa</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>

<body class="auth-page">

    <div class="app-header">
        <a href="index.html" class="header-link btn-light">Voltar</a>
    </div>

    <div class="page-main">
        <div class="box">

            <h2>Publicar Roupa</h2>

            <p id="feedback"></p>

            <form id="cadastra" enctype="multipart/form-data">
	
                <label>Título da doação</label>
                <input type="text" name="titulo" maxlength="120" placeholder="Ex: Blusa de frio infantil" required>

                <label>Tipo da roupa</label>
                <input type="text" name="tipo" required>

                <label>Tamanho</label>
                <select name="tamanho" required>
                    <option value="">Selecione</option>
                    <option value="PP">PP</option>
                    <option value="P">P</option>
                    <option value="M">M</option>
                    <option value="G">G</option>
                    <option value="GG">GG</option>
                    <option value="XG">XG</option>
                </select>

                <label>Sexo</label>
                <select name="sexo">
                    <option value="Masculino">Masculino</option>
                    <option value="Feminino">Feminino</option>
                    <option value="Unissex">Unissex</option>
                </select>

                <label>Estado</label>
                <select name="estado">
                    <option value="Novo">Novo</option>
                    <option value="Semi-Novo">Semi-Novo</option>
                    <option value="Usado">Usado</option>
                </select>

                <label>Local da doação</label>
                <input type="text" name="local_doacao" placeholder="Ex: Centro, São Paulo" required>

                <label>Foto da peça</label>
                <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required>

                <button type="submit" class="btn-block">Cadastrar Roupa</button>
            </form>

        </div>
    </div>

    <script>
        document.getElementById("cadastra").addEventListener("submit", async function (e) {
            e.preventDefault();

            if (!this.checkValidity()) {
                this.classList.add("was-validated");
                return;
            }
            this.classList.add("was-validated");

            const feedback = document.getElementById("feedback");
            const formData = new FormData(this);

            try {
                const response = await fetch("CadastroRoupas.php", {
                    method: "POST",
                    body: formData
                });

                const data = await response.json();

                    feedback.textContent = data.message;
                    feedback.className = data.success ? "sucesso" : "erro";

                    if (data.success) {
                        setTimeout(() => {
                            window.location.href = "index.html";
                        }, 800);
                    }

            } catch (error) {
                console.error(error);
                feedback.textContent = "Erro interno no servidor. Recarregue a página e tente novamente.";
                feedback.className = "erro";
            }
        });
    </script>

</body>

</html>
