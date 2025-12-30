/**
 * DashMed - Système global de notifications d'alertes
 * 
 * Ce script charge les alertes via API pour affichage sur TOUTES les pages.
 * Inclure ce fichier dans toutes les vues pour avoir les notifications partout.
 */

'use strict';

const DashMedGlobalAlerts = (function () {
    const API_URL = 'api-alerts.php';
    const CHECK_INTERVAL = 30000; // Vérifie toutes les 30 secondes
    let lastCheckTime = 0;
    let displayedAlertIds = new Set(); // Pour éviter les doublons

    /**
     * Configuration par défaut des toasts
     */
    const defaultConfig = {
        position: 'topRight',
        timeout: 8000,
        progressBar: true,
        close: true,
        transitionIn: 'flipInX',
        transitionOut: 'flipOutX',
        pauseOnHover: true,
        resetOnHover: true,
        displayMode: 'once', // Affiche toutes les alertes (pas de remplacement)
        layout: 2,
        maxWidth: 400,
    };

    /**
     * Récupère les alertes depuis l'API
     */
    async function fetchAlerts() {
        try {
            // Récupère room_id depuis l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const room = urlParams.get('room') || getCookie('room_id') || '';

            const url = room ? `${API_URL}?room=${room}` : API_URL;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                cache: 'no-cache'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.alerts && data.alerts.length > 0) {
                console.log(`[DashMed Global] ${data.alerts.length} alerte(s) reçue(s)`);
                return data.alerts;
            }

            return [];
        } catch (error) {
            console.error('[DashMed Global] Erreur fetch alertes:', error);
            return [];
        }
    }

    /**
     * Récupère un cookie par son nom
     */
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    /**
     * Affiche une alerte en toast
     */
    function showAlert(alert) {
        if (!alert || !alert.type) {
            return;
        }

        // Génère un ID unique pour éviter les doublons
        const alertId = `${alert.parameterId}_${alert.value}_${alert.type}`;

        if (displayedAlertIds.has(alertId)) {
            return; // Déjà affichée
        }

        displayedAlertIds.add(alertId);

        const toastOptions = {
            ...defaultConfig,
            title: alert.title || 'Alerte',
            message: alert.message || '',
            buttons: [
                ['<button>Fermer</button>', function (instance, toast) {
                    instance.hide({ transitionOut: 'fadeOut' }, toast);
                }]
            ]
        };

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
            default:
                iziToast.show(toastOptions);
        }
    }

    /**
     * Vérifie et affiche les nouvelles alertes
     */
    async function checkAndShowAlerts() {
        const now = Date.now();

        // Évite de checker trop souvent
        if (now - lastCheckTime < 5000) {
            return;
        }

        lastCheckTime = now;

        const alerts = await fetchAlerts();

        if (alerts.length > 0) {
            alerts.forEach((alert, index) => {
                setTimeout(() => {
                    showAlert(alert);
                }, index * 800); // Délai de 800ms entre chaque toast
            });
        }
    }

    /**
     * Initialise le système de notifications global
     */
    function init() {
        // Vérifie que iziToast est chargé
        if (typeof iziToast === 'undefined') {
            console.error('[DashMed Global] iziToast non chargé!');
            return;
        }

        console.log('[DashMed Global] Système de notifications initialisé');

        // Premier check immédiat (avec délai pour laisser la page charger)
        setTimeout(checkAndShowAlerts, 2000);

        // Checks périodiques
        setInterval(checkAndShowAlerts, CHECK_INTERVAL);
    }

    // Exposition publique
    return {
        init: init,
        checkNow: checkAndShowAlerts
    };
})();

// Auto-initialisation au chargement du DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', DashMedGlobalAlerts.init);
} else {
    DashMedGlobalAlerts.init();
}
