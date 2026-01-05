/**
 * DashMed - Notifications Globales Modernes
 * - Alertes critiques : modale centrée avec bouton "Voir"
 * - Alertes warning : toast en haut à droite
 */
'use strict';

const DashMedGlobalAlerts = (function () {
    const API_URL = 'api-alerts.php';
    const CHECK_INTERVAL = 300000; // 5 minutes
    let displayedIds = new Set();
    let criticalModal = null;

    // Icône de fermeture
    const CLOSE_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12"/></svg>';

    // Icône médicale (stéthoscope + clipboard)
    const MEDICAL_ICON = `<svg class="medical-alert-icon" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round">
        <!-- Clipboard -->
        <rect x="10" y="20" width="55" height="65" rx="5"/>
        <rect x="25" y="12" width="25" height="12" rx="3"/>
        <line x1="20" y1="40" x2="50" y2="40"/>
        <line x1="20" y1="52" x2="55" y2="52"/>
        <line x1="20" y1="64" x2="45" y2="64"/>
        <!-- Stethoscope -->
        <path d="M55 55 C55 55, 70 45, 70 30 C70 20, 78 15, 85 15 C92 15, 92 25, 85 25"/>
        <path d="M85 25 C85 25, 85 35, 75 45 C65 55, 65 70, 75 80"/>
        <circle cx="75" cy="85" r="6"/>
        <!-- Magnifying lens on clipboard -->
        <ellipse cx="47" cy="60" rx="18" ry="12"/>
        <line x1="40" y1="55" x2="40" y2="65"/>
        <line x1="50" y1="55" x2="50" y2="65"/>
    </svg>`;

    function escapeHTML(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function parseAlertData(alert) {
        const param = alert.title?.split('—')[1]?.trim() || 'Paramètre';
        const valMatch = alert.message?.match(/(\d+[,.]?\d*)\s*([^\(]+)/);
        const val = valMatch ? valMatch[1] : alert.value || '—';
        const unit = valMatch ? valMatch[2].trim() : alert.unit || '';
        const threshMatch = alert.message?.match(/seuil\s+(min|max)\s*:\s*(\d+[,.]?\d*)\s*([^\)]*)/i);
        const threshType = threshMatch ? threshMatch[1] : '';
        const threshVal = threshMatch ? threshMatch[2] : '';
        const threshUnit = threshMatch ? threshMatch[3].trim() : unit;
        return { param, val, unit, threshType, threshVal, threshUnit };
    }

    // HTML pour les toasts (warnings)
    function buildToastHTML(alert) {
        const { param, val, unit, threshType, threshVal, threshUnit } = parseAlertData(alert);

        return `
<div class="medical-alert warning">
    <div class="medical-alert-body">
        <div class="medical-alert-param">${escapeHTML(param)}</div>
        <div class="medical-alert-value">${val}<span class="unit">${escapeHTML(unit)}</span></div>
        <div class="medical-alert-threshold">Seuil ${threshType} attendu : <strong>${threshVal} ${escapeHTML(threshUnit)}</strong></div>
    </div>
    <button class="medical-alert-close" data-close>${CLOSE_ICON}</button>
</div>`;
    }

    // Afficher une alerte warning en toast
    function showWarningToast(alert) {
        const opts = {
            message: buildToastHTML(alert),
            position: 'topRight',
            timeout: 20000,
            progressBar: true,
            close: false,
            transitionIn: 'fadeInLeft',
            transitionOut: 'fadeOutRight',
            layout: 1,
            backgroundColor: 'transparent',
            onOpening: (_, toast) => {
                toast.querySelector('[data-close]')?.addEventListener('click', () => iziToast.hide({}, toast));
            }
        };
        iziToast.warning(opts);
    }

    // Créer ou récupérer le conteneur des modales critiques
    function getCriticalContainer() {
        if (criticalModal) return criticalModal;

        const overlay = document.createElement('div');
        overlay.className = 'critical-modal-overlay';
        overlay.innerHTML = `
            <div class="critical-modal-container">
                <button class="critical-modal-close-all">${CLOSE_ICON}</button>
                <div class="critical-modal-grid"></div>
                <button class="critical-modal-action">Voir le tableau de bord</button>
            </div>
        `;

        document.body.appendChild(overlay);
        criticalModal = overlay;

        // Fermer toutes les modales
        overlay.querySelector('.critical-modal-close-all').addEventListener('click', () => closeCriticalModal());
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeCriticalModal();
        });

        // Bouton "Voir" -> redirection dashboard
        overlay.querySelector('.critical-modal-action').addEventListener('click', () => {
            closeCriticalModal();
            window.location.href = '/?page=dashboard';
        });

        return overlay;
    }

    function closeCriticalModal() {
        if (criticalModal) {
            criticalModal.classList.remove('active');
            // Vider la grille pour le prochain check
            const grid = criticalModal.querySelector('.critical-modal-grid');
            if (grid) grid.innerHTML = '';
        }
    }

    // Ajouter une alerte critique à la grille
    function showCriticalModal(alert) {
        const { param, val, unit, threshType, threshVal, threshUnit } = parseAlertData(alert);
        const container = getCriticalContainer();
        const grid = container.querySelector('.critical-modal-grid');

        // Créer une carte pour cette alerte
        const card = document.createElement('div');
        card.className = 'critical-modal-card';
        card.innerHTML = `
            <div class="critical-alert-urgent">CRITIQUE</div>
            <div class="critical-alert-param">${escapeHTML(param)}</div>
            <div class="critical-alert-value">${val}<span class="unit">${escapeHTML(unit)}</span></div>
            <div class="critical-alert-threshold">Seuil ${threshType} attendu : <strong>${threshVal} ${escapeHTML(threshUnit)}</strong></div>
        `;

        grid.appendChild(card);
        container.classList.add('active');
    }

    // HTML pour les toasts info (RDV)
    function buildInfoToastHTML(alert) {
        const title = alert.title?.split('—')[1]?.trim() || 'Rendez-vous';
        const time = alert.rdvTime || '';
        const doctor = alert.doctor || '';
        return `
<div class="medical-alert info">
    <div class="medical-alert-body">
        <div class="medical-alert-param">${escapeHTML(title)}</div>
        <div class="medical-alert-value">${escapeHTML(time)}</div>
        <div class="medical-alert-threshold">Dr <strong>${escapeHTML(doctor)}</strong></div>
    </div>
    ${MEDICAL_ICON}
    <button class="medical-alert-close" data-close>${CLOSE_ICON}</button>
</div>`;
    }

    // Afficher une notification info (RDV)
    function showInfoToast(alert) {
        iziToast.info({
            message: buildInfoToastHTML(alert),
            position: 'topRight',
            timeout: 15000,
            progressBar: true,
            close: false,
            transitionIn: 'fadeInLeft',
            transitionOut: 'fadeOutRight',
            layout: 1,
            backgroundColor: 'transparent',
            onOpening: (_, toast) => {
                toast.querySelector('[data-close]')?.addEventListener('click', () => iziToast.hide({}, toast));
            }
        });
    }

    function showAlert(alert) {
        if (!alert?.type) return;
        const id = `${alert.parameterId}_${alert.value || alert.rdvTime || ''}`;
        if (displayedIds.has(id)) return;
        displayedIds.add(id);

        if (alert.type === 'error') {
            showCriticalModal(alert);
        } else if (alert.type === 'info') {
            showInfoToast(alert);
        } else {
            showWarningToast(alert);
        }
    }

    async function fetchAlerts() {
        try {
            const room = new URLSearchParams(location.search).get('room') || '';
            const res = await fetch(room ? `${API_URL}?room=${room}` : API_URL);
            const data = await res.json();
            return data.success ? data.alerts : [];
        } catch { return []; }
    }

    async function check() {
        const alerts = await fetchAlerts();
        alerts.forEach((a, i) => setTimeout(() => showAlert(a), i * 600));
    }

    function init() {
        if (typeof iziToast === 'undefined') return;
        setTimeout(check, 1500);
        setInterval(check, CHECK_INTERVAL);
    }

    return { init, checkNow: check };
})();

document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', DashMedGlobalAlerts.init)
    : DashMedGlobalAlerts.init();

