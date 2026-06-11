<?php
require_once __DIR__ . '/../config/app.php';

$mensagem = "Não foi possível validar o e-mail.";
$ok = false;

$conn = db_connect('DB_NAME_USUARIOS');

if ($conn->connect_error) {
    $mensagem = "Erro de conexão com o banco.";
} else {
    db_ensure_usuario_schema($conn);

    $token = trim($_GET["token"] ?? "");

    if ($token === "") {
        $mensagem = "Link de validação inválido.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE confirmacao_token = ? AND confirmacao_expira > NOW() LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                if ($row) {
                    $idUsuario = (int) $row['id'];
                    $stmt->close();

                    $update = $conn->prepare("
                        UPDATE usuarios
                        SET email_verificado = 1,
                            confirmacao_token = NULL,
                            confirmacao_expira = NULL
                        WHERE id = ?
                    ");

                    if ($update) {
                        $update->bind_param("i", $idUsuario);
                        $ok = $update->execute();
                        $update->close();
                        $mensagem = $ok ? "O e-mail foi validado com sucesso." : "Erro interno ao validar o e-mail.";
                        if ($ok) {
                            app_log_event(
                                'Validação de e-mail',
                                'Usuário validou o e-mail da conta.',
                                $idUsuario,
                                null,
                                null
                            );
                        }
                    } else {
                        $mensagem = "Erro interno ao validar o e-mail.";
                    }
                } else {
                    $mensagem = "Link de validação inválido ou expirado.";
                    $stmt->close();
                }
            } else {
                $stmt->close();
            }
        } else {
            $mensagem = "Erro interno ao validar o e-mail.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Validação de E-mail</title>
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="status-page">

<div class="status-card">
    <p class="status-message <?php echo $ok ? '' : 'error'; ?>"><?php echo e($mensagem); ?></p>
    <a class="form-link" href="login.html">Ir para o Login</a>
</div>

</body>
</html>
