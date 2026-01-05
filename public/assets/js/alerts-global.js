/**
 * DashMed - Notifications Globales
 */
'use strict';

const CLOSE_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12"/></svg>';

const DashMedGlobalAlerts = (function () {
    const API_URL = 'api-alerts.php', CHECK_INTERVAL = 300000;
    let displayedIds = new Set(), criticalModal = null;

    const MEDICAL_ICON = `<svg class="medical-alert-icon" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"><rect x="10" y="20" width="55" height="65" rx="5"/><rect x="25" y="12" width="25" height="12" rx="3"/><line x1="20" y1="40" x2="50" y2="40"/><line x1="20" y1="52" x2="55" y2="52"/><line x1="20" y1="64" x2="45" y2="64"/><path d="M55 55 C55 55, 70 45, 70 30 C70 20, 78 15, 85 15 C92 15, 92 25, 85 25"/><path d="M85 25 C85 25, 85 35, 75 45 C65 55, 65 70, 75 80"/><circle cx="75" cy="85" r="6"/><ellipse cx="47" cy="60" rx="18" ry="12"/><line x1="40" y1="55" x2="40" y2="65"/><line x1="50" y1="55" x2="50" y2="65"/></svg>`;

    const esc = s => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };

    function parseAlertData(a) {
        const param = a.title?.split('—')[1]?.trim() || 'Paramètre';
        const valMatch = a.message?.match(/(\d+[,.]?\d*)\s*([^\(]+)/);
        const val = valMatch ? valMatch[1] : a.value || '—';
        const unit = valMatch ? valMatch[2].trim() : a.unit || '';
        const threshMatch = a.message?.match(/seuil\s+(min|max)\s*:\s*(\d+[,.]?\d*)\s*([^\)]*)/i);
        return { param, val, unit, threshType: threshMatch?.[1] || '', threshVal: threshMatch?.[2] || '', threshUnit: threshMatch?.[3]?.trim() || unit };
    }

    function buildToastHTML(a) {
        const { param, val, unit, threshType, threshVal, threshUnit } = parseAlertData(a);
        return `<div class="medical-alert warning"><div class="medical-alert-body"><div class="medical-alert-param">${esc(param)}</div><div class="medical-alert-value">${val}<span class="unit">${esc(unit)}</span></div><div class="medical-alert-threshold">Seuil ${threshType} attendu : <strong>${threshVal} ${esc(threshUnit)}</strong></div></div><button class="medical-alert-close" data-close>${CLOSE_ICON}</button></div>`;
    }

    function buildInfoToastHTML(a) {
        const title = a.title?.split('—')[1]?.trim() || 'Rendez-vous';
        return `<div class="medical-alert info"><div class="medical-alert-body"><div class="medical-alert-param">${esc(title)}</div><div class="medical-alert-value">${esc(a.rdvTime || '')}</div><div class="medical-alert-threshold">Dr <strong>${esc(a.doctor || '')}</strong></div></div>${MEDICAL_ICON}<button class="medical-alert-close" data-close>${CLOSE_ICON}</button></div>`;
    }

    const toastOpts = (msg) => ({
        message: msg, position: 'topRight', progressBar: true, close: false,
        transitionIn: 'fadeInLeft', transitionOut: 'fadeOutRight', layout: 1, backgroundColor: 'transparent',
        onOpening: (_, t) => t.querySelector('[data-close]')?.addEventListener('click', () => iziToast.hide({}, t))
    });

    function showWarningToast(a) { iziToast.warning({ ...toastOpts(buildToastHTML(a)), timeout: 20000 }); }
    function showInfoToast(a) { iziToast.info({ ...toastOpts(buildInfoToastHTML(a)), timeout: 15000 }); }

    function getCriticalContainer() {
        if (criticalModal) return criticalModal;
        const overlay = document.createElement('div');
        overlay.className = 'critical-modal-overlay';
        overlay.innerHTML = `<div class="critical-modal-container"><button class="critical-modal-close-all">${CLOSE_ICON}</button><div class="critical-modal-grid"></div><button class="critical-modal-action">Voir le tableau de bord</button></div>`;
        document.body.appendChild(overlay);
        criticalModal = overlay;
        overlay.querySelector('.critical-modal-close-all').addEventListener('click', closeCriticalModal);
        overlay.addEventListener('click', e => e.target === overlay && closeCriticalModal());
        overlay.querySelector('.critical-modal-action').addEventListener('click', () => { closeCriticalModal(); window.location.href = '/?page=dashboard'; });
        return overlay;
    }

    function closeCriticalModal() {
        if (!criticalModal) return;
        criticalModal.classList.remove('active');
        criticalModal.querySelector('.critical-modal-grid').innerHTML = '';
    }

    function showCriticalModal(a) {
        const { param, val, unit, threshType, threshVal, threshUnit } = parseAlertData(a);
        const container = getCriticalContainer(), grid = container.querySelector('.critical-modal-grid');
        const card = document.createElement('div');
        card.className = 'critical-modal-card';
        card.innerHTML = `<div class="critical-alert-urgent">CRITIQUE</div><div class="critical-alert-param">${esc(param)}</div><div class="critical-alert-value">${val}<span class="unit">${esc(unit)}</span></div><div class="critical-alert-threshold">Seuil ${threshType} attendu : <strong>${threshVal} ${esc(threshUnit)}</strong></div>`;
        grid.appendChild(card);
        container.classList.add('active');
    }

    function showAlert(a) {
        if (!a?.type) return;
        const id = `${a.parameterId}_${a.value || a.rdvTime || ''}`;
        if (displayedIds.has(id)) return;
        displayedIds.add(id);
        if (typeof NotifHistory !== 'undefined') NotifHistory.add(a);
        if (a.type === 'error') showCriticalModal(a);
        else if (a.type === 'info') showInfoToast(a);
        else showWarningToast(a);
    }

    async function fetchAlerts() {
        try {
            const room = new URLSearchParams(location.search).get('room') || '';
            const res = await fetch(room ? `${API_URL}?room=${room}` : API_URL);
            const data = await res.json();
            return data.success ? data.alerts : [];
        } catch { return []; }
    }

    async function check() { (await fetchAlerts()).forEach((a, i) => setTimeout(() => showAlert(a), i * 600)); }

    function init() {
        if (typeof iziToast === 'undefined') return;
        setTimeout(check, 1500);
        setInterval(check, CHECK_INTERVAL);
    }

    return { init, checkNow: check };
})();

