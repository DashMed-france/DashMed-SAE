(() => {
    const digits = Array.from(document.querySelectorAll('.code-digit'));
    const hidden = document.getElementById('code');

    if (digits.length && hidden) {
        digits.forEach((inp, idx) => {
            inp.addEventListener('input', () => {
                inp.value = inp.value.replace(/[^0-9]/g, '').slice(0, 1);

                if (inp.value && idx < digits.length - 1) {
                    digits[idx + 1].focus();
                }

                hidden.value = digits.map(d => d.value || '').join('');
            });

            inp.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !inp.value && idx > 0) {
                    digits[idx - 1].focus();
                }
            });
        });
    }
})();