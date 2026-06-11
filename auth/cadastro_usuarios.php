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

    if ($_SERVER["REQUEST_METHOD"] == "POST" && !$conn->connect_error) {

        $senha = $_POST["senha"];
        $confirmar = $_POST["confirmar_senha"];
        $nome = $_POST["nome"] ?? "";
        $email = trim($_POST["email"] ?? "");
        $telefone = $_POST["telefone"] ?? "";
        $endereco = $_POST["endereco"] ?? "";
        $datanascimento = $_POST["datanascimento"] ?? "";

        $senhaForte = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/";
        $senhaNormalizada = normalizarTexto($senha);
        $partesNome = [];
        foreach (preg_split('/\s+/', normalizarTexto($nome)) as $parte) {
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

        $dataAtual = new DateTime();
        $dataNascObj = DateTime::createFromFormat('Y-m-d', $datanascimento);
        $idade = $dataNascObj ? $dataNascObj->diff($dataAtual)->y : -1;

        if (!isset($_POST["termos"])) {
            $erro = "Aceite os termos de uso.";
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]{8,}$/u', $nome)) {
            $erro = "O nome deve conter apenas letras e ter no mínimo 8 caracteres.";
        } elseif (!preg_match('/^[a-zA-Z0-9._]+@[a-zA-Z]+(\.[a-zA-Z]+)+$/', $email)) {
            $erro = "O formato do e-mail é inválido.";
        } elseif (!$dataNascObj || $idade < 18 || $idade > 120 || $dataNascObj > $dataAtual) {
            $erro = "A idade deve ser entre 18 e 120 anos.";
        } elseif ($senha !== $confirmar) {
            $erro = "As senhas não coincidem!";
        } elseif (!preg_match($senhaForte, $senha)) {
            $erro = "Senha fraca!";
        } elseif (preg_match($regexSequenciaNumerica, $senha)) {
            $erro = "A senha não pode conter sequência de números.";
        } elseif ($contemNome) {
            $erro = "A senha não pode conter o seu nome.";
        } elseif (!validar_recaptcha($_POST['g-recaptcha-response'] ?? '')) { 
            $erro = "Por favor, confirme que você não é um robô.";
        } else {
            $idExistente = null;
            $jaVerificado = 0;
            $stmtBusca = $conn->prepare("SELECT id, email_verificado FROM usuarios WHERE email = ? LIMIT 1");
            if ($stmtBusca) {
                $stmtBusca->bind_param("s", $email);
                $stmtBusca->execute();
                $resBusca = $stmtBusca->get_result();
                if ($resBusca && $resBusca->num_rows > 0) {
                    $existente = $resBusca->fetch_assoc();
                    $idExistente = (int) $existente["id"];
                    $jaVerificado = (int) ($existente["email_verificado"] ?? 0);
                }
                $stmtBusca->close();
            }

            if ($idExistente && $jaVerificado === 1) {
                echo "<script>window.location.href='login.html?status=email_ja_cadastrado';</script>";
                exit;
            }

            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $tokenConfirmacao = bin2hex(random_bytes(32));
            $expiraConfirmacao = date("Y-m-d H:i:s", strtotime("+24 hours"));
            $idCadastro = $idExistente;
            $cadastroNovo = !$idExistente;

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $linkConfirmacao = $protocol . "://" . $host . $path . "/confirmar_cadastro_email.php?token=" . $tokenConfirmacao;

            if ($idExistente && $jaVerificado === 0) {
                // Reenvio: atualiza cadastro pendente.
                $stmtPend = $conn->prepare("
                    UPDATE usuarios
                    SET nome = ?,
                        telefone = ?,
                        endereco = ?,
                        data_nascimento = ?,
                        senha = ?,
                        confirmacao_token = ?,
                        confirmacao_expira = ?
                    WHERE id = ?
                ");
                if ($stmtPend) {
                    $stmtPend->bind_param(
                        "sssssssi",
                        $nome,
                        $telefone,
                        $endereco,
                        $datanascimento,
                        $senhaHash,
                        $tokenConfirmacao,
                        $expiraConfirmacao,
                        $idExistente
                    );
                    if (!$stmtPend->execute()) {
                        $erro = "Erro ao atualizar cadastro pendente! " . $stmtPend->error;
                    }
                    $stmtPend->close();
                } else {
                    $erro = "Erro ao preparar atualização do cadastro! " . $conn->error;
                }
            } else {
                // Novo cadastro.
                $stmtCria = $conn->prepare("
                    INSERT INTO usuarios
                        (nome, email, telefone, endereco, data_nascimento, senha, email_verificado, confirmacao_token, confirmacao_expira)
                    VALUES
                        (?, ?, ?, ?, ?, ?, 0, ?, ?)
                ");
                if ($stmtCria) {
                    $stmtCria->bind_param(
                        "ssssssss",
                        $nome,
                        $email,
                        $telefone,
                        $endereco,
                        $datanascimento,
                        $senhaHash,
                        $tokenConfirmacao,
                        $expiraConfirmacao
                    );
                    if (!$stmtCria->execute()) {
                        $erro = "Erro ao cadastrar! " . $stmtCria->error;
                    } else {
                        $idCadastro = (int) $conn->insert_id;
                    }
                    $stmtCria->close();
                } else {
                    $erro = "Erro ao preparar cadastro! " . $conn->error;
                }
            }

            if ($erro === "") {
                app_log_event(
                    $cadastroNovo ? 'Criação de conta' : 'Atualização de cadastro pendente',
                    $cadastroNovo
                        ? 'Usuário iniciou cadastro e validação de e-mail.'
                        : 'Usuário atualizou um cadastro pendente de validação.',
                    $idCadastro,
                    $nome,
                    $email
                );

                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    configure_mailer($mail);
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = "Valide seu e-mail - Bazar Online";
                    $mail->Body = "
                    <html>
                    <head><meta charset='UTF-8'></head>
                    <body>
                        <p>Seu cadastro foi criado. Falta apenas validar seu e-mail.</p>
                        <p>
                            <a href='$linkConfirmacao'>
                                Validar e-mail
                            </a>
                        </p>
                        <p>Este link expira em 24 horas.</p>
                    </body>
                    </html>
                ";
                    $mail->AltBody = "Para validar seu e-mail, acesse: {$linkConfirmacao}. Este link expira em 24 horas.";
                    $mail->send();

                    echo "<script>window.location.href='login.html?status=confirmacao_cadastro_enviada';</script>";
                    exit;
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                    $erro = "Não foi possível enviar o e-mail de validação.";
                }
            }
        }
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

        document.getElementById('formCadastro').addEventListener('submit', function (e) {
            if (!this.checkValidity()) {
                e.preventDefault();
            }
            this.classList.add('was-validated');
        });

        window.addEventListener("load", () => {
            validarCaptchaVisibilidade();
            senha.dispatchEvent(new Event("input"));
        });
    </script>

</body>

</html>
