
/**
 * Gestion de la sélection d'utilisateur via les user-cards avec recherche
 */
document.addEventListener('DOMContentLoaded', function() {
    const userCards = document.querySelectorAll('.user-card');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const searchInput = document.getElementById('search');
    const form = document.querySelector('form');

    /**
     * Normalise une chaîne pour la recherche (retire accents, met en minuscules)
     */
    function normalizeString(str) {
        return str.normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    /**
     * Filtre les cartes utilisateur selon la recherche
     */
    function filterUsers() {
        const searchTerm = normalizeString(searchInput.value);
        let visibleCount = 0;

        userCards.forEach(card => {
            const userName = normalizeString(card.textContent);
            const email = normalizeString(card.getAttribute('data-email') || '');
            
            // Recherche dans le nom ou l'email
            if (userName.includes(searchTerm) || email.includes(searchTerm)) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
                // Désélectionne la carte si elle est cachée
                if (card.classList.contains('selected')) {
                    card.classList.remove('selected');
                    emailInput.value = '';
                }
            }
        });

        // Affiche un message si aucun résultat
        updateNoResultsMessage(visibleCount);
    }

    /**
     * Affiche/cache un message "Aucun résultat"
     */
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

    // Événement de recherche avec debounce pour optimiser les performances
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(filterUsers, 200);
        });

        // Recherche instantanée sur Enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                filterUsers();
            }
        });
    }

    // Gestion du clic sur les cartes utilisateur
    userCards.forEach(card => {
        card.addEventListener('click', function() {
            // Ne permet pas de sélectionner une carte cachée
            if (this.style.display === 'none') {
                return;
            }

            // Récupère l'email depuis l'attribut data-email
            const email = this.getAttribute('data-email');
            
            if (email) {
                // Remplit le champ email caché
                emailInput.value = email;
                
                // Marque visuellement la carte comme sélectionnée
                userCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                
                // Focus sur le champ mot de passe
                passwordInput.focus();
            }
        });

        // Permet la soumission directe en appuyant sur Entrée
        card.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && this.style.display !== 'none') {
                this.click();
            }
        });

        // Rend les cartes accessibles au clavier
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
        card.setAttribute('aria-label', 'Sélectionner ' + card.textContent.trim());
    });

    // Validation avant soumission
    form.addEventListener('submit', function(e) {
        if (!emailInput.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un utilisateur avant de vous connecter.');
            return false;
        }
    });

    // Synchronisation du champ email visible (si présent)
    const emailVisible = document.getElementById('email-visible');
    if (emailVisible) {
        emailVisible.addEventListener('input', function() {
            emailInput.value = this.value;
            // Désélectionne les cartes
            userCards.forEach(c => c.classList.remove('selected'));
        });
    }
});