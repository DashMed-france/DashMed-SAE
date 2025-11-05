var modal = document.querySelector(".modal");
var closeButton = document.querySelector(".close-button");

function openModal(param, value, isCritical) {
    const modalTitle = document.getElementById('modalTitle');
    const modalValue = document.getElementById('modalValue');
    const modalDetails = document.getElementById('modalDetails');

    modalTitle.textContent = param;
    modalValue.textContent = value;
    modalDetails.innerHTML = isCritical ? '<p class="tag tag--danger">Valeur critique</p>' : '';

    modal.classList.add("show-modal");
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
