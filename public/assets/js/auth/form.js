document.querySelectorAll('.password-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-target');
        const input = document.getElementById(id);
        if (!input) return;
        const isPwd = input.type === 'password';
        input.type = isPwd ? 'text' : 'password';
        const img = btn.querySelector('img');
        if (img) {
            img.src = isPwd ? 'assets/img/icons/eye-closed.svg' : 'assets/img/icons/eye-open.svg';
            img.alt = isPwd ? 'Masquer' : 'Afficher';
        }
    });
});