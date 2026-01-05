(function () {
    // — Dropdown profil —
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

    // — Dark Mode Toggle Logic —
    const btnToggle = document.getElementById('toggleDark');
    const label = document.getElementById('modeLabel');
    const root = document.documentElement;

    if (btnToggle) {
        // Function to update UI elements based on current theme
        const updateThemeUI = (theme) => {
            // Update Label
            if (label) {
                label.textContent = theme === 'dark' ? 'Mode sombre' : 'Mode clair';
            }
            // Switch styles are handled by CSS based on [data-theme]
        };

        // Initialize UI on load
        const currentTheme = localStorage.getItem('theme') || 'light';
        root.setAttribute('data-theme', currentTheme); // Ensure root has the initial theme
        updateThemeUI(currentTheme);

        // Click Handler
        btnToggle.addEventListener('click', (e) => {
            e.stopPropagation(); // Keep menu open
            e.preventDefault();

            const current = root.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';

            // Apply theme
            root.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);

            // Update UI
            updateThemeUI(next);

            // Animation effect
            const switchEl = btnToggle.querySelector('.switch');
            if (switchEl) {
                switchEl.style.transform = 'scale(0.95)';
                setTimeout(() => switchEl.style.transform = '', 150);
            }
        });
    }
})();