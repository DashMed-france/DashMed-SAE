const aside = document.getElementById('aside')
const showAsideBtn = document.getElementById('aside-show-btn')
function toggleAside() {
    aside.classList.toggle('active-aside');
}

document.addEventListener("DOMContentLoaded", function() {
    const sortBtn = document.getElementById("sort-btn");
    const sortMenu = document.getElementById("sort-menu");
    const consultationList = document.getElementById("consultation-list");

    // Toggle menu
    sortBtn.addEventListener("click", () => {
        sortMenu.style.display = sortMenu.style.display === "none" ? "block" : "none";
    });

// passer du mode clair au mode sombre
const toggleBtn = document.getElementById('toggleDark');
const modeLabel = document.getElementById('modeLabel');
const themeLink = document.getElementById('theme-style');

// Vérifie le thème sauvegardé
const savedTheme = localStorage.getItem('theme') || 'light';
setTheme(savedTheme);
    // Tri
    document.querySelectorAll(".sort-option").forEach(btn => {
        btn.addEventListener("click", () => {
            const order = btn.getAttribute("data-order");
            const items = Array.from(consultationList.querySelectorAll(".consultation-link"));

            items.sort((a, b) => {
                const dateA = new Date(a.dataset.date);
                const dateB = new Date(b.dataset.date);
                return order === "asc" ? dateA - dateB : dateB - dateA;
            });

            // Réinjecte les items triés
            items.forEach(item => consultationList.appendChild(item));
            sortMenu.style.display = "none"; // ferme le menu
        });
    });
});

// Lorsqu’on clique sur le bouton
toggleBtn.addEventListener('click', () => {
    const newTheme = (themeLink.getAttribute('href').includes('light')) ? 'dark' : 'light';
    setTheme(newTheme);
});

// Fonction pour changer le thème
function setTheme(theme) {
    if (theme === 'dark') {
        themeLink.href = '/assets/css/themes/dark.css';
        modeLabel.textContent = 'Mode clair';
    } else {
        themeLink.href = '/assets/css/themes/light.css';
        modeLabel.textContent = 'Mode sombre';
    }
    localStorage.setItem('theme', theme);
}