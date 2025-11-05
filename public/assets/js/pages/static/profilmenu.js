(function () {
    // — Dropdown profil —
    const btnProfile  = document.getElementById('profileBtn');
    const menu        = document.getElementById('profileMenu');

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

    // — Toggle dark/clair avec animation lune ⇄ soleil —
    const btnToggle = document.getElementById('toggleDark');
    const label     = document.getElementById('modeLabel');
    const root      = document.documentElement;

    if (!btnToggle) return;

    // état initial depuis localStorage
    try {
        const saved = localStorage.getItem('theme');
        if (saved) root.setAttribute('data-theme', saved);
    } catch(_) {}

    const updateUI = () => {
        const isDark = root.getAttribute('data-theme') === 'dark';
        btnToggle.setAttribute('aria-pressed', String(isDark));
        if (label) label.textContent = isDark ? 'Mode clair' : 'Mode sombre';
    };
    updateUI();

    const setTheme = (name) => {
        root.setAttribute('data-theme', name);
        try { localStorage.setItem('theme', name); } catch(_) {}
        updateUI();
    };

    btnToggle.addEventListener('click', (e) => {
        e.preventDefault();
        const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        setTheme(next);
        // petit "pop"
        btnToggle.classList.add('anim');
        setTimeout(() => btnToggle.classList.remove('anim'), 300);
    });
})();