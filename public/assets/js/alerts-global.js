/**
 * DashMed - Notifications Globales Modernes
 */
'use strict';

const DashMedGlobalAlerts = (function () {
    const API_URL = 'api-alerts.php';
    const CHECK_INTERVAL = 30000;
    let displayedIds = new Set();

    // Icônes SVG minimalistes
    const ICONS = {
        critical: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12"/></svg>'
    };

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

    function buildHTML(alert) {
        const severity = alert.type === 'error' ? 'critical' : 'warning';
        const { param, val, unit, threshType, threshVal, threshUnit } = parseAlertData(alert);
        const icon = ICONS[severity];

        return `
<div class="medical-alert ${severity}">
    <div class="medical-alert-icon">${icon}</div>
    <div class="medical-alert-body">
        <div class="medical-alert-param">${escapeHTML(param)}</div>
        <div class="medical-alert-value">${val}<span class="unit">${escapeHTML(unit)}</span></div>
        <div class="medical-alert-threshold">Seuil ${threshType} : <strong>${threshVal} ${escapeHTML(threshUnit)}</strong></div>
    </div>
    <button class="medical-alert-close" data-close>${ICONS.close}</button>
</div>`;
    }

    function showAlert(alert) {
        if (!alert?.type) return;
        const id = `${alert.parameterId}_${alert.value}`;
        if (displayedIds.has(id)) return;
        displayedIds.add(id);

        const opts = {
            message: buildHTML(alert),
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

        alert.type === 'error' ? iziToast.error(opts) : iziToast.warning(opts);
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
