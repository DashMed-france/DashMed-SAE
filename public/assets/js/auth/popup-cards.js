document.addEventListener('DOMContentLoaded', function() {

    const cardData = {
        'frequence-respiratoire-mesuree': {
            title: 'Fréquence respiratoire mesurée',
            value: '20',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 2 minutes' }
            ]
        },
        'frequence-respiratoire-spontanee': {
            title: 'Fréquence respiratoire spontanée',
            value: '14',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 2 minutes' }
            ]
        },
        'frequence-respiratoire-regle-sur-le-ventilateur': {
            title: 'Fréquence respiratoire réglée sur le ventilateur',
            value: '14',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 5 minutes' }
            ]
        },
        'frequence-respiratoire-mesuree-capnographie': {
            title: 'Fréquence respiratoire mesurée sur la capnographie',
            value: '16',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 1 minute' }
            ]
        },
        'fraction-inspire-oxygene-reglee-ventilateur': {
            title: 'Fraction inspirée en oxygène réglée sur le ventilateur',
            value: '28',
            details: [
                { label: 'Dernière réglage', value: 'Il y a 10 minutes' }
            ]
        },
        'fraction-inspire-oxygene-mesuree': {
            title: 'Fraction inspirée en oxygène mesurée',
            value: '56',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 30 secondes' }
            ]
        },
        'fraction-expiree-co2-mesuree': {
            title: 'Fraction expirée de CO2 mesurée',
            value: '38ml/kg',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 1 minute' }
            ]
        },
        'volume-courant-regle-ventilateur': {
            title: 'Volume courant réglé sur le ventilateur',
            value: '32ml/kg',
            details: [
                { label: 'Dernière réglage', value: 'Il y a 5 minutes' }
            ]
        },
        'volume-courant-mesuree': {
            title: 'Volume courant mesurée',
            value: '42ml/kg',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 2 minutes' }
            ]
        },
        'volume-minute-mesuree-1': {
            title: 'Volume minute mesurée',
            value: '38ml/kg',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 1 minute' }
            ]
        },
        'volume-minute-mesuree-2': {
            title: 'Volume minute mesurée',
            value: '36ml/kg', // Note: Doublon dans le HTML, j'ai différencié la clé.
            details: [
                { label: 'Dernière mesure', value: 'Il y a 30 secondes' }
            ]
        },
        'volume-minute-spontane-mesuree': {
            title: 'Volume minute spontané mesurée',
            value: '123',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 2 minutes' }
            ]
        },
        'pression-expiratoire-positive-reglee': {
            title: 'Pression expiratoire positive réglée',
            value: '78',
            details: [
                { label: 'Dernière réglage', value: 'Il y a 15 minutes' }
            ]
        },
        'pression-expiratoire-positive-mesuree': {
            title: 'Pression expiratoire positive mesurée',
            value: '38',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 1 minute' }
            ]
        },
        'temps-inspiratoire-regle': {
            title: 'Temps inspiratoire réglé',
            value: '14',
            details: [
                { label: 'Dernière réglage', value: 'Il y a 5 minutes' }
            ]
        },
        'temps-inspiratoire-mesuree': {
            title: 'Temps inspiratoire mesurée',
            value: '46',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 2 minutes' }
            ]
        },
        'temps-expiration-mesuree': {
            title: 'Temps expiration mesurée',
            value: '55',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 2 minutes' }
            ]
        },
        'temps-inspi-temps-expiratoire-regle-1': {
            title: 'Temps Inspi/Temps expiratoire réglé',
            value: '?',
            details: [
                { label: 'Statut', value: 'Valeur non renseignée dans le HTML' }
            ]
        },
        'temps-inspi-temps-expiratoire-regle-2': {
            title: 'Temps Inspi/Temps expiratoire réglé', // Note: Doublon dans le HTML, j'ai différencié la clé.
            value: '?',
            details: [
                { label: 'Statut', value: 'Valeur non renseignée dans le HTML' }
            ]
        },
        'pression-des-voies-aerienne-moyenne-mesuree': {
            title: 'Pression des voies aérienne moyenne mesurée',
            value: '148',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 1 minute' }
            ]
        },
        'pression-des-voies-aerienne-maximales': {
            title: 'Pression des voies aériennes maximales',
            value: '89',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 1 minute' }
            ]
        },
        'pression-de-plateau': {
            title: 'Pression de plateau',
            value: '26',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 2 minutes' }
            ]
        },
        'aide-inspiratoire-reglee': {
            title: 'Aide inspiratoire réglée',
            value: '?',
            details: [
                { label: 'Statut', value: 'Valeur non renseignée dans le HTML' }
            ]
        },
        'mode-ventilatoire-regle': {
            title: 'Mode ventilatoire réglé',
            value: '?',
            details: [
                { label: 'Statut', value: 'Valeur non renseignée dans le HTML' }
            ]
        },
        'saturation-pulsee-en-o2': {
            title: 'Saturation pulsée en O2',
            value: '88',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 30 secondes' }
            ]
        },
        'volume-courant-expire-mesure': {
            title: 'Volume courant expiré mesuré',
            value: '?',
            details: [
                { label: 'Statut', value: 'Valeur non renseignée dans le HTML' }
            ]
        }


    };

    const popupHTML = `
<div id="card-popup" class="popup-overlay">
        <div class="popup-content">
            <button class="popup-close" onclick="closeCardPopup()">&times;</button>
            <div class="popup-header">
                <h2 id="popup-title"></h2>
                <div class="popup-value" id="popup-value"></div>
            </div>
            <div class="popup-details">
                <h3>Détails</h3>
                <div id="popup-details-content"></div>
            </div>
            <div class="popup-navigation">
                <button class="nav-btn prev-btn">
                    <img src="assets/img/icons/fleche-G.svg" alt="Précédent">
                </button>
                <button class="nav-btn next-btn">
                    <img src="assets/img/icons/fleche-D.svg" alt="Suivant">
                </button>
            </div>
        </div>
    </div>
    `;

    document.body.insertAdjacentHTML('beforeend', popupHTML);

    // Ajouter les événements click sur toutes les cartes
    const cards = document.querySelectorAll('.card, .card2');
    cards.forEach((card, index) => {
        card.addEventListener('click', function(e) {
            // Empêcher l'ouverture du popup si on clique sur un bouton
            if (e.target.closest('button')) {
                return;
            }

            // Déterminer quelle carte a été cliquée
            const cardKeys = Object.keys(cardData);
            const cardKey = cardKeys[index] || cardKeys[0];

            openCardPopup(cardData[cardKey]);
        });
    });

    // Fermer le popup en cliquant en dehors
    document.getElementById('card-popup').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCardPopup();
        }
    });

    // Fermer avec la touche Échap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCardPopup();
        }
    });
});

function openCardPopup(data) {
    const popup = document.getElementById('card-popup');
    const title = document.getElementById('popup-title');
    const value = document.getElementById('popup-value');
    const detailsContent = document.getElementById('popup-details-content');

    title.textContent = data.title;
    value.textContent = data.value;

    let detailsHTML = '';
    data.details.forEach(detail => {
        detailsHTML += `
            <div class="detail-row">
                <span class="detail-label">${detail.label}</span>
                <span class="detail-value">${detail.value}</span>
            </div>
        `;
    });
    detailsContent.innerHTML = detailsHTML;

    popup.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCardPopup() {
    const popup = document.getElementById('card-popup');
    popup.classList.remove('active');
    document.body.style.overflow = '';
}