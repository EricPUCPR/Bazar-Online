<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Cadastro</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body class="auth-page">

    <div class="app-header">
        <a href="login.html" class="header-link btn-light">Login</a>
        <a href="../index.html" class="header-link btn-primary">Voltar</a>
    </div>

    <?php
    require __DIR__ . '/../vendor/mailer/PHPMailer/src/Exception.php';
    require __DIR__ . '/../vendor/mailer/PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/../vendor/mailer/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/../config/app.php';
    function normalizarTexto($texto)
    {
        $texto = trim((string) $texto);
        if (function_exists('mb_strtolower')) {
            $texto = mb_strtolower($texto, 'UTF-8');
        } else {
            $texto = strtolower($texto);
        }
        if (function_exists('iconv')) {
            $convertido = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
            if ($convertido !== false) {
                $texto = $convertido;
            }
        }
        $texto = preg_replace('/[^a-z0-9 ]/', '', $texto);
        return $texto;
    }

    $regexSequenciaNumerica = "/(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/";
    $erro = "";
    $sucesso = "";

    $conn = db_connect('DB_NAME_USUARIOS');

    if ($conn->connect_error) {
        $erro = "Erro de conexão com o banco.";
    } else {
        db_ensure_usuario_schema($conn);
    }
    ?>

    <div class="page-main">
        <div class="box">

            <h2>Cadastro</h2>

            <?php if ($erro): ?>
                <p class="erro"><?= e($erro) ?></p>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <p class="ok"><?= e($sucesso) ?></p>
            <?php endif; ?>

            <form method="POST" id="formCadastro">
                

                <input type="text" id="nome" name="nome" placeholder="Nome completo" pattern="[a-zA-ZÀ-ÿ\s]{8,}"
                    title="O nome deve conter apenas letras e ter no mínimo 8 caracteres." value="<?= e($_POST['nome'] ?? '') ?>" required>
                <input type="email" name="email" id="email" placeholder="E-mail" pattern="[a-zA-Z0-9._]+@[a-zA-Z]+(\.[a-zA-Z]+)+"
                    title="O e-mail deve conter nome, pontos ou números antes do @, e ter domínios compostos apenas por letras."
                    value="<?= e($_POST['email'] ?? '') ?>" required>
                <input type="date" name="datanascimento" id="datanascimento" min="<?= date('Y-m-d', strtotime('-120 years')) ?>"
                    max="<?= date('Y-m-d', strtotime('-18 years')) ?>" value="<?= e($_POST['datanascimento'] ?? '') ?>" required>
                <input type="tel" id="telefone" name="telefone" placeholder="Telefone" inputmode="numeric"
                    maxlength="15" value="<?= e($_POST['telefone'] ?? '') ?>" required>
                <input type="text" name="endereco" id="endereco" placeholder="Endereço" value="<?= e($_POST['endereco'] ?? '') ?>" required>

                <div class="password-wrapper">
                    <input type="password" id="senha" name="senha" placeholder="Senha" value="<?= e($_POST['senha'] ?? '') ?>" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('senha', this)"
                        aria-label="Visualizar senha">👁</button>
                </div>

                <div class="password-wrapper">
                    <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Confirmar senha"
                        value="<?= e($_POST['confirmar_senha'] ?? '') ?>" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('confirmar_senha', this)"
                        aria-label="Visualizar confirmar senha">👁</button>
                </div>

                <div id="requisitos">
                    <span id="r1">• No mínimo 8 caracteres</span>
                    <span id="r2">• 1 Letra maiúscula</span>
                    <span id="r3">• 1 Letra minúscula</span>
                    <span id="r4">• 1 Número</span>
                    <span id="r5">• 1 Símbolo</span>
                    <span id="r6">• Não é permitida sequência numérica</span>
                    <span id="r7">• Não é permitido nome próprio</span>
                </div>

                <div class="terms-check">
                    <label>
                        <input type="checkbox" id="termos" name="termos" <?= isset($_POST['termos']) ? 'checked' : '' ?> required>
                        Eu aceito os <a href="../legal/terms/index.html" target="_blank">termos de uso</a>
                    </label>
                </div>

                <div id="captcha-container" class="g-recaptcha" style="margin-top: 20px; display: none; justify-content: center;" data-sitekey="<?= env_value('RECAPTCHA_SITE_KEY') ?>"></div>

                <button type="submit" class="btn-block" style="margin-top: 15px;">Cadastrar</button>

            </form>

            <a href="login.html" class="form-link">Já tenho cadastro</a>

        </div>
    </div>

    <script>
        const senha = document.getElementById("senha");
        const requisitos = document.getElementById("requisitos");
        const telefone = document.getElementById("telefone");
        const nome = document.getElementById("nome");
        const confirmarSenha = document.getElementById("confirmar_senha");
        const termos = document.getElementById("termos");
        const form = document.getElementById("formCadastro");
        const captchaContainer = document.getElementById("captcha-container");

        function togglePassword(id, botao) {
            const campo = document.getElementById(id);
            if (!campo) return;

            if (campo.type === "password") {
                campo.type = "text";
                botao.textContent = "🙈";
            } else {
                campo.type = "password";
                botao.textContent = "👁";
            }
        }

        function validarCaptchaVisibilidade() {
            const val = senha.value;
            const senhaNormalizada = val
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/[^a-z0-9]/g, "");

            const partesNome = (nome.value || "")
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .split(/\s+/)
                .map((parte) => parte.replace(/[^a-z0-9]/g, ""))
                .filter((parte) => parte.length >= 3);

            const temSequenciaNumerica = /(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/.test(val);
            const contemNome = partesNome.some((parte) => senhaNormalizada.includes(parte));

            const checks = [
                val.length >= 8,
                /[A-Z]/.test(val),
                /[a-z]/.test(val),
                /\d/.test(val),
                /[@$!%*?&]/.test(val),
                !temSequenciaNumerica,
                !contemNome
            ];

            const senhasConferem = (val === confirmarSenha.value && val !== "");
            const requisitosSenhaOk = checks.every(c => c);
            const formValido = form.checkValidity();

            if (formValido && senhasConferem && requisitosSenhaOk && termos.checked) {
                captchaContainer.style.display = "flex";
            } else {
                captchaContainer.style.display = "none";
            }

            return checks;
        }

        senha.addEventListener("input", () => {
            const val = senha.value;
            const checks = validarCaptchaVisibilidade();

            if (val.length === 0 || checks.every(c => c)) {
                requisitos.classList.remove("is-visible");
            } else {
                requisitos.classList.add("is-visible");
            }

            document.getElementById("r1").className = checks[0] ? "ok" : "erro";
            document.getElementById("r2").className = checks[1] ? "ok" : "erro";
            document.getElementById("r3").className = checks[2] ? "ok" : "erro";
            document.getElementById("r4").className = checks[3] ? "ok" : "erro";
            document.getElementById("r5").className = checks[4] ? "ok" : "erro";
            document.getElementById("r6").className = checks[5] ? "ok" : "erro";
            document.getElementById("r7").className = checks[6] ? "ok" : "erro";
        });

        nome.addEventListener("input", () => {
            senha.dispatchEvent(new Event("input"));
            validarCaptchaVisibilidade();
        });

        confirmarSenha.addEventListener("input", validarCaptchaVisibilidade);
        termos.addEventListener("change", validarCaptchaVisibilidade);
        
        form.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', validarCaptchaVisibilidade);
        });

        telefone.addEventListener("input", () => {
            let valor = telefone.value.replace(/\D/g, "").slice(0, 11);

            if (valor.length > 2) {
                valor = `(${valor.slice(0, 2)}) ${valor.slice(2)}`;
            }

            if (valor.length > 10) {
                valor = `${valor.slice(0, 10)}-${valor.slice(10)}`;
            }

            telefone.value = valor;
        });

        document.getElementById('formCadastro').addEventListener('submit', async function (e) {
            e.preventDefault();

            if (!this.checkValidity()) {
                this.classList.add('was-validated');
                return;
            }

            await criptografarCadastro();
        });

        window.addEventListener("load", () => {
            validarCaptchaVisibilidade();
            senha.dispatchEvent(new Event("input"));
        });

        function arrayBufferToBase64(buffer) {
            const bytes = new Uint8Array(buffer);

            let binary = "";

            for (let i = 0; i < bytes.length; i++) {
                binary += String.fromCharCode(bytes[i]);
            }

            return btoa(binary);
        }

        async function criptografarCadastro() {
            const dadosCadastro = {
                nome: document.getElementById("nome").value,
                email: document.getElementById("email").value,
                datanascimento: document.getElementById("datanascimento").value,
                telefone: document.getElementById("telefone").value,
                endereco: document.getElementById("endereco").value,
                senha: document.getElementById("senha").value,
                confirmar_senha: document.getElementById("confirmar_senha").value,
                termos: document.getElementById("termos").checked ? "on" : "",
                "g-recaptcha-response": grecaptcha.getResponse()
            };


            const publicKeyDer = await fetch("../crypto/public_key.php")
                .then(response => response.arrayBuffer());

            console.log("Chave pública recebida do servidor:", publicKeyDer);

            const publicKey = await crypto.subtle.importKey(
                "spki",
                publicKeyDer,
                {
                    name: "RSA-OAEP",
                    hash: "SHA-1"
                },
                false,
                ["encrypt"]
            );

            const aesKey = await crypto.subtle.generateKey(
                {
                    name: "AES-GCM",
                    length: 256
                },
                true,
                ["encrypt"]
            );

            console.log("Chave AES de sessão gerada:", aesKey);

            const aesRaw = await crypto.subtle.exportKey("raw", aesKey);

            const iv = crypto.getRandomValues(new Uint8Array(12));

            const dadosTexto = JSON.stringify(dadosCadastro);

            const dadosCriptografados = await crypto.subtle.encrypt(
                {
                    name: "AES-GCM",
                    iv: iv
                },
                aesKey,
                new TextEncoder().encode(dadosTexto)
            );

            const chaveAesCriptografada = await crypto.subtle.encrypt(
                {
                    name: "RSA-OAEP"
                },
                publicKey,
                aesRaw
            );

            const pacote = {
                key: arrayBufferToBase64(chaveAesCriptografada),
                iv: arrayBufferToBase64(iv),
                data: arrayBufferToBase64(dadosCriptografados)
            };

            console.log("Pacote criptografado enviado ao back:", pacote);

            const resposta = await fetch("../crypto/receber_cadastro_criptografado.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(pacote)
            });

            const resultado = await resposta.json();

            console.log("Resposta do back:", resultado);

            if (resultado.success && resultado.redirect) {
                window.location.href = resultado.redirect;
            } else {
                alert(resultado.mensagem || "Processo finalizado.");
            }
        }
    </script>

</body>

</html>
