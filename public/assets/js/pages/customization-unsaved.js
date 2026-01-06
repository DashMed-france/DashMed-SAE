document.addEventListener('DOMContentLoaded', () => {
    const unsavedBar = document.getElementById('unsaved-bar');
    const saveBtn = document.getElementById('save-changes-btn');
    const form = document.querySelector('form[action*="customization"]');

    if (!unsavedBar || !saveBtn || !form) return;

    form.addEventListener('change', () => {
        unsavedBar.style.display = 'flex';
    });

    saveBtn.addEventListener('click', (e) => {
        e.preventDefault();
        form.submit();
    });
});
