var modal = document.querySelector(".modal");
var closeButton = document.querySelector(".close-button");

function openModal(param, value, isCritical) {
    const modalTitle   = modal.querySelector('#modalTitle');
    const modalValue   = modal.querySelector('#modalValue');
    const modalDetails = modal.querySelector('#modalDetails');

    if (modalTitle) modalTitle.textContent = param;
    if (modalValue) modalValue.textContent = value;

    modalDetails.innerHTML = isCritical ? '<p class="tag tag--danger">Valeur critique</p>' : '';

    modal.classList.add('show-modal');
}

function toggleModal() {
    modal.classList.toggle("show-modal");
}

function windowOnClick(event) {
    if (event.target === modal) {
        toggleModal();
    }
}

closeButton.addEventListener("click", toggleModal);
window.addEventListener("click", windowOnClick);

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