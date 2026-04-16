document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    const emailInput = document.getElementById('email');

    form.addEventListener('submit', (event) => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(emailInput.value)) {
            event.preventDefault(); 
            alert('Por favor, insira um e-mail válido.');
            emailInput.focus();
        }
    });
});