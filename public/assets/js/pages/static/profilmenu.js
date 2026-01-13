(function () {
    const btnProfile = document.getElementById('profileBtn');
    const menu = document.getElementById('profileMenu');

    if (btnProfile && menu) {
        const closeMenu = () => {
            menu.classList.remove('open');
            btnProfile.setAttribute('aria-expanded', 'false');
            menu.setAttribute('aria-hidden', 'true');
        };
        const openMenu = () => {
            menu.classList.add('open');
            btnProfile.setAttribute('aria-expanded', 'true');
            menu.setAttribute('aria-hidden', 'false');
        };
        btnProfile.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            menu.classList.contains('open') ? closeMenu() : openMenu();
        });
        document.addEventListener('click', (e) => {
            if (!menu.classList.contains('open')) return;
            if (!menu.contains(e.target) && e.target !== btnProfile) closeMenu();
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });
    }

    const btnToggle = document.getElementById('toggleDark');
    const label = document.getElementById('modeLabel');
    const root = document.documentElement;

    if (btnToggle) {
        const updateThemeUI = (theme) => {
            if (label) {
                label.textContent = theme === 'dark' ? 'Mode sombre' : 'Mode clair';
            }
        };

        const currentTheme = localStorage.getItem('theme') || 'light';
        root.setAttribute('data-theme', currentTheme);
        updateThemeUI(currentTheme);

        btnToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();

            const current = root.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';

            root.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);

            updateThemeUI(next);

            const switchEl = btnToggle.querySelector('.switch');
            if (switchEl) {
                switchEl.style.transform = 'scale(0.95)';
                setTimeout(() => switchEl.style.transform = '', 150);
            }
        });
    }
})();