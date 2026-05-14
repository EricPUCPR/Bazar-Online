<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Login Admin</title>
<link rel="stylesheet" href="assets/css/app.css">
</head>

<body class="auth-page">

<div class="app-header">
    <a href="index.html" class="header-link btn-light">Voltar</a>
</div>

<div class="page-main">
<div class="box">

<h2 id="tituloAdmin">Login Admin</h2>

<form id="adminLoginForm">
    <div id="emailArea">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="Digite o e-mail admin" required>
    </div>

    <div id="codigoArea" class="two-factor-area">
        <label for="codigoAdmin">Código do e-mail</label>
        <input type="text" id="codigoAdmin" name="codigo" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" placeholder="Digite os 6 dígitos">
        <small>Enviamos um código para o e-mail admin.</small>
    </div>

    <span id="feedback"></span>

    <button type="submit" id="submitBtn" class="btn-block">Login</button>
</form>

</div>
</div>

<script>
const form = document.getElementById("adminLoginForm");
const tituloAdmin = document.getElementById("tituloAdmin");
const emailArea = document.getElementById("emailArea");
const codigoArea = document.getElementById("codigoArea");
const emailInput = document.getElementById("email");
const codigoInput = document.getElementById("codigoAdmin");
const submitBtn = document.getElementById("submitBtn");
const feedback = document.getElementById("feedback");
let aguardandoCodigo = false;

function setFeedback(message, status = "muted") {
    feedback.className = `feedback feedback-${status}`;
    feedback.textContent = message;
}

function mostrarCodigo() {
    aguardandoCodigo = true;
    tituloAdmin.textContent = "Digite o código do e-mail";
    emailArea.style.display = "none";
    codigoArea.classList.add("active");
    codigoInput.required = true;
    submitBtn.textContent = "Validar código";
    codigoInput.focus();
}

form.addEventListener("submit", async (event) => {
    event.preventDefault();
    setFeedback("Validando...");

    try {
        const formData = new FormData();
        const endpoint = aguardandoCodigo ? "admin_verifica_codigo.php" : "admin_autentica.php";

        if (aguardandoCodigo) {
            formData.append("codigo", codigoInput.value.trim());
        } else {
            formData.append("email", emailInput.value.trim());
        }

        const response = await fetch(endpoint, {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (!data.success) {
            setFeedback(data.mensagem || "Não foi possível validar.", "error");
            return;
        }

        setFeedback(data.mensagem || "Código enviado.", "success");

        if (!aguardandoCodigo) {
            mostrarCodigo();
            return;
        }

        setTimeout(() => {
            window.location.href = "index.html";
        }, 800);
    } catch (error) {
        console.error(error);
        setFeedback("Erro ao conectar com o servidor.", "error");
    }
});
</script>

</body>
</html>
