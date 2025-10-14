(function () {
    const btn = document.querySelector('.toggle-password');
    const input = document.getElementById('password');
    if (btn && input) {
        btn.addEventListener('click', () => {
            const isPwd = input.type === 'password';
            input.type = isPwd ? 'text' : 'password';
            const img = btn.querySelector('img');
            if (img) {
                img.src = isPwd ? 'assets/img/icons/eye-closed.svg' : 'assets/img/icons/eye-open.svg';
                img.alt = isPwd ? 'hide' : 'eye';
            }
        });
    }
})();