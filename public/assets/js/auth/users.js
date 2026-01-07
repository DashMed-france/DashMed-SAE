document.addEventListener('DOMContentLoaded', function() {
    const userCards = document.querySelectorAll('.user-card');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const searchInput = document.getElementById('search');
    const form = document.querySelector('form');

    function normalizeString(str) {
        return str.normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function filterUsers() {
        const searchTerm = normalizeString(searchInput.value);
        let visibleCount = 0;

        userCards.forEach(card => {
            const userName = normalizeString(card.textContent);
            const email = normalizeString(card.getAttribute('data-email') || '');
            
            if (userName.includes(searchTerm) || email.includes(searchTerm)) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
                if (card.classList.contains('selected')) {
                    card.classList.remove('selected');
                    emailInput.value = '';
                }
            }
        });

        updateNoResultsMessage(visibleCount);
    }

    function updateNoResultsMessage(visibleCount) {
        let noResultsMsg = document.getElementById('no-results-message');
        
        if (visibleCount === 0) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('p');
                noResultsMsg.id = 'no-results-message';
                noResultsMsg.className = 'no-results';
                noResultsMsg.textContent = 'Aucun utilisateur trouvé';
                document.getElementById('user-list').appendChild(noResultsMsg);
            }
        } else {
            if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }
    }

    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(filterUsers, 200);
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                filterUsers();
            }
        });
    }

    userCards.forEach(card => {
        card.addEventListener('click', function() {
            if (this.style.display === 'none') {
                return;
            }

            const email = this.getAttribute('data-email');
            
            if (email) {
                emailInput.value = email;

                userCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');

                passwordInput.focus();
            }
        });

        card.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && this.style.display !== 'none') {
                this.click();
            }
        });

        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
        card.setAttribute('aria-label', 'Sélectionner ' + card.textContent.trim());
    });

    form.addEventListener('submit', function(e) {
        if (!emailInput.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un utilisateur avant de vous connecter.');
            return false;
        }
    });

    const emailVisible = document.getElementById('email-visible');
    if (emailVisible) {
        emailVisible.addEventListener('input', function() {
            emailInput.value = this.value;
            userCards.forEach(c => c.classList.remove('selected'));
        });
    }
});