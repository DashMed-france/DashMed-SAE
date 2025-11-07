const aside = document.getElementById('aside')
const showAsideBtn = document.getElementById('aside-show-btn')
function toggleAside() {
    aside.classList.toggle('active-aside');
}


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