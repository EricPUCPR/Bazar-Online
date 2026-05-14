
const password = document.getElementById("password");
const confirmar = document.getElementById("confirmar_senha");

const reqLength = document.getElementById("req-length");
const reqUpper = document.getElementById("req-upper");
const reqLower = document.getElementById("req-lower");
const reqNumber = document.getElementById("req-number");
const reqSpecial = document.getElementById("req-special");
const reqNoSeq = document.getElementById("req-no-seq");
const reqNoName = document.getElementById("req-no-name");

const erroRegex = document.getElementById("erro-regex");
const erroMatch = document.getElementById("erro-match");

const form = document.getElementById("formSenha");
const nomeReferencia = document.getElementById("nome_referencia");

if (!password || !confirmar || !reqLength || !reqUpper || !reqLower || !reqNumber || !reqSpecial || !reqNoSeq || !reqNoName || !erroRegex || !erroMatch || !form) {
} else {

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
    
    const isLongEnough = value.length >= 8;
    const hasUpper = /[A-Z]/.test(value);
    const hasLower = /[a-z]/.test(value);
    const hasNumber = /\d/.test(value);
    const hasSpecial = /[@$!%*?&]/.test(value);
    const hasNumberSequence = /(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/.test(value);
    const senhaNormalizada = value
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-z0-9]/g, "");
    const partesNome = (nomeReferencia?.value || "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .split(/\s+/)
        .map((parte) => parte.replace(/[^a-z0-9]/g, ""))
        .filter((parte) => parte.length >= 3);
    const containsName = partesNome.some((parte) => senhaNormalizada.includes(parte));

    toggleClass(reqLength, isLongEnough);
    toggleClass(reqUpper, hasUpper);
    toggleClass(reqLower, hasLower);
    toggleClass(reqNumber, hasNumber);
    toggleClass(reqSpecial, hasSpecial);
    toggleClass(reqNoSeq, !hasNumberSequence);
    toggleClass(reqNoName, !containsName);

    if (value.length === 0) {
        erroRegex.classList.remove("is-visible");
        return; 
    }

    if (!isLongEnough || !hasUpper || !hasLower || !hasNumber || !hasSpecial || hasNumberSequence || containsName) {
        erroRegex.classList.add("is-visible");
        erroRegex.textContent = "Senha fraca!";
    } else {
        erroRegex.classList.remove("is-visible");
    }


});

confirmar.addEventListener("input", () => {
    const value = password.value;

    const isLongEnough = value.length >= 8;
    const hasUpper = /[A-Z]/.test(value);
    const hasLower = /[a-z]/.test(value);
    const hasNumber = /\d/.test(value);
    const hasSpecial = /[@$!%*?&]/.test(value);
    const hasNumberSequence = /(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/.test(value);
    const senhaNormalizada = value
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-z0-9]/g, "");
    const partesNome = (nomeReferencia?.value || "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .split(/\s+/)
        .map((parte) => parte.replace(/[^a-z0-9]/g, ""))
        .filter((parte) => parte.length >= 3);
    const containsName = partesNome.some((parte) => senhaNormalizada.includes(parte));


    toggleClass(reqLength, isLongEnough);
    toggleClass(reqUpper, hasUpper);
    toggleClass(reqLower, hasLower);
    toggleClass(reqNumber, hasNumber);
    toggleClass(reqSpecial, hasSpecial);
    toggleClass(reqNoSeq, !hasNumberSequence);
    toggleClass(reqNoName, !containsName);

    if (!isLongEnough || !hasUpper || !hasLower || !hasNumber || !hasSpecial || hasNumberSequence || containsName) {
        erroRegex.classList.add("is-visible");
        erroRegex.textContent = "Senha fraca!";
    } else {
        erroRegex.classList.remove("is-visible");
    }
    
    
    
    
    erroMatch.classList.toggle("is-visible", password.value !== confirmar.value);
});

form.addEventListener("submit", (e) => {
    const value = password.value;

    const valido =
        value.length >= 8 &&
        /[A-Z]/.test(value) &&
        /[a-z]/.test(value) &&
        /\d/.test(value) &&
        /[@$!%*?&]/.test(value) &&
        !/(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/.test(value) &&
        !(nomeReferencia?.value || "")
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .split(/\s+/)
            .map((parte) => parte.replace(/[^a-z0-9]/g, ""))
            .filter((parte) => parte.length >= 3)
            .some((parte) =>
                value
                    .toLowerCase()
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "")
                    .replace(/[^a-z0-9]/g, "")
                    .includes(parte)
            ) &&
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
}
