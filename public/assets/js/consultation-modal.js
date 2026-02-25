document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('add-consultation-modal');
    const openBtn = document.getElementById('btn-add-consultation');
    const closeBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-modal-btn');
    const form = document.getElementById('add-consultation-form');
    const modalTitle = modal.querySelector('.modal-header h2');
    const formAction = document.getElementById('form-action');
    const consultationIdInput = document.getElementById('consultation-id');

    const inputDoctor = document.getElementById('doctor-select');
    const inputTitle = document.getElementById('consultation-title');
    const inputDate = document.getElementById('consultation-date');
    const inputTime = document.getElementById('consultation-time');
    const inputType = document.getElementById('consultation-type');
    const inputNote = document.getElementById('consultation-note');
    const existingDocsContainer = document.getElementById('modal-existing-documents');
    const modalDocSection = document.getElementById('modal-document-section');

    if (openBtn) {
        openBtn.addEventListener('click', () => {
            resetForm();
            setAddMode();
            openModal();
        });
    }

    document.body.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            e.preventDefault();
            e.stopPropagation();
            const data = editBtn.dataset;
            fillForm(data);
            setEditMode(data.id);
            openModal();
            return;
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            e.preventDefault();
            e.stopPropagation();
            if (confirm('Êtes-vous sûr de vouloir supprimer cette consultation ? Cette action est irréversible.')) {
                const consultationId = deleteBtn.dataset.id;
                submitDeleteForm(consultationId);
            }
            return;
        }
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    function setAddMode() {
        if (modalTitle) modalTitle.textContent = 'Nouvelle Consultation';
        if (formAction) formAction.value = 'add_consultation';
        if (consultationIdInput) consultationIdInput.value = '';

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
        if (existingDocsContainer) {
            existingDocsContainer.innerHTML = '';
            existingDocsContainer.style.display = 'none';
        }
    }

    function fillForm(data) {
        if (inputTitle) inputTitle.value = data.title;
        if (inputDate) inputDate.value = data.date;
        if (inputTime) inputTime.value = data.time;
        if (inputNote) inputNote.value = data.note;

        if (inputType && data.type) {
            let found = false;

            const decodeHtml = (html) => {
                const txt = document.createElement("textarea");
                txt.innerHTML = html;
                return txt.value;
            };

            const normalize = (str) => {
                return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
            };

            const rawVal = decodeHtml(data.type);
            const target = normalize(rawVal);

            inputType.value = rawVal;
            if (inputType.value === rawVal) {
                found = true;
            } else {
                for (let i = 0; i < inputType.options.length; i++) {
                    const opt = inputType.options[i];
                    if (normalize(opt.value) === target || normalize(opt.text) === target) {
                        inputType.value = opt.value;
                        found = true;
                        break;
                    }
                }
            }

            if (!found && rawVal) {
                const newOption = document.createElement('option');
                newOption.value = rawVal;
                newOption.text = rawVal;
                inputType.add(newOption);
                inputType.value = rawVal;
            } else if (!found) {
                inputType.value = 'Autre';
            }
        }

        if (inputDoctor && data.doctorId) {
            inputDoctor.value = data.doctorId;
        }

        // Handle existing documents
        if (data.documents) {
            try {
                const docs = JSON.parse(data.documents);
                displayExistingDocs(docs);
            } catch (e) {
                console.error("Error parsing documents data", e);
            }
        }
    }

    function displayExistingDocs(docs) {
        if (!existingDocsContainer) return;

        existingDocsContainer.innerHTML = '';

        if (docs && docs.length > 0) {
            existingDocsContainer.style.display = 'flex';

            docs.forEach(doc => {
                const docItem = document.createElement('div');
                docItem.className = 'doc-item';

                docItem.innerHTML = `
                    <a href="/?page=api_consultation_document&id=${doc.id}" target="_blank" class="doc-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        <span class="doc-filename">${doc.filename}</span>
                        <span class="doc-size">${doc.size}</span>
                    </a>
                `;

                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'doc-delete-btn';
                deleteBtn.title = "Supprimer ce document";
                deleteBtn.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                `;

                deleteBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (confirm(`Voulez-vous vraiment supprimer le document "${doc.filename}" ?`)) {
                        handleDocDelete(doc.id, docItem);
                    }
                });

                docItem.appendChild(deleteBtn);
                existingDocsContainer.appendChild(docItem);
            });
        } else {
            existingDocsContainer.style.display = 'none';
        }
    }

    // Handle AJAX deletion for both card and modal
    document.addEventListener('submit', (e) => {
        if (e.target.classList.contains('doc-delete-form')) {
            e.preventDefault();
            const form = e.target;
            const docId = form.querySelector('input[name="id_document"]').value;
            const docItem = form.closest('.doc-item');

            if (confirm('Supprimer ce document ?')) {
                handleDocDelete(docId, docItem);
            }
        }
    });

    async function handleDocDelete(docId, element) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_document_pdf');
            formData.append('id_document', docId);

            const response = await fetch('?page=medicalprocedure', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            // Even if the server redirects, we want to handle the UI removal
            if (response.ok) {
                element.style.opacity = '0';
                element.style.transform = 'scale(0.9)';
                element.style.transition = 'all 0.3s ease';

                setTimeout(() => {
                    element.remove();
                    // If this was in the modal, check if empty
                    if (existingDocsContainer && existingDocsContainer.children.length === 0) {
                        existingDocsContainer.style.display = 'none';
                    }
                }, 300);

                if (typeof iziToast !== 'undefined') {
                    iziToast.success({
                        message: 'Document supprimé avec succès',
                        position: 'topRight',
                        timeout: 2000
                    });
                }
            } else {
                throw new Error('Deletion failed');
            }
        } catch (error) {
            console.error('Error deleting document:', error);
            if (typeof iziToast !== 'undefined') {
                iziToast.error({
                    message: 'Erreur lors de la suppression du document',
                    position: 'topRight'
                });
            }
        }
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
