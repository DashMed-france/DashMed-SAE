'use strict';

const CLOSE_ICON = `
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path d="M6 18L18 6M6 6l12 12"/>
    </svg>`;

function scrollToCard(parameterId) {
    const panel = document.querySelector(`[data-param-id="${parameterId}"]`);
    if (panel) {
        const slug = panel.closest('[id^="detail-"]')?.id?.replace('detail-', '');
        if (slug) {
            const card = document.querySelector(`[data-slug="${slug}"]`);
            if (card) {
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                card.classList.add('card--highlight');
                setTimeout(() => card.classList.remove('card--highlight'), 2000);
                return;
            }
        }
    }
    const cardByParam = document.querySelector(`.card[data-detail-id*="${parameterId}"]`);
    if (cardByParam) {
        cardByParam.scrollIntoView({ behavior: 'smooth', block: 'center' });
        cardByParam.classList.add('card--highlight');
        setTimeout(() => cardByParam.classList.remove('card--highlight'), 2000);
    }
}

const _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
if (_audioCtx.state === 'suspended') {
    document.addEventListener('click', () => _audioCtx.resume(), { once: true });
}

const DashMedGlobalAlerts = (function () {
    const API_URL = 'api-alerts.php';
    const CHECK_INTERVAL = 300000;
    let displayedIds = new Set();
    let activeCriticalToasts = new Map();

    const esc = s => {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    };

    function parseAlertData(a) {
        const param = a.title?.split('—')[1]?.trim() || 'Paramètre';
        const valMatch = a.message?.match(/(\d+[,.]?\d*)\s*([^\(]+)/);
        const val = valMatch ? valMatch[1] : a.value || '—';
        const unit = valMatch ? valMatch[2].trim() : a.unit || '';
        const threshMatch = a.message?.match(/seuil\s+(min|max)\s*:\s*(\d+[,.]?\d*)\s*([^\)]*)/i);
        return {
            param,
            val,
            unit,
            threshType: threshMatch?.[1] || '',
            threshVal: threshMatch?.[2] || '',
            threshUnit: threshMatch?.[3]?.trim() || unit
        };
    }

    function buildToastHTML(a, type, timeout) {
        const { param, val, unit, threshType, threshVal, threshUnit } = parseAlertData(a);
        const hasCard = !!a.parameterId;
        return `
            <div class="medical-alert ${type} ${hasCard ? 'medical-alert--clickable' : ''}" ${hasCard ? `data-param-id="${esc(String(a.parameterId))}"` : ''}>
                <div class="medical-alert-body">
                    <div class="medical-alert-param">${esc(param)}</div>
                    <div class="medical-alert-value">${val}<span class="unit">${esc(unit)}</span></div>
                    <div class="medical-alert-threshold">Seuil ${threshType} attendu : <strong>${threshVal} ${esc(threshUnit)}</strong></div>
                </div>
                <button class="medical-alert-close" data-close>${CLOSE_ICON}</button>
                <div class="medical-alert-progress"><div class="medical-alert-progress-bar" style="animation-duration:${timeout}ms"></div></div>
            </div>`;
    }

    function buildInfoToastHTML(a, timeout) {
        const title = a.title?.split('—')[1]?.trim() || 'Rendez-vous';
        return `
            <div class="medical-alert info">
                <div class="medical-alert-body">
                    <div class="medical-alert-param">${esc(title)}</div>
                    <div class="medical-alert-value">${esc(a.rdvTime || '')}</div>
                    <div class="medical-alert-threshold">Dr <strong>${esc(a.doctor || '')}</strong></div>
                </div>
                <button class="medical-alert-close" data-close>${CLOSE_ICON}</button>
                <div class="medical-alert-progress"><div class="medical-alert-progress-bar" style="animation-duration:${timeout}ms"></div></div>
            </div>`;
    }

    const baseToastOpts = (msg, timeout) => ({
        message: msg,
        position: 'topRight',
        progressBar: false,
        close: false,
        timeout: timeout,
        transitionIn: 'fadeInLeft',
        transitionOut: 'fadeOutRight',
        layout: 1,
        backgroundColor: 'transparent',
        onOpening: (_, t) => t.querySelector('[data-close]')?.addEventListener('click', () => {
            iziToast.hide({}, t);
        })
    });

    function showWarningToast(a) {
        iziToast.warning({
            ...baseToastOpts(buildToastHTML(a, 'warning', 20000), 20000),
            onOpening: (_, t) => {
                t.querySelector('[data-close]')?.addEventListener('click', () => iziToast.hide({}, t));
                t.querySelector('.medical-alert--clickable')?.addEventListener('click', (e) => {
                    if (!e.target.closest('[data-close]')) {
                        scrollToCard(a.parameterId);
                    }
                });
            }
        });
    }

    function showInfoToast(a) {
        iziToast.info({ ...baseToastOpts(buildInfoToastHTML(a, 20000), 20000) });
    }

    function showCriticalToast(a) {
        const id = getAlertId(a);
        if (activeCriticalToasts.has(id)) return;

        const opts = {
            ...baseToastOpts(buildToastHTML(a, 'critical', 40000), 40000),
            onOpening: (_, t) => {
                activeCriticalToasts.set(id, t);
                t.querySelector('[data-close]')?.addEventListener('click', () => {
                    activeCriticalToasts.delete(id);
                    iziToast.hide({}, t);
                });
                t.querySelector('.medical-alert--clickable')?.addEventListener('click', (e) => {
                    if (!e.target.closest('[data-close]')) {
                        scrollToCard(a.parameterId);
                    }
                });
            },
            onClosed: () => {
                activeCriticalToasts.delete(id);
            }
        };

        iziToast.error(opts);
    }

    function dismissCriticalToast(parameterId) {
        for (const [id, toastEl] of activeCriticalToasts.entries()) {
            if (id.startsWith(parameterId + '_')) {
                iziToast.hide({}, toastEl);
                activeCriticalToasts.delete(id);
            }
        }
    }

    function getAlertId(a) {
        return `${a.parameterId}_${a.value || a.rdvTime || ''}`;
    }

    function playAlertSound(type) {
        const srcs = {
            error:   'assets/sounds/critical.wav',
            warning: 'assets/sounds/warning.wav',
            info:    'assets/sounds/info.wav',
        };
        const audio = new Audio(srcs[type] || srcs.warning);
        audio.volume = type === 'error' ? 1.0 : 0.6;
        _audioCtx.resume().then(() => {
            const source = _audioCtx.createMediaElementSource(audio);
            source.connect(_audioCtx.destination);
            audio.play().catch(() => {});
        });
    }

    function showAlert(a) {
        if (localStorage.getItem('dashmed_dnd') === 'true') return;
        if (!a?.type) return;
        const id = getAlertId(a);
        if (displayedIds.has(id)) return;
        if (typeof NotifHistory !== 'undefined' && NotifHistory.isInHistory(id)) {
            displayedIds.add(id);
            return;
        }
        displayedIds.add(id);
        if (typeof NotifHistory !== 'undefined') NotifHistory.add(a);

        playAlertSound(a.type);

        if (a.type === 'error') showCriticalToast(a);
        else if (a.type === 'info') showInfoToast(a);
        else showWarningToast(a);
    }

    async function fetchAlerts() {
        if (localStorage.getItem('dashmed_dnd') === 'true') return [];
        try {
            const room = new URLSearchParams(location.search).get('room') || '';
            const res = await fetch(room ? `${API_URL}?room=${room}` : API_URL);
            const data = await res.json();
            if (!data.success) return [];
            const alerts = data.alerts;
            const currentIds = new Set(alerts.map(a => getAlertId(a)));
            for (const [id] of activeCriticalToasts.entries()) {
                const parameterId = id.split('_')[0];
                const stillActive = alerts.some(a => a.type === 'error' && String(a.parameterId) === String(parameterId));
                if (!stillActive) dismissCriticalToast(parameterId);
            }
            return alerts;
        } catch { return []; }
    }

    async function check() {
        (await fetchAlerts()).forEach((a, i) => setTimeout(() => showAlert(a), i * 600));
    }

    function init() {
        if (typeof iziToast === 'undefined') return;
        setTimeout(check, 1500);
        setInterval(check, CHECK_INTERVAL);
    }

    return { init, checkNow: check };
})();

