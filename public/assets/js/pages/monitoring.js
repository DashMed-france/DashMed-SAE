document.addEventListener('DOMContentLoaded', () => {
    const unsavedBar = document.getElementById('unsaved-bar');
    const saveBtn = document.getElementById('save-changes-btn');

    if (!unsavedBar || !saveBtn) return;

    window.showUnsavedChanges = () => {
        unsavedBar.style.display = 'flex';
    };

    window.hideUnsavedChanges = () => {
        unsavedBar.style.display = 'none';
    };

    saveBtn.addEventListener('click', () => {
        const modalForm = document.querySelector('.modal.show-modal form');
        if (modalForm) {
            modalForm.submit();
            return;
        }

        console.log("Sauvegarde globale non implémentée");
        hideUnsavedChanges();
    });

    document.addEventListener('change', (e) => {
        if (e.target.matches('.modal-select')) {
            showUnsavedChanges();
        }
    });
});
