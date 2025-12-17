const aside = document.getElementById('aside');
const showAsideBtn = document.getElementById('aside-show-btn');

function toggleAside() {
    if (aside) {
        aside.classList.toggle('active-aside');
    }
}

// Dark Mode Logic
const toggleBtn = document.getElementById('toggleDark');
const modeLabel = document.getElementById('modeLabel');
const themeLink = document.getElementById('theme-style');

const savedTheme = localStorage.getItem('theme') || 'light';
if (typeof setTheme === 'function') {
    setTheme(savedTheme);
} else {
    // Definition here if not global (it shouldn't be global usually in modules but valid here for simple script)
    function setTheme(theme) {
        if (!themeLink) return;

        if (theme === 'dark') {
            themeLink.href = 'assets/css/themes/dark.css';
            if (modeLabel) modeLabel.textContent = 'Mode clair';
        } else {
            themeLink.href = 'assets/css/themes/light.css';
            if (modeLabel) modeLabel.textContent = 'Mode sombre';
        }
        localStorage.setItem('theme', theme);
    }
    setTheme(savedTheme);
}


if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        if (themeLink) {
            const newTheme = (themeLink.getAttribute('href').includes('light')) ? 'dark' : 'light';
            setTheme(newTheme);
        }
    });
}