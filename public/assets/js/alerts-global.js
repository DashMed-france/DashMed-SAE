/**
 * DashMed - Notifications Globales Modernes
 * - Alertes critiques : modale centrée avec bouton "Voir"
 * - Alertes warning : toast en haut à droite
 */
'use strict';

const DashMedGlobalAlerts = (function () {
    const API_URL = 'api-alerts.php';
    const CHECK_INTERVAL = 30000;
    let displayedIds = new Set();
    let criticalModal = null;

    // Icône de fermeture
    const CLOSE_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12"/></svg>';

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
        <div class="medical-alert-threshold">Seuil ${threshType} : <strong>${threshVal} ${escapeHTML(threshUnit)}</strong></div>
    </div>
    <button class="medical-alert-close" data-close>${CLOSE_ICON}</button>
</div>`;
    }

    // Afficher une alerte warning en toast
    function showWarningToast(alert) {
        const opts = {
            message: buildToastHTML(alert),
            position: 'topRight',
            timeout: 12000,
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

    // Créer ou récupérer la modale critique
    function getCriticalModal() {
        if (criticalModal) return criticalModal;

        const overlay = document.createElement('div');
        overlay.className = 'critical-modal-overlay';
        overlay.innerHTML = `
            <div class="critical-modal">
                <button class="critical-modal-close">${CLOSE_ICON}</button>
                <div class="critical-modal-content"></div>
                <button class="critical-modal-action">Voir</button>
            </div>
        `;

        document.body.appendChild(overlay);
        criticalModal = overlay;

        // Fermer la modale
        overlay.querySelector('.critical-modal-close').addEventListener('click', () => closeCriticalModal());
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
        }
    }

    // Afficher une alerte critique en modale centrée
    function showCriticalModal(alert) {
        const { param, val, unit, threshType, threshVal, threshUnit } = parseAlertData(alert);
        const modal = getCriticalModal();
        const content = modal.querySelector('.critical-modal-content');

        content.innerHTML = `
            <div class="critical-alert-param">${escapeHTML(param)}</div>
            <div class="critical-alert-value">${val}<span class="unit">${escapeHTML(unit)}</span></div>
            <div class="critical-alert-threshold">Seuil ${threshType} : <strong>${threshVal} ${escapeHTML(threshUnit)}</strong></div>
        `;

        modal.classList.add('active');
    }

    function showAlert(alert) {
        if (!alert?.type) return;
        const id = `${alert.parameterId}_${alert.value}`;
        if (displayedIds.has(id)) return;
        displayedIds.add(id);

        if (alert.type === 'error') {
            // Alerte critique -> modale centrée
            showCriticalModal(alert);
        } else {
            // Alerte warning -> toast en haut à droite
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

