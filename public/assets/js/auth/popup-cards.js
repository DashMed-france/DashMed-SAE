document.addEventListener('DOMContentLoaded', function() {

    const cardData = {
        'frequence-cardiaque': {
            title: 'Fréquence cardiaque',
            value: '72 bpm',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 2 minutes' }
            ]
        },
        'tension-arterielle': {
            title: 'Tension artérielle',
            value: '120/80 mmHg',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 5 minutes' }
            ]
        },
        'pression-arterielle': {
            title: 'Pression artérielle Moyenne',
            value: '86 mmHg',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 5 minutes' }
            ]
        },
        'saturation-o2': {
            title: 'Saturation O₂',
            value: '98 %',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 1 minute' }
            ]
        },
        'frequence-respiratoire': {
            title: 'Fréquence respiratoire',
            value: '15 c/min',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 3 minutes' }
            ]
        },
        'temperature': {
            title: 'Température',
            value: '36,7 °C',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 10 minutes' }
            ]
        },
        'co2-expire': {
            title: 'CO₂ expiré',
            value: '37 mmHg',
            details: [
                { label: 'Tendance', value: 'Stable' },
                { label: 'Dernière mesure', value: 'Il y a 2 minutes' }
            ]
        },
        'pression-veineuse': {
            title: 'Pression veineuse centrale',
            value: '4 mmHg',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 5 minutes' }
            ]
        },
        'debit-cardiaque': {
            title: 'Débit cardiaque',
            value: '6 L/min',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 5 minutes' }
            ]
        },
        'resistances-vasculaires': {
            title: 'Résistances Vasculaires Systémiques',
            value: '900',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 5 minutes' }
            ]
        },
        'oxygenation-tissulaire': {
            title: 'Oxygénation Tissulaire Cérébrale',
            value: '30 mmHg',
            details: [
                { label: 'Dernière mesure', value: 'Il y a 2 minutes' }
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

    // Construire les détails
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
    document.body.style.overflow = 'hidden'; // Empêcher le scroll de la page
}

function closeCardPopup() {
    const popup = document.getElementById('card-popup');
    popup.classList.remove('active');
    document.body.style.overflow = ''; // Rétablir le scroll
}