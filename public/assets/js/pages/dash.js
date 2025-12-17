const aside = document.getElementById('aside')
const showAsideBtn = document.getElementById('aside-show-btn')
function toggleAside() {
    aside.classList.toggle('active-aside');
}

document.addEventListener("DOMContentLoaded", function() {
    const sortBtn = document.getElementById("sort-btn");
    const sortMenu = document.getElementById("sort-menu");
    const sortBtn2 = document.getElementById("sort-btn2");
    const sortMenu2 = document.getElementById("sort-menu2");
    const consultationList = document.getElementById("consultation-list");

    // pour mettre par ordre croissant des le debut
    if (consultationList) {
        const items = Array.from(consultationList.querySelectorAll(".consultation-link"));
        items.sort((a, b) => {
            const dateA = new Date(a.dataset.date);
            const dateB = new Date(b.dataset.date);
            return dateA - dateB;
        });
        items.forEach(item => consultationList.appendChild(item));
    }

    if (sortBtn) {
        sortBtn.addEventListener("click", () => {
            sortMenu.style.display = sortMenu.style.display === "none" ? "block" : "none";
        });
    }

    // le trucs pour boutons trier
    document.querySelectorAll(".sort-option").forEach(btn => {
        btn.addEventListener("click", () => {
            const order = btn.getAttribute("data-order");
            const items = Array.from(consultationList.querySelectorAll(".consultation-link"));

            items.sort((a, b) => {
                const dateA = new Date(a.dataset.date);
                const dateB = new Date(b.dataset.date);
                return order === "asc" ? dateA - dateB : dateB - dateA;
            });

            items.forEach(item => consultationList.appendChild(item));
            sortMenu.style.display = "none";
        });
    });

    if (sortBtn2) {
        sortBtn2.addEventListener("click", () => {
            sortMenu2.style.display = sortMenu2.style.display === "none" ? "block" : "none";
        });
    }

    // le truc pour boutons options
    document.querySelectorAll(".sort-option2").forEach(btn => {
        btn.addEventListener("click", () => {
            const filterText = btn.textContent.trim().toLowerCase();
            const items = Array.from(consultationList.querySelectorAll(".consultation-link"));
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            items.forEach(item => {
                const itemDate = new Date(item.dataset.date);
                itemDate.setHours(0, 0, 0, 0);

                if (filterText.includes("tout")) {
                    item.style.display = "block";
                } else if (filterText.includes("venir")) {
                    item.style.display = itemDate >= today ? "block" : "none";
                } else if (filterText.includes("passé")) {
                    item.style.display = itemDate < today ? "block" : "none";
                }
            });
            sortMenu2.style.display = "none";
        });
    });
});







// passer du mode clair au mode sombre
const toggleBtn = document.getElementById('toggleDark');
const modeLabel = document.getElementById('modeLabel');
const themeLink = document.getElementById('theme-style');

// Vérifie le thème sauvegardé
const savedTheme = localStorage.getItem('theme') || 'light';
setTheme(savedTheme);

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