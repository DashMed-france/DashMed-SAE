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
                // Remove active class and reset styles for all buttons
                filterBtns.forEach(b => {
                    b.classList.remove('active');
                    if (b !== btn) {
                        b.style.background = 'var(--bg-card, #ffffff)';
                        b.style.color = 'var(--text-color, #1f2937)';
                        b.style.border = '1px solid var(--border-color, #e5e7eb)';
                        b.style.fontWeight = '500';
                    }
                });
                
                // Add active class and set styles for clicked button
                btn.classList.add('active');
                btn.style.background = 'var(--primary-color, #2563eb)';
                btn.style.color = 'white';
                btn.style.border = 'none';
                btn.style.fontWeight = '600';

                const filterValue = btn.getAttribute('data-filter');
                const cards = document.querySelectorAll('.card');

                cards.forEach(card => {
                    if (filterValue === 'all') {
                        card.style.display = '';
                    } else {
                        const cardCategory = card.getAttribute('data-category');
                        if (cardCategory === filterValue) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });
    }
});