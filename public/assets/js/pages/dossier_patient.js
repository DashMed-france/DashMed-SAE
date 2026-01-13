document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('patientEditModal');
    const btnOpenEdit = document.querySelector('.btn-edit-patient');
    const btnCloseList = document.querySelectorAll('.btn-close, .btn-secondary');

    window.openEditModal = function () {
        if (editModal) {
            editModal.classList.add('active');
            editModal.setAttribute('aria-hidden', 'false');
        }
    };

    window.closeEditModal = function () {
        if (editModal) {
            editModal.classList.remove('active');
            editModal.setAttribute('aria-hidden', 'true');
        }
    };

    if (editModal) {
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) {
                window.closeEditModal();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (editModal && editModal.classList.contains('active')) {
                window.closeEditModal();
            }
        }
    });
});
