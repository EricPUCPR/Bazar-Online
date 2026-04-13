
const password = document.getElementById("password");
const confirmar = document.getElementById("confirmar_senha");

const reqLength = document.getElementById("req-length");
const reqSeq = document.getElementById("req-seq");
const reqCase = document.getElementById("req-case");
const reqEspecial = document.getElementById("req-especial");

const erroRegex = document.getElementById("erro-regex");
const erroMatch = document.getElementById("erro-match");

const form = document.getElementById("formSenha");

function togglePassword(id, icon) {
    const input = document.getElementById(id);

    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}

password.addEventListener("input", () => {
    const value = password.value;
    
    // 1. Validações
    const isLongEnough = value.length >= 8;
    const hasSeq = value.includes("123");
    const hasCase = /[a-z]/.test(value) && /[A-Z]/.test(value);
    const hasEspecial = /[^A-Za-z0-9]/.test(value);

    // 2. Atualiza as cores dos requisitos (aquela lista que fica embaixo)
    toggleClass(reqLength, isLongEnough);
    toggleClass(reqSeq, !hasSeq);
    toggleClass(reqCase, hasCase);
    toggleClass(reqEspecial, hasEspecial);

    // 3. Lógica do SPAN de erro
    // Se o campo estiver vazio, não mostra erro nenhum
    if (value.length === 0) {
        erroRegex.style.display = "none";
        return; 
    }

    // Só mostra o span se: 
    // Tiver a sequência "123" OU (se já terminou de digitar algo e ainda falta tamanho ou letras)
    if (hasSeq || (value.length > 0 && (!isLongEnough || !hasCase))) {
        erroRegex.style.display = "block";
        
        // Ajuste de texto para você saber o que está disparando o erro
        if (hasSeq) {
            erroRegex.textContent = "A sequência '123' não é permitida.";
        } else {
            erroRegex.textContent = "Senha muito curta ou sem letras maiúsculas/minúsculas.";
        }
    } else {
        // Se tudo estiver certo, esconde
        erroRegex.style.display = "none";
    }


});

confirmar.addEventListener("input", () => {
    const value = password.value;

    const isLongEnough = value.length >= 8;
    const hasSeq = value.includes("123");
    const hasCase = /[a-z]/.test(value) && /[A-Z]/.test(value);
    const hasEspecial = /[^A-Za-z0-9]/.test(value);


    toggleClass(reqLength, isLongEnough);
    toggleClass(reqSeq, !hasSeq);
    toggleClass(reqCase, hasCase);
    toggleClass(reqEspecial, hasEspecial);

    if (hasSeq || !isLongEnough || !hasCase) {
        erroRegex.style.display = "block";
        if (hasSeq){
            erroRegex.innerText = "A senha não pode conter sequências como '123'";
        }
    } else {
        erroRegex.innerText= "none";
    }
    
    
    
    
    erroMatch.style.display =
        password.value === confirmar.value ? "none" : "block";
});

form.addEventListener("submit", (e) => {
    const value = password.value;

    const valido =
        value.length >= 8 &&
        !value.includes("123") &&
        /[a-z]/.test(value) &&
        /[A-Z]/.test(value) &&
        /[^A-Za-z0-9]/.test(value) &&
        value === confirmar.value;

    if (!valido) {
        e.preventDefault();
        alert("Corrija os erros antes de enviar!");
    }
});

function toggleClass(element, valid) {
    element.classList.remove("valid", "invalid");
    element.classList.add(valid ? "valid" : "invalid");
}
