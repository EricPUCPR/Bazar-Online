
const senhaInput = document.getElementById('password');
const confirmarInput = document.getElementById('confirmar_senha');
const erroRegex = document.getElementById('erro-regex');
const formulario = document.querySelector('form');

senhaInput.addEventListener('input', () => {
    const regexSem123 = /^(?!.*123).*$/;

    if (!regexSem123.test(senhaInput.value)) {
        erroRegex.style.display = 'block';
        senhaInput.style.borderColor = 'red';
    } else {
        erroRegex.style.display = 'none';
        senhaInput.style.borderColor = '';
    }
});


formulario.addEventListener('submit', (event) => {
    if (senhaInput.value !== confirmarInput.value) {
        event.preventDefault(); 
        alert("As senhas não sao iguais!");
    } else if (!/^(?!.*123).*$/.test(senhaInput.value)) {
        event.preventDefault();
        alert("A senha contém sequências proibidas (123).");
    }
});
