(() => {
    const inputs = document.querySelectorAll("#codeForm .code-container input");
    if (inputs.length) {
        inputs.forEach((input, index) => {
            input.addEventListener("input", e => {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                if (e.target.value && index < inputs.length - 1) inputs[index + 1].focus();
            });
            input.addEventListener("keydown", e => {
                if (e.key === "Backspace" && !input.value && index > 0) inputs[index - 1].focus();
            });
        });

        const form = document.getElementById("codeForm");
        if (form) {
            form.addEventListener("submit", e => {
                e.preventDefault();
                let code = Array.from(inputs).map(i => i.value).join('');
                if (code.length === inputs.length) {
                    window.location.href = "passwordView.php";
                }
            });
        }
    }

    const digits = Array.from(document.querySelectorAll('.code-digit'));
    const hidden = document.getElementById('code');
    if (digits.length && hidden) {
        digits.forEach((inp, idx) => {
            inp.addEventListener('input', () => {
                inp.value = inp.value.replace(/[^0-9]/g, '').slice(0,1);
                if (inp.value && idx < digits.length - 1) digits[idx+1].focus();
                hidden.value = digits.map(d => d.value || '').join('');
            });
            inp.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !inp.value && idx > 0) digits[idx-1].focus();
            });
        });
    }

    document.querySelectorAll('button.toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            if (!targetId) return;
            const input = document.getElementById(targetId);
            if (!input) return;

            const isPwd = input.type === 'password';
            input.type = isPwd ? 'text' : 'password';

            // update aria and image
            btn.setAttribute('aria-pressed', String(isPwd));
            const img = btn.querySelector('img');
            if (img) {
                // ajuste les chemins/noms d'icônes si besoin
                img.src = isPwd ? 'assets/img/icons/eye-closed.svg' : 'assets/img/icons/eye-open.svg';
                img.alt = isPwd ? 'Masquer le mot de passe' : 'Afficher le mot de passe';
            }
        });
    });
})();