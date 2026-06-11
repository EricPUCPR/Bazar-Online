<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Login Admin</title>
<link rel="stylesheet" href="../assets/css/app.css">
</head>

<body class="auth-page">

<div class="app-header">
    <a href="../index.html" class="header-link btn-light">Voltar</a>
</div>

<div class="page-main">
<div class="box">

<h2 id="tituloAdmin">Login Admin</h2>

<form id="adminLoginForm">
    <div id="emailArea">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="Digite o e-mail admin" required>
    </div>

    <div id="metodoArea">
        <label for="metodo">Método de autenticação</label>
        <select id="metodo" name="metodo">
            <option value="telegram">Telegram</option>
            <option value="email">E-mail</option>
            <option value="pergunta">Pergunta de segurança</option>
        </select>
    </div>

    <div id="codigoArea" class="two-factor-area">
        <label for="codigoAdmin">Código do e-mail</label>
        <input type="text" id="codigoAdmin" name="codigo" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" placeholder="Digite os 6 dígitos">
        <small>Enviamos um código para o e-mail admin.</small>
    </div>

    <div id="perguntaArea" class="two-factor-area">
    <label id="textoPergunta" for="respostaSeguranca">Pergunta de segurança</label>
    <input 
        type="password" 
        id="respostaSeguranca" 
        name="resposta_seguranca" 
        placeholder="Digite sua resposta"
    >
    <small>Responda a pergunta cadastrada no seu perfil.</small>
</div>

    <span id="feedback"></span>

    <button type="submit" id="submitBtn" class="btn-block">Login</button>
</form>

</div>
</div>

<script>
const metodoInput = document.getElementById("metodo");
const emailInput = document.getElementById("email");
const form = document.getElementById("adminLoginForm");
const tituloAdmin = document.getElementById("tituloAdmin");
const emailArea = document.getElementById("emailArea");
const codigoArea = document.getElementById("codigoArea");
const codigoInput = document.getElementById("codigoAdmin");
const submitBtn = document.getElementById("submitBtn");
const feedback = document.getElementById("feedback");
const perguntaArea = document.getElementById("perguntaArea");
const textoPergunta = document.getElementById("textoPergunta");
const respostaSegurancaInput = document.getElementById("respostaSeguranca");
let aguardandoPergunta = false;
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
        let endpoint = "admin_autentica.php";

        if (aguardandoCodigo) {
            endpoint = "admin_verifica_codigo.php";
        }

        if (aguardandoPergunta) {
            endpoint = "admin_verifica_pergunta.php";
        }

        if (aguardandoPergunta) {
            formData.append("resposta_seguranca", respostaSegurancaInput.value.trim());
        } else if (aguardandoCodigo) {
            formData.append("codigo", codigoInput.value.trim());
        } else {
            formData.append("email", emailInput.value.trim());
            formData.append("metodo", metodoInput.value);
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

        if (!aguardandoCodigo && !aguardandoPergunta) {

            if (data.pergunta) {

            aguardandoPergunta = true;

            tituloAdmin.textContent = "Pergunta de segurança";

            emailArea.style.display = "none";
            metodoArea.style.display = "none";

            perguntaArea.classList.add("active");

            textoPergunta.textContent = data.texto_pergunta;

            respostaSegurancaInput.required = true;
            respostaSegurancaInput.focus();

            return;
        }

    mostrarCodigo();
    return;
}

        setTimeout(() => {
            window.location.href = "../index.html";
        }, 800);
    } catch (error) {
        console.error(error);
        setFeedback("Erro ao conectar com o servidor.", "error");
    }
});
</script>

</body>
</html>
