document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('add-consultation-modal');
    const openBtn = document.getElementById('btn-add-consultation');
    const closeBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-modal-btn');
    const form = document.getElementById('add-consultation-form');

    // Function to open modal
    function openModal() {
        modal.classList.remove('hidden');
        // Set default date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('consultation-date').value = today;

        // Set default time to current time
        const now = new Date();
        const time = now.toTimeString().split(' ')[0].substring(0, 5);
        document.getElementById('consultation-time').value = time;
    }

    // Function to close modal
    function closeModal() {
        modal.classList.add('hidden');
        // Optional: Reset form on close?
        // form.reset(); 
    }

    // Event Listeners
    if (openBtn) {
        openBtn.addEventListener('click', openModal);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    // Close on click outside
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Handle Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
});
