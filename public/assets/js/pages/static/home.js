function updateClock(){
    const el = document.getElementById('clock');
    if (!el) return;
    const d = new Date();
    const s = String(d.getSeconds()).padStart(2,'0');
    const h = String(d.getHours()).padStart(2,'0');
    const m = String(d.getMinutes()).padStart(2,'0');
    el.textContent = `${h}:${m}:${s}`;
}
updateClock();
setInterval(updateClock, 1000);

const links = document.getElementById('links')
function toggleDropdownMenu() {
    links.classList.toggle('active')
}

document.getElementById('year').textContent = new Date().getFullYear();