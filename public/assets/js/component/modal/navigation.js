function formatTime(isoTime) {
    if (!isoTime) return '—';
    const d = new Date(isoTime);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function computeState(num, nmin, nmax, cmin, cmax, flag) {
    if (isNaN(num)) return '—';

    const isCrit = flag || (!isNaN(cmin) && num <= cmin) || (!isNaN(cmax) && num >= cmax);
    if (isCrit) return 'Constante critique 🚨';

    const inNorm = (!isNaN(nmin) && !isNaN(nmax)) ? (num >= nmin && num <= nmax) : true;
    let near = false;

    if (!isNaN(nmin) && !isNaN(nmax) && nmax > nmin) {
        const w = nmax - nmin;
        const m = 0.10 * w;
        if (num >= nmin && num <= nmax) {
            if ((num - nmin) <= m || (nmax - num) <= m) {
                near = true;
            }
        }
    }

    return (!inNorm || near) ? 'Prévention d\'alerte ⚠️' : 'Constante normale ✅';
}

function updateStateClass(stateEl, state) {
    if (!stateEl) return;
    stateEl.className = 'modal-state';
    if (state.includes('critique')) {
        stateEl.classList.add('alert');
    } else if (state.includes('Prévention') || state.includes('⚠️')) {
        stateEl.classList.add('warn');
    } else if (state.includes('normale') || state.includes('stable')) {
        stateEl.classList.add('stable');
    }
}

function navigateHistory(panelId, chartId, title, direction) {
    const root = document.getElementById('modalDetails');
    if (!root) return;

    const panel = root.querySelector('#' + panelId);
    if (!panel) return;

    const list = panel.querySelectorAll('ul[data-hist]>li');
    if (!list.length) return;

    let idx = parseInt(panel.getAttribute('data-idx') || '0', 10);
    idx += direction;

    if (idx < 0) idx = 0;
    if (idx >= list.length) idx = list.length - 1;

    panel.setAttribute('data-idx', idx);

    const item = list[idx];
    const time = item.dataset.time || '';
    const val = item.dataset.value || '';
    const flag = item.dataset.flag === '1';

    const timeEl = panel.querySelector('[data-field=time]');
    if (timeEl) {
        timeEl.setAttribute('data-time', time);
        timeEl.textContent = formatTime(time);
    }

    const nmin = parseFloat(panel.dataset.nmin);
    const nmax = parseFloat(panel.dataset.nmax);
    const cmin = parseFloat(panel.dataset.cmin);
    const cmax = parseFloat(panel.dataset.cmax);
    const num = parseFloat(val);

    const state = computeState(num, nmin, nmax, cmin, cmax, flag);

    const stateEl = panel.querySelector('[data-field=state]');
    if (stateEl) {
        stateEl.textContent = state;
        updateStateClass(stateEl, state);
    }

    const unit = panel.dataset.unit || '';
    const valueEl = root.querySelector('.modal-value');
    if (valueEl) {
        valueEl.textContent = val + (unit ? (' ' + unit) : '') + (flag ? ' — critique 🚨' : '');
    }

    updatePanelPieChart(panelId, chartId, title);
}

function openModalCard(display, value, critFlag, detailId, slug, chartData) {
    openModal(display, value, critFlag);

    const detailsSrc = document.getElementById(detailId);
    const modalDetails = document.getElementById('modalDetails');
    modalDetails.innerHTML = detailsSrc ? detailsSrc.innerHTML : '<p>Aucun détail disponible.</p>';

    const canvas = modalDetails.querySelector('.modal-chart');
    if (!canvas) return;
    canvas.id = canvas.dataset.id;

    if (chartData.type === 'pie' || chartData.type === 'doughnut') {
        updatePanelPieChart('panel-' + slug, chartData.target, chartData.title);
    } else {
        createChart(
            chartData.type || 'line',
            chartData.title,
            chartData.labels,
            chartData.data,
            chartData.target,
            chartData.color,
            chartData.thresholds,
            chartData.view
        );
    }
}
