const aside = document.getElementById('aside');
const showAsideBtn = document.getElementById('aside-show-btn');


function toggleAside() {
    if (aside) {
        aside.classList.toggle('active-aside');
    }
}

const restoreBtn = document.getElementById('aside-restore-btn');
const body = document.body;

function toggleDesktopAside() {
    const isCollapsed = body.classList.toggle('aside-collapsed');
    localStorage.setItem('asideCollapsed', isCollapsed);
}

const savedAsideState = localStorage.getItem('asideCollapsed');
if (savedAsideState === 'true') {
    body.classList.add('aside-collapsed');
}


function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
}

const savedTheme = localStorage.getItem('theme') || 'light';
applyTheme(savedTheme);


window.addEventListener('storage', (e) => {
    if (e.key === 'theme') {
        applyTheme(e.newValue);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const filterBtns = document.querySelectorAll('.category-filter-btn');
    if (filterBtns.length > 0) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => {
                    b.classList.remove('active');
                    if (b.classList.contains('category-filter-btn--custom')) {
                        const origColor = b.dataset.groupColor || b.style.color || '';
                        b.style.backgroundColor = '';
                        b.style.color = origColor;
                    }
                });
                btn.classList.add('active');

                if (btn.classList.contains('category-filter-btn--custom')) {
                    const groupColor = btn.style.color || btn.dataset.groupColor || '#3b82f6';
                    if (!btn.dataset.groupColor) btn.dataset.groupColor = groupColor;
                    btn.style.backgroundColor = groupColor;
                    btn.style.color = '#ffffff';
                }

                const filterValue = btn.getAttribute('data-filter');
                const cards = document.querySelectorAll('.card');

                let groupLayout = {};
                let groupIds = [];
                if (filterValue === 'custom_group') {
                    const indicatorsRaw = btn.getAttribute('data-group-indicators') || '';
                    groupIds = indicatorsRaw.split(',').map(s => s.trim()).filter(Boolean);
                    try {
                        groupLayout = JSON.parse(btn.getAttribute('data-group-layout') || '{}');
                    } catch (e) { /* ignore parse errors */ }
                }

                cards.forEach(card => {
                    if (!card.hasAttribute('data-orig-grid-col')) {
                        card.setAttribute('data-orig-grid-col', card.style.gridColumn || 'auto');
                        card.setAttribute('data-orig-grid-row', card.style.gridRow || 'auto');
                    }

                    const origCol = card.getAttribute('data-orig-grid-col');
                    const origRow = card.getAttribute('data-orig-grid-row');

                    const getAutoSpan = (styleStr) => {
                        if (!styleStr || styleStr === 'auto') return 'auto';
                        const parts = styleStr.split('/');
                        if (parts.length > 1) return 'auto / ' + parts[1].trim();
                        return 'auto';
                    };

                    if (filterValue === 'all') {
                        card.style.display = '';
                        card.style.gridColumn = origCol;
                        card.style.gridRow = origRow;
                    } else if (filterValue === 'custom_group') {
                        const cardParamId = card.getAttribute('data-parameter-id') || '';

                        if (groupIds.includes(cardParamId)) {
                            card.style.display = '';
                            const itemLayout = groupLayout[cardParamId];
                            if (itemLayout && itemLayout.x !== null && itemLayout.y !== null) {
                                card.style.gridColumn = (itemLayout.x + 1) + ' / span ' + (itemLayout.w || 4);
                                card.style.gridRow = (itemLayout.y + 1) + ' / span ' + (itemLayout.h || 3);
                            } else {
                                card.style.gridColumn = getAutoSpan(origCol);
                                card.style.gridRow = getAutoSpan(origRow);
                            }
                        } else {
                            card.style.display = 'none';
                        }
                    } else {
                        const cardCategory = card.getAttribute('data-category');
                        if (cardCategory === filterValue) {
                            card.style.display = '';
                            card.style.gridColumn = getAutoSpan(origCol);
                            card.style.gridRow = getAutoSpan(origRow);
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });
    }
});