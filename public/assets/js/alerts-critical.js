/**
 * DashMed - Module de notifications d'alertes médicales avec iziToast
 * 
 * Affiche des notifications toast lors de dépassements de seuils de paramètres médicaux.
 * Utilise iziToast.js pour l'affichage (https://izitoast.marcelodolza.com/)
 */

'use strict';

const DashMedAlerts = (function () {
    // Configuration par défaut des toasts
    const defaultConfig = {
        position: 'topRight',
        timeout: 6000,
        progressBar: true,
        close: true,
        transitionIn: 'flipInX',
        transitionOut: 'flipOutX',
        pauseOnHover: true,
        resetOnHover: true,
        displayMode: 'replace',
        layout: 2,
        maxWidth: 400,
    };

    // Icônes personnalisées selon le type
    const iconMap = {
        error: 'ico-error',
        warning: 'ico-warning',
        info: 'ico-info',
        success: 'ico-success',
    };

    /**
     * Affiche une notification toast pour une alerte
     * @param {Object} alert - Objet alerte avec type, title, message, etc.
     */
    function showAlertToast(alert) {
        if (!alert || !alert.type) {
            console.warn('[DashMedAlerts] Alerte invalide:', alert);
            return;
        }

        const toastOptions = {
            ...defaultConfig,
            title: alert.title || 'Alerte',
            message: alert.message || '',
            icon: alert.icon || iconMap[alert.type] || 'ico-info',
            buttons: buildButtons(alert),
        };

        // Appel de la méthode iziToast appropriée selon le type
        switch (alert.type) {
            case 'error':
                iziToast.error(toastOptions);
                break;
            case 'warning':
                iziToast.warning(toastOptions);
                break;
            case 'info':
                iziToast.info(toastOptions);
                break;
            case 'success':
                iziToast.success(toastOptions);
                break;
            default:
                iziToast.show(toastOptions);
        }
    }

    /**
     * Construit les boutons d'action pour le toast
     * @param {Object} alert - Données de l'alerte
     * @returns {Array} Configuration des boutons iziToast
     */
    function buildButtons(alert) {
        const buttons = [];

        // Bouton "Voir le graphique" si parameterId est disponible
        if (alert.parameterId) {
            buttons.push([
                '<button type="button" class="toast-btn-graph"><i class="ico-chart"></i> Graphique</button>',
                function (instance, toast) {
                    navigateToParameter(alert.parameterId);
                    instance.hide({ transitionOut: 'fadeOut' }, toast);
                },
                true // Focus sur ce bouton
            ]);
        }

        // Bouton "Fermer"
        buttons.push([
            '<button type="button" class="toast-btn-close">Fermer</button>',
            function (instance, toast) {
                instance.hide({ transitionOut: 'fadeOut' }, toast);
            },
            false
        ]);

        return buttons;
    }

    /**
     * Navigue vers la page patient avec focus sur un paramètre spécifique
     * @param {string} parameterId - ID du paramètre médical
     */
    function navigateToParameter(parameterId) {
        // Récupère l'ID patient depuis l'URL ou les données de la page
        const urlParams = new URLSearchParams(window.location.search);
        const room = urlParams.get('room') || '1';
        
        // Scroll vers la carte du paramètre si elle existe sur la page
        const paramCard = document.querySelector(`[data-parameter-id="${parameterId}"]`);
        if (paramCard) {
            paramCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            paramCard.classList.add('highlight-alert');
            setTimeout(() => paramCard.classList.remove('highlight-alert'), 3000);
        } else {
            // Sinon, redirige vers la page monitoring avec le paramètre
            window.location.href = `/?page=monitoring&room=${room}&param=${encodeURIComponent(parameterId)}`;
        }
    }

    /**
     * Affiche toutes les alertes d'un tableau
     * @param {Array} alerts - Tableau d'objets alertes
     * @param {number} delayBetween - Délai en ms entre chaque toast (default 500)
     */
    function showAllAlerts(alerts, delayBetween = 500) {
        if (!Array.isArray(alerts) || alerts.length === 0) {
            return;
        }

        // Affiche les alertes avec un délai pour éviter la surcharge visuelle
        alerts.forEach((alert, index) => {
            setTimeout(() => {
                showAlertToast(alert);
            }, index * delayBetween);
        });
    }

    /**
     * Initialise les alertes au chargement de la page
     * Lit les alertes depuis une variable globale window.dashmedAlerts
     */
    function init() {
        // Vérifie que iziToast est chargé
        if (typeof iziToast === 'undefined') {
            console.error('[DashMedAlerts] iziToast non chargé. Veuillez inclure la librairie.');
            return;
        }

        // Configuration globale d'iziToast
        iziToast.settings({
            timeout: 6000,
            transitionIn: 'flipInX',
            transitionOut: 'flipOutX',
        });

        // Affiche les alertes si elles existent
        if (window.dashmedAlerts && Array.isArray(window.dashmedAlerts)) {
            // Délai initial pour laisser la page charger
            setTimeout(() => {
                showAllAlerts(window.dashmedAlerts, 800);
            }, 1000);

            console.log(`[DashMedAlerts] ${window.dashmedAlerts.length} alerte(s) détectée(s)`);
        }
    }

    // Exposition publique de l'API
    return {
        init: init,
        showAlert: showAlertToast,
        showAllAlerts: showAllAlerts,
    };
})();

// Auto-initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', function () {
    DashMedAlerts.init();
});
