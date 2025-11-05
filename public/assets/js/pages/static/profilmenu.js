    (function () {
    const btn  = document.getElementById('profileBtn');
    const menu = document.getElementById('profileMenu');
    const toggleDarkBtn = document.getElementById('toggleDark');

    if (!btn || !menu) return;

    const closeMenu = () => {
    menu.classList.remove('open');
    btn.setAttribute('aria-expanded', 'false');
    menu.setAttribute('aria-hidden', 'true');
};

    const openMenu = () => {
    menu.classList.add('open');
    btn.setAttribute('aria-expanded', 'true');
    menu.setAttribute('aria-hidden', 'false');
};

    btn.addEventListener('click', (e) => {
    e.stopPropagation();
    menu.classList.contains('open') ? closeMenu() : openMenu();
});

    document.addEventListener('click', (e) => {
    if (!menu.classList.contains('open')) return;
    if (!menu.contains(e.target) && e.target !== btn) closeMenu();
});

    document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
});

    // Dark mode simple via <html data-theme="dark">, persistant
    const root = document.documentElement;
    const applyTheme = (name) => {
    root.setAttribute('data-theme', name);
    try { localStorage.setItem('theme', name); } catch (_) {}
};
    // au chargement
    const saved = (function(){ try { return localStorage.getItem('theme'); } catch(_) { return null; }})();
    if (saved) applyTheme(saved);

    if (toggleDarkBtn) {
    toggleDarkBtn.addEventListener('click', () => {
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    applyTheme(next);
});
}
})();

