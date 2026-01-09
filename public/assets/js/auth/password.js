(() => {
    // Handle 6-digit code input auto-focus and aggregation
    const digits = Array.from(document.querySelectorAll('.code-digit'));
    const hidden = document.getElementById('code');

    if (digits.length && hidden) {
        digits.forEach((inp, idx) => {
            inp.addEventListener('input', () => {
                // Ensure only numbers
                inp.value = inp.value.replace(/[^0-9]/g, '').slice(0, 1);

                // Auto-focus next
                if (inp.value && idx < digits.length - 1) {
                    digits[idx + 1].focus();
                }

                // Update hidden input
                hidden.value = digits.map(d => d.value || '').join('');
            });

            inp.addEventListener('keydown', (e) => {
                // Handle backspace to focus previous
                if (e.key === 'Backspace' && !inp.value && idx > 0) {
                    digits[idx - 1].focus();
                }
            });
        });
    }
})();