const NotifHistory = (function () {
    const STORAGE_KEY = 'notif_history_by_room';
    let panel = null, overlay = null;

    const getCurrentRoom = () => {
        const urlRoom = new URLSearchParams(location.search).get('room');
        if (urlRoom) return urlRoom;
        const cookieMatch = document.cookie.match(/room_id=(\d+)/);
        return cookieMatch ? cookieMatch[1] : null;
    };

    const getAllHistory = () => {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}; }
        catch { return {}; }
    };
    const getHistory = () => {
        const room = getCurrentRoom();
        if (!room) return [];
        const all = getAllHistory();
        return all[room] || [];
    };

    const saveHistory = h => {
        const room = getCurrentRoom();
        if (!room) return;
        const all = getAllHistory();
        all[room] = h.slice(0, 50);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(all));
    };

    const clearCurrentRoomHistory = () => {
        const room = getCurrentRoom();
        if (!room) return;
        const all = getAllHistory();
        delete all[room];
        localStorage.setItem(STORAGE_KEY, JSON.stringify(all));
    };

    function addToHistory(a) {
        const h = getHistory();
        h.unshift({ ...a, timestamp: Date.now() });
        saveHistory(h);
        updateBadge();
    }

    function removeFromHistory(i) {
        const h = getHistory();
        h.splice(i, 1);
        saveHistory(h);
        updateBadge();
    }

    function isInHistory(alertId) {
        const h = getHistory();
        return h.some(n => {
            const nId = `${n.parameterId}_${n.value || n.rdvTime || ''}`;
            return nId === alertId;
        });
    }

    function updateBadge() {
        const btn = document.querySelector('.action-btn[aria-label="Notifications"]');
        if (!btn) return;
        let badge = btn.querySelector('.notif-badge');
        const count = getHistory().length;
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notif-badge';
                btn.style.position = 'relative';
                btn.appendChild(badge);
            }
            badge.textContent = count > 9 ? '9+' : count;
        } else badge?.remove();
    }

    function formatTime(ts) {
        const d = new Date(ts), diff = Date.now() - d;
        if (diff < 60000) return 'À l\'instant';
        if (diff < 3600000) return `Il y a ${Math.floor(diff / 60000)} min`;
        if (diff < 86400000) return `Il y a ${Math.floor(diff / 3600000)}h`;
        return d.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function getDndState() {
        return localStorage.getItem('dashmed_dnd') === 'true';
    }

    function setDndState(enabled) {
        localStorage.setItem('dashmed_dnd', enabled);
        const toggle = panel?.querySelector('#notif-panel-dnd');
        if (toggle) toggle.checked = enabled;
        syncProfileToggle(enabled);
    }

    function syncProfileToggle(enabled) {
        const profileToggle = document.getElementById('dnd-dev-toggle');
        if (profileToggle) profileToggle.checked = enabled;
    }

    function createPanel() {
        overlay = document.createElement('div');
        overlay.className = 'notif-panel-overlay';
        overlay.addEventListener('click', close);
        panel = document.createElement('div');
        panel.className = 'notif-panel';
        panel.innerHTML = `
            <div class="notif-panel-header">
                <h2>Notifications</h2>
                <button class="notif-panel-close">${CLOSE_ICON}</button>
            </div>
            <div class="notif-panel-body"></div>
            <div class="notif-panel-dnd">
                <label class="notif-dnd-label">
                    <span>Ne pas déranger</span>
                    <div class="notif-dnd-toggle">
                        <input type="checkbox" id="notif-panel-dnd">
                        <span class="notif-dnd-slider"></span>
                    </div>
                </label>
            </div>
            <div class="notif-panel-footer">
                <button class="notif-clear-all">Tout effacer</button>
            </div>`;
        panel.querySelector('.notif-panel-close').addEventListener('click', close);
        panel.querySelector('.notif-clear-all').addEventListener('click', () => {
            clearCurrentRoomHistory();
            updateBadge();
            render();
        });
        const dndToggle = panel.querySelector('#notif-panel-dnd');
        dndToggle.checked = getDndState();
        dndToggle.addEventListener('change', e => setDndState(e.target.checked));
        document.body.appendChild(overlay);
        document.body.appendChild(panel);
    }

    function render() {
        if (!panel) createPanel();
        const body = panel.querySelector('.notif-panel-body'),
            footer = panel.querySelector('.notif-panel-footer'),
            h = getHistory();
        footer.style.display = h.length ? '' : 'none';
        if (!h.length) {
            body.innerHTML = '<div class="notif-panel-empty">Aucune notification</div>';
            return;
        }
        body.innerHTML = h.map((n, i) => {
            const type = n.type === 'error' ? 'critical' : (n.type === 'info' ? 'info' : 'warning');
            const param = n.title?.split('—')[1]?.trim() || n.rdvTime || 'Alerte';
            const valMatch = n.message?.match(/(\d+[,.]?\d*)\s*([^\(]+)/);
            const val = valMatch ? `${valMatch[1]} ${valMatch[2].trim()}` : (n.rdvTime || '—');
            const hasCard = type !== 'info' && !!n.parameterId;
            return `<div class="notif-item ${type} ${hasCard ? 'notif-item--clickable' : ''}" data-idx="${i}" ${hasCard ? `data-param-id="${n.parameterId}"` : ''}>
                <div class="notif-item-param">${param}</div>
                <div class="notif-item-value">${val}</div>
                <div class="notif-item-time">${formatTime(n.timestamp)}</div>
                <button class="notif-item-delete">${CLOSE_ICON}</button>
            </div>`;
        }).join('');
        body.querySelectorAll('.notif-item-delete').forEach(btn => btn.addEventListener('click', e => {
            e.stopPropagation();
            const item = btn.closest('.notif-item');
            item.classList.add('removing');
            setTimeout(() => {
                removeFromHistory(+item.dataset.idx);
                render();
            }, 250);
        }));
        body.querySelectorAll('.notif-item--clickable').forEach(item => item.addEventListener('click', e => {
            if (!e.target.closest('.notif-item-delete')) {
                scrollToCard(item.dataset.paramId);
                close();
            }
        }));
    }

    const open = () => {
        render();
        const dndToggle = panel?.querySelector('#notif-panel-dnd');
        if (dndToggle) dndToggle.checked = getDndState();
        overlay?.classList.add('active');
        panel?.classList.add('active');
    };

    const close = () => {
        overlay?.classList.remove('active');
        panel?.classList.remove('active');
    };

    function init() {
        const btn = document.querySelector('.action-btn[aria-label="Notifications"]');
        btn?.addEventListener('click', e => {
            e.preventDefault();
            open();
        });
        updateBadge();

        const profileToggle = document.getElementById('dnd-dev-toggle');
        if (profileToggle) {
            profileToggle.addEventListener('change', e => setDndState(e.target.checked));
        }
    }

    return { init, add: addToHistory, isInHistory };
})();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', DashMedGlobalAlerts.init);
    document.addEventListener('DOMContentLoaded', NotifHistory.init);
} else {
    DashMedGlobalAlerts.init();
    NotifHistory.init();
}