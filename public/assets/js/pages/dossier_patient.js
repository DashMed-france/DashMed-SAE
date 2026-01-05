/**
 * Gestion de la page Dossier Patient.
 * Gère l'ouverture et la fermeture des modales d'édition.
 */

document.addEventListener('DOMContentLoaded', () => {
    // === Modale d'Édition du Patient ===
    const editModal = document.getElementById('patientEditModal');
    const btnOpenEdit = document.querySelector('.btn-edit-patient'); // Assurez-vous que le bouton a cette classe ou utilisez onclick existant si nécessaire (mais mieux vaut event listener)
    const btnCloseList = document.querySelectorAll('.btn-close, .btn-secondary');

    // Fonction pour ouvrir la modale
    window.openEditModal = function () {
        if (editModal) {
            editModal.classList.add('active');
            editModal.setAttribute('aria-hidden', 'false');
        }
    };

    // Fonction pour fermer la modale
    window.closeEditModal = function () {
        if (editModal) {
            editModal.classList.remove('active');
            editModal.setAttribute('aria-hidden', 'true');
        }
    };

    // Fermeture au clic sur l'overlay (en dehors du contenu)
    if (editModal) {
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) {
                window.closeEditModal();
            }
        });
    }

    // Gestion de la touche Echap pour fermer
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (editModal && editModal.classList.contains('active')) {
                window.closeEditModal();
            }
        }
    });
});
