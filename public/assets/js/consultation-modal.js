document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const modal = document.getElementById('add-consultation-modal');
    const openBtn = document.getElementById('btn-add-consultation');
    const closeBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-modal-btn');
    const form = document.getElementById('add-consultation-form');
    const modalTitle = modal.querySelector('.modal-header h2');
    const formAction = document.getElementById('form-action');
    const consultationIdInput = document.getElementById('consultation-id');

    // Inputs
    const inputDoctor = document.getElementById('doctor-select');
    const inputTitle = document.getElementById('consultation-title');
    const inputDate = document.getElementById('consultation-date');
    const inputTime = document.getElementById('consultation-time');
    const inputType = document.getElementById('consultation-type');
    const inputNote = document.getElementById('consultation-note');

    // Open Modal (Add Mode)
    if (openBtn) {
        openBtn.addEventListener('click', () => {
            resetForm();
            setAddMode();
            openModal();
        });
    }

    // Event Delegation for Edit and Delete (handles dynamic DOM updates)
    document.body.addEventListener('click', (e) => {
        // Edit Button Click
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            e.stopPropagation();
            const data = editBtn.dataset;
            fillForm(data);
            setEditMode(data.id);
            openModal();
            return;
        }

        // Delete Button Click
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            e.stopPropagation();
            if (confirm('Êtes-vous sûr de vouloir supprimer cette consultation ? Cette action est irréversible.')) {
                const consultationId = deleteBtn.dataset.id;
                submitDeleteForm(consultationId);
            }
            return;
        }
    });

    // Close Modal
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    // Outside Click
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Escape Key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    function setAddMode() {
        if (modalTitle) modalTitle.textContent = 'Nouvelle Consultation';
        if (formAction) formAction.value = 'add_consultation';
        if (consultationIdInput) consultationIdInput.value = '';

        // Set default date/time for new entries
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        if (inputDate) inputDate.value = `${year}-${month}-${day}`;
        if (inputTime) inputTime.value = `${hours}:${minutes}`;
    }

    function setEditMode(id) {
        if (modalTitle) modalTitle.textContent = 'Modifier Consultation';
        if (formAction) formAction.value = 'update_consultation';
        if (consultationIdInput) consultationIdInput.value = id;
    }

    function resetForm() {
        if (form) form.reset();
    }

    function fillForm(data) {
        if (inputTitle) inputTitle.value = data.title;
        if (inputDate) inputDate.value = data.date;
        if (inputTime) inputTime.value = data.time;
        if (inputNote) inputNote.value = data.note;
        if (inputType) inputType.value = data.type;
        // Handling doctor selection (text match attempt if ID not available, though we prefer ID)
        // If the select has options with values = doctor ID, we should try to select that if available?
        // Currently data.doctor is Name. We didn't add doctorId to data-attributes yet.
        // Let's assume user must re-select doctor for now or we add data-doctor-id to view.
    }

    function submitDeleteForm(id) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=medicalprocedure';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_consultation';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id_consultation';
        idInput.value = id;

        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
});
