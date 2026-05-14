<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Cadastro</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body class="auth-page">

    <div class="app-header">
        <a href="Login.html" class="header-link btn-light">Login</a>
        <a href="index.html" class="header-link btn-primary">Voltar</a>
    </div>

    <?php
    require 'vendor/mailer/PHPMailer/src/Exception.php';
    require 'vendor/mailer/PHPMailer/src/PHPMailer.php';
    require 'vendor/mailer/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/config/session.php';
    require_once __DIR__ . '/config/app.php';

    // ── Modo API: processamento AJAX ──────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_ajax'])) {
        header('Content-Type: application/json; charset=utf-8');

        function normalizarTexto($texto)
        {
            $texto = trim((string) $texto);
            $texto = function_exists('mb_strtolower') ? mb_strtolower($texto, 'UTF-8') : strtolower($texto);
            if (function_exists('iconv')) {
                $c = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
                if ($c !== false) $texto = $c;
            }
            return preg_replace('/[^a-z0-9 ]/', '', $texto);
        }

        $regexSeq   = "/(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/";
        $senhaForte = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/";

        $senha          = $_POST['senha']          ?? '';
        $confirmar      = $_POST['confirmar_senha'] ?? '';
        $nome           = $_POST['nome']           ?? '';
        $email          = trim($_POST['email']     ?? '');
        $telefone       = $_POST['telefone']       ?? '';
        $endereco       = $_POST['endereco']       ?? '';
        $datanascimento = $_POST['datanascimento'] ?? '';

        $senhaNorm   = normalizarTexto($senha);
        $partesNome  = array_filter(
            array_map(fn($p) => preg_replace('/[^a-z0-9]/', '', $p),
                preg_split('/\s+/', normalizarTexto($nome))),
            fn($p) => strlen($p) >= 3
        );
        $contemNome = array_reduce($partesNome, fn($carry, $p) => $carry || str_contains($senhaNorm, $p), false);

        $dataAtual   = new DateTime();
        $dataNascObj = DateTime::createFromFormat('Y-m-d', $datanascimento);
        $idade       = $dataNascObj ? $dataNascObj->diff($dataAtual)->y : -1;


        // ── Validações ────────────────────────────────────────────────────────
        $err = null;
        if (!isset($_POST['termos']))                                      $err = 'Aceite os termos de uso.';
        elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]{8,}$/u', $nome))            $err = 'O nome deve conter apenas letras e ter no mínimo 8 caracteres.';
        elseif (!preg_match('/^[a-zA-Z0-9._]+@[a-zA-Z]+(\.[a-zA-Z]+)+$/', $email)) $err = 'O formato do e-mail é inválido.';
        elseif (!$dataNascObj || $idade < 18 || $idade > 120)             $err = 'A idade deve ser entre 18 e 120 anos.';
        elseif ($senha !== $confirmar)                                     $err = 'As senhas não coincidem!';
        elseif (!preg_match($senhaForte, $senha))                         $err = 'Senha fraca!';
        elseif (preg_match($regexSeq, $senha))                            $err = 'A senha não pode conter sequência de números.';
        elseif ($contemNome)                                               $err = 'A senha não pode conter o seu nome.';
        elseif (!validar_recaptcha($_POST['g-recaptcha-response'] ?? ''))  $err = 'Por favor, confirme que você não é um robô.';

        if ($err) { echo json_encode(['success' => false, 'mensagem' => $err]); exit; }


        // ── Verifica se e-mail já existe como conta ATIVA no banco ────────────
        $conn = db_connect('DB_NAME_USUARIOS');
        if ($conn->connect_error) {
            echo json_encode(['success' => false, 'mensagem' => 'Falha ao verificar o e-mail. Tente novamente.']);
            exit;

        }
        $stmtChk = $conn->prepare("CALL proc_usuario_email_existe(?)");
        if ($stmtChk) {
            $stmtChk->bind_param("s", $email);
            $stmtChk->execute();
            $resChk = $stmtChk->get_result();
            $jaExiste = $resChk && $resChk->num_rows > 0;
            $stmtChk->close();
            $conn->next_result();
        } else {
            $jaExiste = false;
        }
        $conn->close();

        if ($jaExiste) {
            echo json_encode(['success' => false, 'mensagem' => 'Este e-mail já possui uma conta ativa.']);
            exit;
        }

        // ── Gera código e guarda TUDO na sessão (banco intocado por enquanto) ─
        $codigo  = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expira  = time() + 900; // 15 minutos

        $_SESSION['cadastro_pendente'] = [
            'nome'           => $nome,
            'email'          => $email,
            'telefone'       => $telefone,
            'endereco'       => $endereco,
            'datanascimento' => $datanascimento,
            'senha_hash'     => password_hash($senha, PASSWORD_DEFAULT),
            'codigo'         => $codigo,
            'expira'         => $expira,
        ];

        // ── Envia e-mail com o código ─────────────────────────────────────────
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            configure_mailer($mail);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Seu código de verificação - Bazar Online";
            $mail->Body = "
                <html><head><meta charset='UTF-8'></head>
                <body style='font-family:Arial,sans-serif;'>
                    <h2 style='color:#1f6f9f;'>Bazar Online</h2>
                    <p>Olá, <strong>" . htmlspecialchars($nome) . "</strong>!</p>
                    <p>Seu código de verificação é:</p>
                    <p style='font-size:40px;font-weight:bold;letter-spacing:12px;color:#0d4f73;'>$codigo</p>
                    <p style='color:#666;font-size:13px;'>Este código expira em <strong>15 minutos</strong>.</p>
                    <p style='color:#999;font-size:12px;'>Se você não criou uma conta no Bazar Online, ignore este e-mail.</p>
                </body></html>
            ";
            $mail->AltBody = "Seu código de verificação é: $codigo (expira em 15 minutos)";
            $mail->send();
            echo json_encode(['success' => true, 'mensagem' => 'Código enviado! Verifique sua caixa de entrada.']);
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Erro ao enviar e-mail de verificação: " . $e->getMessage());
            unset($_SESSION['cadastro_pendente']); // limpa sessão se e-mail falhou
            echo json_encode(['success' => false, 'mensagem' => 'Não foi possível enviar o código. Tente novamente.']);
        }
        exit;
    }
    ?>

    <div class="page-main">
        <div class="box">

            <h2>Cadastro</h2>

            <!-- ── PASSO 1: Formulário ── -->
            <div id="passo-form">
                <form id="formCadastro">
                    <input type="hidden" name="_ajax" value="1">

                    <input type="text" id="nome" name="nome" placeholder="Nome completo" pattern="[a-zA-ZÀ-ÿ\s]{8,}"
                        title="O nome deve conter apenas letras e ter no mínimo 8 caracteres." required>
                    <input type="email" name="email" id="email" placeholder="E-mail"
                        pattern="[a-zA-Z0-9._]+@[a-zA-Z]+(\.[a-zA-Z]+)+" title="Digite um e-mail válido." required>
                    <input type="date" name="datanascimento" id="datanascimento"
                        min="<?= date('Y-m-d', strtotime('-120 years')) ?>"
                        max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required>
                    <input type="tel" id="telefone" name="telefone" placeholder="Telefone"
                        inputmode="numeric" maxlength="15" required>
                    <input type="text" name="endereco" id="endereco" placeholder="Endereço" required>

                    <div class="password-wrapper">
                        <input type="password" id="senha" name="senha" placeholder="Senha" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('senha', this)" aria-label="Visualizar senha">👁</button>
                    </div>
                    <div class="password-wrapper">
                        <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Confirmar senha" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirmar_senha', this)" aria-label="Visualizar confirmar senha">👁</button>
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
                            <input type="checkbox" id="termos" name="termos" required>
                            Eu aceito os <a href="legal/terms/index.html" target="_blank">termos de uso</a>
                        </label>
                    </div>

                    <div id="captcha-container" class="g-recaptcha"
                        style="margin-top:20px;display:none;justify-content:center;"
                        data-sitekey="<?= env_value('RECAPTCHA_SITE_KEY') ?>"></div>

                    <span id="feedback" class="feedback"></span>

                    <button type="submit" id="btnCadastrar" class="btn-block" style="margin-top:15px;">Cadastrar</button>
                </form>
                <a href="Login.html" class="form-link">Já tenho cadastro</a>
            </div>

            <!-- ── PASSO 2: Verificação de código ── -->
            <div id="passo-codigo" style="display:none;">
                <p style="margin-bottom:16px;color:#666;font-size:14px;line-height:1.5;">
                    Enviamos um código de <strong>6 dígitos</strong> para o seu e-mail.<br>
                    Digite-o abaixo para criar sua conta.
                </p>

                <input type="text" id="codigo-input" inputmode="numeric" maxlength="6"
                    pattern="[0-9]{6}" autocomplete="one-time-code"
                    placeholder="_ _ _ _ _ _"
                    style="text-align:center;font-size:24px;letter-spacing:8px;">

                <span id="feedback-codigo" class="feedback"></span>

                <button id="btnVerificar" class="btn-block" style="margin-top:15px;">Verificar código</button>

                <button id="btnReenviar" class="btn-block"
                    style="margin-top:8px;background:#f5f7fa;color:#1f6f9f;border:1px solid #d5dee8;">
                    Reenviar código
                </button>

                <a href="CadastroUsuarios.php" class="form-link small-link" style="margin-top:16px;">Voltar ao cadastro</a>
            </div>

        </div>
    </div>

    <script>
    const $ = id => document.getElementById(id);

    function setFeedback(id, msg, tipo = 'muted') {
        const el = $(id);
        el.className = `feedback feedback-${tipo}`;
        el.textContent = msg;
    }

    function togglePassword(id, botao) {
        const campo = $(id);
        if (!campo) return;
        campo.type = campo.type === 'password' ? 'text' : 'password';
        botao.textContent = campo.type === 'password' ? '👁' : '🙈';
    }

    const senha = $('senha'), requisitos = $('requisitos');
    const confirmarSenha = $('confirmar_senha'), telefone = $('telefone');
    const nome = $('nome'), termos = $('termos'), form = $('formCadastro');
    const captchaContainer = $('captcha-container');

    function validarCaptchaVisibilidade() {
        const val = senha.value;
        const senhaNorm = val.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]/g,'');
        const partesNome = (nome.value||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'')
            .split(/\s+/).map(p=>p.replace(/[^a-z0-9]/g,'')).filter(p=>p.length>=3);
        const temSeq    = /(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/.test(val);
        const contemNome = partesNome.some(p=>senhaNorm.includes(p));
        const checks = [val.length>=8,/[A-Z]/.test(val),/[a-z]/.test(val),/\d/.test(val),/[@$!%*?&]/.test(val),!temSeq,!contemNome];
        const tudo = checks.every(c=>c) && val===confirmarSenha.value && val!=='' && form.checkValidity() && termos.checked;
        captchaContainer.style.display = tudo ? 'flex' : 'none';
        return checks;
    }

    senha.addEventListener('input', () => {
        const checks = validarCaptchaVisibilidade();
        requisitos.classList.toggle('is-visible', senha.value.length > 0 && !checks.every(c=>c));
        ['r1','r2','r3','r4','r5','r6','r7'].forEach((id,i) => $(id).className = checks[i] ? 'ok' : 'erro');
    });
    nome.addEventListener('input', () => { senha.dispatchEvent(new Event('input')); validarCaptchaVisibilidade(); });
    confirmarSenha.addEventListener('input', validarCaptchaVisibilidade);
    termos.addEventListener('change', validarCaptchaVisibilidade);
    form.querySelectorAll('input').forEach(inp => inp.addEventListener('input', validarCaptchaVisibilidade));

    telefone.addEventListener('input', () => {
        let v = telefone.value.replace(/\D/g,'').slice(0,11);
        if (v.length>2) v=`(${v.slice(0,2)}) ${v.slice(2)}`;
        if (v.length>10) v=`${v.slice(0,10)}-${v.slice(10)}`;
        telefone.value = v;
    });

    window.addEventListener('load', () => { validarCaptchaVisibilidade(); senha.dispatchEvent(new Event('input')); });

    // ── Submit AJAX ───────────────────────────────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
        const captchaResp = typeof grecaptcha !== 'undefined' ? grecaptcha.getResponse() : '';
        if (captchaContainer.style.display==='flex' && !captchaResp) {
            setFeedback('feedback','Por favor, marque o captcha.','error'); return;
        }
        $('btnCadastrar').disabled = true;
        setFeedback('feedback','Enviando...','muted');
        const fd = new FormData(form);
        fd.set('g-recaptcha-response', captchaResp);
        try {
            const res  = await fetch('CadastroUsuarios.php', { method:'POST', body:fd });
            const data = await res.json();
            if (data.success) {
                $('passo-form').style.display   = 'none';
                $('passo-codigo').style.display = 'block';
                $('codigo-input').focus();
            } else {
                setFeedback('feedback', data.mensagem||'Erro ao cadastrar.', 'error');
                if (typeof grecaptcha!=='undefined') grecaptcha.reset();
                $('btnCadastrar').disabled = false;
            }
        } catch {
            setFeedback('feedback','Erro ao conectar com o servidor.','error');
            $('btnCadastrar').disabled = false;
        }
    });

    // ── Verificar código ──────────────────────────────────────────────────────
    $('btnVerificar').addEventListener('click', async () => {
        const codigo = $('codigo-input').value.trim();
        if (!/^\d{6}$/.test(codigo)) { setFeedback('feedback-codigo','Digite os 6 dígitos do código.','error'); return; }
        $('btnVerificar').disabled = true;
        setFeedback('feedback-codigo','Verificando...','muted');
        try {
            const fd = new FormData();
            fd.append('codigo', codigo);
            const res  = await fetch('verificar_cadastro.php', { method:'POST', body:fd });
            const data = await res.json();
            if (data.success) {
                setFeedback('feedback-codigo', data.mensagem, 'success');
                setTimeout(() => { window.location.href = 'index.html?status=email_confirmado'; }, 1500);
            } else {
                setFeedback('feedback-codigo', data.mensagem||'Código incorreto.', 'error');
                $('btnVerificar').disabled = false;
            }
        } catch {
            setFeedback('feedback-codigo','Erro ao conectar com o servidor.','error');
            $('btnVerificar').disabled = false;
        }
    });

    // ── Reenviar código ───────────────────────────────────────────────────────
    $('btnReenviar').addEventListener('click', async () => {
        $('btnReenviar').disabled = true;
        setFeedback('feedback-codigo','Reenviando...','muted');
        const fd = new FormData(form);
        fd.set('g-recaptcha-response','');
        try {
            const res  = await fetch('CadastroUsuarios.php', { method:'POST', body:fd });
            const data = await res.json();
            setFeedback('feedback-codigo', data.mensagem||(data.success?'Código reenviado!':'Erro.'), data.success?'success':'error');
        } catch { setFeedback('feedback-codigo','Erro ao conectar.','error'); }
        setTimeout(() => { $('btnReenviar').disabled = false; }, 30000);
    });
    </script>

</body>

