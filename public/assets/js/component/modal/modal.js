var modal = document.querySelector(".modal");
var closeButton = document.querySelector(".close-button");

/**
 * Opens the modal and populates its title and value.
 * Appends a critical tag if the value is deemed critical.
 *
 * @param {string} param - The name of the medical parameter (e.g., 'FC', 'SpO2').
 * @param {string|number} value - The actual recorded value.
 * @param {boolean} isCritical - Whether the value exceeds critical thresholds.
 */
function openModal(param, value, isCritical) {
    const modalTitle = modal.querySelector('#modalTitle');
    const modalValue = modal.querySelector('#modalValue');
    const modalDetails = modal.querySelector('#modalDetails');

    if (modalTitle) modalTitle.textContent = param;
    if (modalValue) modalValue.textContent = value;

    if (modalDetails) {
        // Prevent memory leaks
        const existingCanvases = modalDetails.querySelectorAll('canvas');
        existingCanvases.forEach(canvas => {
            if (canvas.chartInstance) {
                canvas.chartInstance.destroy();
                canvas.chartInstance = null;
            }
        });
        modalDetails.innerHTML = isCritical ? '<p class="tag tag--danger">Valeur critique</p>' : '';
    }

    modal.classList.add('show-modal');
    document.body.classList.add("modal-open");
}

/**
 * Toggles the visibility of the modal and manages the body scrolling state.
 */
function toggleModal() {
    modal.classList.toggle("show-modal");
    if (modal.classList.contains("show-modal")) {
        document.body.classList.add("modal-open");
    } else {
        document.body.classList.remove("modal-open");
    }
}

/**
 * Closes the modal when clicking outside its content area.
 *
 * @param {MouseEvent} event - The window click event.
 */
function windowOnClick(event) {
    if (event.target === modal) {
        toggleModal();
    }
}

closeButton.addEventListener("click", toggleModal);
window.addEventListener("click", windowOnClick);

/**
 * Formats an ISO time string into a more readable localized French format.
 * Includes relative descriptors like "Aujourd'hui" and "Hier".
 *
 * @param {string} timeStr - The ISO 8601 time string to format.
 * @returns {string} The localized time string.
 */
function formatTime(timeStr) {
    if (!timeStr || timeStr === '—') return '—';

    const date = new Date(timeStr);
    if (isNaN(date)) return timeStr;

    const now = new Date();

    const sameDay =
        date.getDate() === now.getDate() &&
        date.getMonth() === now.getMonth() &&
        date.getFullYear() === now.getFullYear();

    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    const isYesterday =
        date.getDate() === yesterday.getDate() &&
        date.getMonth() === yesterday.getMonth() &&
        date.getFullYear() === yesterday.getFullYear();

    if (sameDay) {
        return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    } else if (isYesterday) {
        return 'Hier ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    } else {
        return date.toLocaleDateString('fr-FR', {
            weekday: 'short',
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

window.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && modal.classList.contains("show-modal")) {
        toggleModal();
    }
});