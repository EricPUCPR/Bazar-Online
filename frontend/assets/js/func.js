//Transforma todo telefone que passa pelo regex do input no formato (XX) XXXXX-XXXX
export function normalizarTelefone(telefoneSujo) {
    // Remove tudo o que nao for numero (espacos, letras, hifens soltos)
    const numeros = telefoneSujo.replace(/\D/g, '');

    // Um celular brasileiro valido com DDD precisa ter exatamente 11 digitos
    if (numeros.length !== 11) {
        return false;
    }

    // Fatiamos a string numerica e montamos o padrao blindado
    const ddd = numeros.slice(0, 2);
    const parte1 = numeros.slice(2, 7);
    const parte2 = numeros.slice(7, 11);

    const telefoneFormatado = `(${ddd}) ${parte1}-${parte2}`;

    // Regex extra do formato desejado
    const regexEstrita = /^\(\d{2}\) \d{5}-\d{4}$/;
    
    if (regexEstrita.test(telefoneFormatado)) {
        return telefoneFormatado; // Sucesso! Retorna o padrao perfeito
    }

    return false; // Falha de seguranca
}
// testes
console.log(normalizarTelefone("(12) 34567-8901")); // Retorna "(12) 34567-8901"

// Muda emoji que esconde e mostra senha
export function emojiSenha(btn, campo) {
    if (campo.type === "password") {
        campo.type = "text";
        btn.textContent = "🙊​​"; //nao conto sua senha
    } else {
        campo.type = "password";
        btn.textContent = "🙈​​"; //nao vejo sua senha
    }
}

// Seta conteudo do feedback que o usuario recebe
export function setFeedback(elementoFeedback, message, status = "muted") {
    elementoFeedback.className = `feedback feedback-${status}`;
    elementoFeedback.textContent = message;
}

// transforma string em [a-z0-9]
function normalizarTexto(texto) {
    texto = String(texto || "").trim().toLowerCase();
    texto = texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    return texto.replace(/[^a-z0-9 ]/g, "");
}

//Faz a validação da senha de acordo com os requisitos
export function validaSenha(senha, nome, confirmarSenha) {
// quebra o nome em partes
    const partesNome = normalizarTexto(nome)
        .split(/\s+/)
        .filter((parte) => parte.length >= 3);

    //verifica se encontra partes do nome na senha
    const contemNome = partesNome.some((parte) => normalizarTexto(senha).replace(/\s/g, "").includes(parte));

    // valida todos os regex
    const checks = [
        senha.length >= 12, //valida 12 caracteres
        /[A-Z]/.test(senha), //valida letra maiuscula
        /[a-z]/.test(senha), //valida letra minuscula
        /\d/.test(senha), //valida numeros
        /[@$!%*?&]/.test(senha), //valida caracteres especiais
        !/(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/.test(senha), //valida sequencia numerica
        !contemNome //valida se contem parte do nome
    ];

    return checks;
}


//Verifica requisitos de senha
export function validaSenhaCadastro(form, captchaArea){
    const senha = form.senha.value;
    const nome = form.nome.value;
    const confirmarSenha = form.confirmar_senha.value;

    console.log(normalizarTexto("Olá, Mundo! 123")); // Retorna "ola mundo 123"

    // valida todos os regex
    const checks = validaSenha(senha, form.nome.value, confirmarSenha);

    if (
        form.checkValidity() 
        && senha === confirmarSenha // se as senhas forem iguais
        && senha !== "" // se a senha nao for nula
        && checks.every(c => c) // se todos os checks forem validados verdadeiros
        && form.termos.checked // se os termos de uso forem aceitos
    ) {
        captchaArea.style.display = "flex"; // exibe o captcha
    } else {
        captchaArea.style.display = "none"; // oculta o captcha
    }

    return checks;
}

//normaliza o telefone digitado no input para o formato (XX) XXXXX-XXXX enquanto usuário digita
export function aplicarMascaraTelefone(telefone) {
    let valor = telefone.value.replace(/\D/g, "").slice(0, 11);
    if (valor.length > 2) {
        valor = `(${valor.slice(0, 2)}) ${valor.slice(2)}`;
    }
    if (valor.length > 10) {
        valor = `${valor.slice(0, 10)}-${valor.slice(10)}`;
    }
    telefone.value = valor;
}