const NotifHistory = (function () {
    const STORAGE_KEY = 'notif_history';
    let panel = null, overlay = null;

    const getHistory = () => { try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; } catch { return []; } };
    const saveHistory = h => localStorage.setItem(STORAGE_KEY, JSON.stringify(h.slice(0, 50)));

    function addToHistory(a) { const h = getHistory(); h.unshift({ ...a, timestamp: Date.now() }); saveHistory(h); updateBadge(); }
    function removeFromHistory(i) { const h = getHistory(); h.splice(i, 1); saveHistory(h); updateBadge(); }

    function updateBadge() {
        const btn = document.querySelector('.action-btn[aria-label="Notifications"]');
        if (!btn) return;
        let badge = btn.querySelector('.notif-badge');
        const count = getHistory().length;
        if (count > 0) {
            if (!badge) { badge = document.createElement('span'); badge.className = 'notif-badge'; btn.style.position = 'relative'; btn.appendChild(badge); }
            badge.textContent = count > 9 ? '9+' : count;
        } else badge?.remove();
    }

    function formatTime(ts) {
        const d = new Date(ts), diff = Date.now() - d;
        if (diff < 60000) return 'À l\'instant';
        if (diff < 3600000) return `Il y a ${Math.floor(diff / 60000)} min`;
        if (diff < 86400000) return `Il y a ${Math.floor(diff / 3600000)}h`;
        return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
    }

    function createPanel() {
        overlay = document.createElement('div');
        overlay.className = 'notif-panel-overlay';
        overlay.addEventListener('click', close);
        panel = document.createElement('div');
        panel.className = 'notif-panel';
        panel.innerHTML = `<div class="notif-panel-header"><h2>Notifications</h2><button class="notif-panel-close">${CLOSE_ICON}</button></div><div class="notif-panel-body"></div><div class="notif-panel-footer"><button class="notif-clear-all">Tout effacer</button></div>`;
        panel.querySelector('.notif-panel-close').addEventListener('click', close);
        panel.querySelector('.notif-clear-all').addEventListener('click', () => { localStorage.removeItem(STORAGE_KEY); updateBadge(); render(); });
        document.body.appendChild(overlay);
        document.body.appendChild(panel);
    }

    function render() {
        if (!panel) createPanel();
        const body = panel.querySelector('.notif-panel-body'), footer = panel.querySelector('.notif-panel-footer'), h = getHistory();
        footer.style.display = h.length ? '' : 'none';
        if (!h.length) { body.innerHTML = '<div class="notif-panel-empty">Aucune notification</div>'; return; }
        body.innerHTML = h.map((n, i) => {
            const type = n.type === 'error' ? 'critical' : (n.type === 'info' ? 'info' : '');
            const param = n.title?.split('—')[1]?.trim() || n.rdvTime || 'Alerte';
            const valMatch = n.message?.match(/(\d+[,.]?\d*)\s*([^\(]+)/);
            const val = valMatch ? `${valMatch[1]} ${valMatch[2].trim()}` : (n.rdvTime || '—');
            return `<div class="notif-item ${type}" data-idx="${i}"><div class="notif-item-param">${param}</div><div class="notif-item-value">${val}</div><div class="notif-item-time">${formatTime(n.timestamp)}</div><button class="notif-item-delete">${CLOSE_ICON}</button></div>`;
        }).join('');
        body.querySelectorAll('.notif-item-delete').forEach(btn => btn.addEventListener('click', e => {
            e.stopPropagation();
            const item = btn.closest('.notif-item');
            item.classList.add('removing');
            setTimeout(() => { removeFromHistory(+item.dataset.idx); render(); }, 250);
        }));
    }

    const open = () => { render(); overlay?.classList.add('active'); panel?.classList.add('active'); };
    const close = () => { overlay?.classList.remove('active'); panel?.classList.remove('active'); };

    function init() {
        document.querySelector('.action-btn[aria-label="Notifications"]')?.addEventListener('click', e => { e.preventDefault(); open(); });
        updateBadge();
    }

    return { init, add: addToHistory };
})();

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', DashMedGlobalAlerts.init) : DashMedGlobalAlerts.init();
document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', NotifHistory.init) : NotifHistory.init();
