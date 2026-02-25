const finiteVals = (arr) => (arr ?? []).map(item => item && typeof item === 'object' && item.y !== undefined ? item.y : item).map(Number).filter(Number.isFinite);

function generateChartData(rawData, visibleSpanMs) {
    if (!rawData || !rawData.length) return { labels: [], data: [], points: [] };

    rawData.sort((a, b) => a.time.getTime() - b.time.getTime());

    let spanH = (rawData[rawData.length - 1].time - rawData[0].time) / 3600000;
    if (visibleSpanMs !== undefined) {
        spanH = visibleSpanMs / 3600000;
    }

    let points = rawData.map(d => ({ time: d.time, value: d.value }));

    const labels = [];
    const data = [];

    points.forEach((p) => {
        data.push({ x: p.time.getTime(), y: p.value });
    });

    return { labels, data, points };
}

const wheelPreventPlugin = {
    id: 'wheelPrevent',
    afterInit: (chart) => {
        chart.canvas.addEventListener('wheel', (e) => {
            if (chart.options?.plugins?.zoom?.zoom?.wheel?.enabled) {
                e.preventDefault();
            }
        }, { passive: false });
    }
};

const daySeparatorPlugin = {
    id: 'daySeparator',
    afterDraw: (chart) => {
        const xScale = chart.scales.x;
        const yScale = chart.scales.y;
        if (!xScale || !yScale || xScale.type !== 'time') return;

        const { ctx, chartArea } = chart;
        if (!chartArea) return;

        const minMs = xScale.min;
        const maxMs = xScale.max;
        const spanMs = maxMs - minMs;

        if (spanMs < 2 * 3600 * 1000) return;

        const startDate = new Date(minMs);
        startDate.setHours(0, 0, 0, 0);
        startDate.setDate(startDate.getDate() + 1);

        const tickColor = getComputedStyle(chart.canvas).getPropertyValue('--chart-tick-color').trim() || 'rgba(255,255,255,0.35)';

        ctx.save();
        ctx.strokeStyle = tickColor;
        ctx.lineWidth = 1.5;
        ctx.setLineDash([6, 4]);
        ctx.globalAlpha = 0.5;

        let cursor = startDate.getTime();
        while (cursor <= maxMs) {
            const x = xScale.getPixelForValue(cursor);
            if (x >= chartArea.left && x <= chartArea.right) {
                ctx.beginPath();
                ctx.moveTo(x, chartArea.top);
                ctx.lineTo(x, chartArea.bottom);
                ctx.stroke();
            }
            cursor += 24 * 3600 * 1000;
        }

        ctx.restore();
    }
};

function makeBaseConfig({ type, title, view }) {
    const config = {
        type,
        data: { datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            parsing: false,
            normalized: false,
            animation: false,
            animations: {
                colors: false,
                x: false
            },
            transitions: {
                active: {
                    animation: {
                        duration: 0
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            plugins: {
                legend: {
                    display: type === 'pie' || type === 'doughnut',
                    position: 'top',
                    labels: {
                        font: { size: 14 },
                        filter: function (item) { return !item.text || !item.text.startsWith('_'); }
                    }
                },
                decimation: {
                    enabled: true,
                    algorithm: 'min-max',
                },
                tooltip: {
                    backgroundColor: 'rgba(24, 24, 27, 0.95)',
                    titleColor: '#A1A1AA',
                    bodyColor: '#FAFAFA',
                    borderColor: '#3F3F46',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        title: function (context) {
                            if (!context.length) return '';
                            const raw = context[0].parsed.x;
                            if (!raw) return context[0].label || '';
                            const d = new Date(raw);
                            const now = new Date();
                            const todayStr = now.toDateString();
                            const yesterdayStr = new Date(now - 86400000).toDateString();
                            const time = d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                            if (d.toDateString() === todayStr) {
                                return `Aujourd'hui à ${time}`;
                            } else if (d.toDateString() === yesterdayStr) {
                                return `Hier à ${time}`;
                            } else {
                                const dateStr = d.toLocaleDateString('fr-FR', { weekday: 'long', day: '2-digit', month: 'long' });
                                return `${dateStr} à ${time}`;
                            }
                        },
                        label: function (context) {
                            return context.parsed.y;
                        }
                    }
                },
                title: { display: false, text: title },
                zoom: {
                    pan: {
                        enabled: true,
                        mode: 'x',
                        threshold: 2,
                        onPan: function ({ chart }) {
                            chart.canvas.dispatchEvent(new CustomEvent('chartInteract'));
                        }
                    },
                    zoom: {
                        wheel: {
                            enabled: true,
                            speed: 0.05
                        },
                        pinch: { enabled: true },
                        mode: 'x',
                        onZoom: function ({ chart }) {
                            chart.canvas.dispatchEvent(new CustomEvent('chartInteract'));
                        }
                    }
                }
            }
        },
        plugins: [
            wheelPreventPlugin,
            daySeparatorPlugin
        ]
    };

    if (['line', 'bar', 'scatter'].includes(type)) {
        config.options.scales = {
            x: {
                type: 'time',
                time: {
                    displayFormats: {
                        minute: 'HH:mm',
                        hour: 'HH:mm',
                        day: 'ddd DD MMM',
                        week: 'DD MMM',
                        month: 'MMM YYYY'
                    }
                },
                grid: { display: false, drawBorder: false },
                ticks: {
                    maxRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: 8,
                    color: 'var(--chart-tick-color)',
                    callback: function (value, index, ticks) {
                        const xScale = this;
                        const spanMs = xScale.max - xScale.min;
                        const d = new Date(value);
                        const now = new Date();
                        const time = d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

                        if (spanMs > 24 * 3600 * 1000) {
                            const todayStr = now.toDateString();
                            const yesterdayStr = new Date(now - 86400000).toDateString();
                            if (d.toDateString() === todayStr) return `Auj. ${time}`;
                            if (d.toDateString() === yesterdayStr) return `Hier ${time}`;
                            return d.toLocaleDateString('fr-FR', { weekday: 'short', day: '2-digit', month: '2-digit' }) + ' ' + time;
                        }
                        return time;
                    }
                }
            },
            y: {
                ticks: {
                    font: { size: 14 },
                    color: 'var(--chart-tick-color)'
                },
                grid: {
                    color: 'var(--chart-grid-color)',
                    drawBorder: false
                }
            }
        };
    }

    return config;
}

/**
 * Custom Plugin: Dynamically calculates and draws threshold zones (critical, normal) in the chart background.
 * Automatically handles standard topologies and inverted scales (e.g., Glasgow, PAO2/FIO2) as well as asymmetric limits.
 * 
 * @param {Object} config - Chart.js configuration of the target chart.
 * @param {Array} dataArr - Temporal data of the chart.
 * @param {Object} thresholds - Medical limits defining colors {nmin, nmax, cmin, cmax}.
 * @param {Object} view - Forced y-scale (optional) in the format {min, max}.
 * @returns {void}
 */
function applyThresholdBands(
    config,
    dataArr,
    thresholds = {},
    view = {}
) {
    const cartesian = ['line', 'bar', 'scatter'].includes(config.type);
    if (!cartesian) return;

    config.options.scales.y = {
        ...config.options.scales.y,
        min: view.min ?? undefined,
        max: view.max ?? undefined,
        grace: 0
    };

    const parseThresh = (val) => (val !== null && val !== undefined && val !== '') ? Number(val) : NaN;

    const nmin = parseThresh(thresholds.nmin);
    const nmax = parseThresh(thresholds.nmax);
    const cmin = parseThresh(thresholds.cmin);
    const cmax = parseThresh(thresholds.cmax);

    if (![nmin, nmax, cmin, cmax].some(Number.isFinite)) return;

    config.plugins = config.plugins || [];
    config.plugins.push({
        id: 'thresholdBands',
        beforeDraw: (chart) => {
            const ctx = chart.ctx;
            const yScale = chart.scales.y;
            const chartArea = chart.chartArea;

            const bounds = {
                min: yScale.min,
                max: yScale.max
            };

            const bands = [];

            if (Number.isFinite(cmax) && Number.isFinite(nmin) && cmax <= nmin) {
                bands.push({ top: cmax, bottom: bounds.min, color: 'var(--chart-band-red)' });
                bands.push({ top: nmin, bottom: cmax, color: 'var(--chart-band-yellow)' });
                const greenTop = Number.isFinite(nmax) ? nmax : bounds.max;
                if (greenTop > nmin) bands.push({ top: greenTop, bottom: nmin, color: 'var(--chart-band-green)' });
                if (Number.isFinite(nmax) && bounds.max > nmax) {
                    bands.push({ top: bounds.max, bottom: nmax, color: 'var(--chart-band-yellow)' });
                }
            } else if (Number.isFinite(nmax) && Number.isFinite(cmin) && nmax <= cmin) {
                const greenBottom = Number.isFinite(nmin) ? nmin : bounds.min;
                if (Number.isFinite(nmin) && greenBottom > bounds.min) {
                    bands.push({ top: greenBottom, bottom: bounds.min, color: 'var(--chart-band-yellow)' });
                }
                if (nmax > greenBottom) bands.push({ top: nmax, bottom: greenBottom, color: 'var(--chart-band-green)' });
                bands.push({ top: cmin, bottom: nmax, color: 'var(--chart-band-yellow)' });
                bands.push({ top: bounds.max, bottom: cmin, color: 'var(--chart-band-red)' });
            } else {
                if (Number.isFinite(cmin)) {
                    bands.push({ top: cmin, bottom: bounds.min, color: 'var(--chart-band-red)' });
                }
                let greenBottom = bounds.min;
                if (Number.isFinite(nmin)) {
                    let bottomEdge = Number.isFinite(cmin) ? cmin : bounds.min;
                    greenBottom = nmin;
                    if (nmin > bottomEdge) bands.push({ top: nmin, bottom: bottomEdge, color: 'var(--chart-band-yellow)' });
                }

                let greenTop = bounds.max;
                if (Number.isFinite(nmax)) {
                    let topEdge = Number.isFinite(cmax) ? cmax : bounds.max;
                    greenTop = nmax;
                    if (topEdge > nmax) bands.push({ top: topEdge, bottom: nmax, color: 'var(--chart-band-yellow)' });
                }

                if (greenTop > greenBottom) {
                    bands.push({ top: greenTop, bottom: greenBottom, color: 'var(--chart-band-green)' });
                }

                if (Number.isFinite(cmax)) {
                    bands.push({ top: bounds.max, bottom: cmax, color: 'var(--chart-band-red)' });
                }
            }

            const style = getComputedStyle(document.body || document.documentElement);
            const getCssColor = (colorVar) => {
                if (colorVar.startsWith('var(')) {
                    const varName = colorVar.match(/var\((--[^)]+)\)/)?.[1];
                    return varName ? style.getPropertyValue(varName).trim() : colorVar;
                }
                return colorVar;
            };

            bands.forEach(band => {
                if (band.bottom >= bounds.max || band.top <= bounds.min) return;

                const topVal = Math.min(band.top, bounds.max);
                const bottomVal = Math.max(band.bottom, bounds.min);

                const yTop = yScale.getPixelForValue(topVal);
                const yBottom = yScale.getPixelForValue(bottomVal);

                if (yBottom > yTop || isNaN(yTop) || isNaN(yBottom)) {
                    ctx.save();
                    ctx.beginPath();
                    ctx.rect(chartArea.left, chartArea.top, chartArea.right - chartArea.left, chartArea.bottom - chartArea.top);
                    ctx.clip();

                    ctx.fillStyle = getCssColor(band.color);
                    ctx.fillRect(chartArea.left, yTop, chartArea.right - chartArea.left, yBottom - yTop);
                    ctx.restore();
                }
            });
        }
    });
}


const getCssVar = (name) => {
    let val = getComputedStyle(document.body || document.documentElement).getPropertyValue(name).trim();
    if (!val) return name;
    let depth = 0;
    while (val.startsWith('var(') && depth < 5) {
        const match = val.match(/var\((--[^)]+)\)/);
        if (match) {
            val = getComputedStyle(document.body || document.documentElement).getPropertyValue(match[1]).trim();
        } else {
            break;
        }
        depth++;
    }
    return val;
};

const resolveColor = (color) => {
    if (Array.isArray(color)) return color.map(c => resolveColor(c));
    if (typeof color === 'string' && color.startsWith('var(')) {
        const match = color.match(/var\((--[^)]+)\)/);
        return match ? getCssVar(match[1]) : color;
    }
    return color;
};

function applyThemeColors(chart, style = null) {
    if (!chart) return;
    if (!style) style = getComputedStyle(document.body || document.documentElement);

    const gridColor = style.getPropertyValue('--chart-grid-color').trim();
    const tickColor = style.getPropertyValue('--chart-tick-color').trim();
    const tooltipBg = style.getPropertyValue('--chart-tooltip-bg').trim();
    const tooltipText = style.getPropertyValue('--chart-tooltip-text').trim();
    const tooltipBorder = style.getPropertyValue('--chart-tooltip-border').trim();

    if (chart.options.scales) {
        ['x', 'y'].forEach(axis => {
            if (chart.options.scales[axis]) {
                if (chart.options.scales[axis].grid) {
                    chart.options.scales[axis].grid.color = gridColor;
                    chart.options.scales[axis].grid.borderColor = gridColor;
                }
                if (chart.options.scales[axis].ticks) {
                    chart.options.scales[axis].ticks.color = tickColor;
                    chart.options.scales[axis].ticks.textStrokeColor = tickColor;
                }
            }
        });
    }

    if (chart.options.plugins && chart.options.plugins.tooltip) {
        chart.options.plugins.tooltip.backgroundColor = tooltipBg;
        chart.options.plugins.tooltip.titleColor = tooltipText;
        chart.options.plugins.tooltip.bodyColor = tooltipText;
        chart.options.plugins.tooltip.borderColor = tooltipBorder;
    }

    if (chart.options.plugins && chart.options.plugins.legend && chart.options.plugins.legend.labels) {
        chart.options.plugins.legend.labels.color = tickColor;
    }

    if (chart.data && chart.data.datasets) {
        chart.data.datasets.forEach(ds => {
            if (ds._origBorderColor) {
                ds.borderColor = typeof ds._origBorderColor === 'function' ? ds._origBorderColor : resolveColor(ds._origBorderColor);
            }
            if (ds._origBackgroundColor) {
                ds.backgroundColor = typeof ds._origBackgroundColor === 'function' ? ds._origBackgroundColor : resolveColor(ds._origBackgroundColor);
            }
        });
    }
}

function renderChart(
    target,
    config
) {
    const el = document.getElementById(target);
    if (!el) { console.error('Canvas introuvable:', target); return null; }
    if (el.chartInstance) el.chartInstance.destroy();

    el.chartInstance = new Chart(el, config);

    applyThemeColors(el.chartInstance);
    el.chartInstance.update('none');

    return el.chartInstance;
}

(function () {
    if (window._chartThemeObserver) return;

    const updateCharts = () => {
        requestAnimationFrame(() => {
            const style = getComputedStyle(document.body || document.documentElement);
            document.querySelectorAll('canvas').forEach(canvas => {
                const chart = canvas.chartInstance;
                if (!chart) return;
                applyThemeColors(chart, style);
                chart.update();
            });
        });
    };

    window._chartThemeObserver = new MutationObserver(updateCharts);
    window._chartThemeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'class'] });
    window._chartThemeObserver.observe(document.body, { attributes: true, attributeFilter: ['data-theme', 'class'] });
})();

function buildLine(
    {
        type,
        title,
        labels,
        data,
        color
    }) {

    const bgFunction = function (context) {
        if (type === 'bar') return resolveColor(color);
        const chart = context.chart;
        const { ctx, chartArea } = chart;
        if (!chartArea) return resolveColor(color);
        try {
            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            const rawCol = resolveColor(color);
            if (rawCol.startsWith('#') && rawCol.length === 7) {
                gradient.addColorStop(0, rawCol + '66');
                gradient.addColorStop(1, rawCol + '00');
            } else if (rawCol.startsWith('rgb')) {
                gradient.addColorStop(0, rawCol.replace(')', ', 0.4)').replace('rgb', 'rgba'));
                gradient.addColorStop(1, rawCol.replace(')', ', 0)').replace('rgb', 'rgba'));
            } else {
                gradient.addColorStop(0, rawCol);
                gradient.addColorStop(1, 'rgba(0,0,0,0)');
            }
            return gradient;
        } catch (e) {
            return resolveColor(color);
        }
    };

    return [{
        label: title,
        data,
        borderColor: resolveColor(color),
        backgroundColor: bgFunction,
        _origBorderColor: color,
        _origBackgroundColor: bgFunction,
        borderWidth: 1.5,
        tension: 0,
        fill: false,
        spanGaps: 5 * 60 * 1000,
        pointRadius: 0,
        pointHoverRadius: 6,
        pointBackgroundColor: resolveColor(color),
        borderRadius: 4,
        barPercentage: 0.8,
        categoryPercentage: 0.9
    }];
}

function buildPie(
    {
        title,
        labels,
        data,
        colors,
        mode,
        max
    }
) {
    if (mode === 'singlePercent') {
        const v = Number(data?.[0]);
        const m = Number(max);

        const safeMax = Number.isFinite(m) && m > 0 ? m : 100;
        const val = Number.isFinite(v) ? Math.max(0, Math.min(v, safeMax)) : 0;

        const usedLabels = (labels?.length ? labels : ['Mesure', 'Reste']);
        const palette = (colors?.length ? colors : ['#', '#334155']);

        const resolvedPalette = resolveColor(palette);

        return [{
            label: title,
            data: [val, Math.max(0, safeMax - val)],
            backgroundColor: resolvedPalette,
            _origBackgroundColor: palette,
            borderWidth: 0
        }];
    }

    const palette = colors?.length ? colors : labels.map((_, i) => `hsl(${(i * 360) / labels.length} 80% 55%)`);
    const resolvedPalette = resolveColor(palette);

    return [{
        label: title,
        data,
        backgroundColor: resolvedPalette,
        _origBackgroundColor: palette,
        borderWidth: 0
    }];
}

function updatePanelPieChart(panelId, chartId, title) {
    updatePanelChart(panelId, chartId, title);
}

/**
 * Asynchronous function to update the data and render the detailed chart in the modal.
 * Extracts HTML attributes and dataset variables to build the Chart.js configuration (lines, bars, pies).
 * Performs a strict parsing of data-x attributes to avoid asymmetric thresholds (empty string to 0).
 * 
 * @param {string} panelId - The textual HTML ID of the global chart panel container.
 * @param {string} chartId - The ID of the target HTML canvas used for rendering.
 * @param {string} title - The title of the displayed medical indicator.
 * @returns {Promise<void>}
 */
async function updatePanelChart(panelId, chartId, title) {
    const root = document.getElementById('modalDetails');
    if (!root) return;

    const panel = root.querySelector('#' + panelId);
    if (!panel) return;

    const list = panel.querySelectorAll('ul[data-hist]>li');
    const noDataPlaceholder = panel.querySelector('.modal-no-data-placeholder');
    const canvas = document.getElementById(chartId);
    const valueContainer = panel.querySelector('.modal-value-only');

    const chartType = panel.dataset.chart || 'line';
    const idx = parseInt(panel.getAttribute('data-idx') || '0', 10);

    const unit = (panel.dataset.unit || '').trim().toLowerCase();

    if (chartType === 'value') {
        const valueRaw = panel.dataset.value || '—';
        const unitRaw = panel.dataset.unitRaw || '';

        const valueText = panel.querySelector('.modal-value-text');
        const unitText = panel.querySelector('.modal-unit-text');

        if (valueContainer && valueText && unitText) {
            valueText.textContent = valueRaw;
            unitText.textContent = unitRaw;
            valueContainer.style.display = 'block';
        }

        if (canvas) canvas.style.display = 'none';
        if (noDataPlaceholder) noDataPlaceholder.style.display = 'none';
        return;
    }

    if (valueContainer) valueContainer.style.display = 'none';

    const parseDatasetNumber = (val) => (val !== undefined && val !== null && val !== '') ? Number(val) : NaN;
    const nmin = parseDatasetNumber(panel.dataset.nmin);
    const nmax = parseDatasetNumber(panel.dataset.nmax);
    const cmin = parseDatasetNumber(panel.dataset.cmin);
    const cmax = parseDatasetNumber(panel.dataset.cmax);
    const dmin = parseDatasetNumber(panel.dataset.dmin);
    const dmax = parseDatasetNumber(panel.dataset.dmax);

    const thresholds = {
        nmin: Number.isFinite(nmin) ? nmin : null,
        nmax: Number.isFinite(nmax) ? nmax : null,
        cmin: Number.isFinite(cmin) ? cmin : null,
        cmax: Number.isFinite(cmax) ? cmax : null
    };

    const view = {
        min: Number.isFinite(dmin) ? dmin : null,
        max: Number.isFinite(dmax) ? dmax : null
    };

    if (chartType === 'pie' || chartType === 'doughnut') {
        const item = list[idx];
        const val = Number(item?.dataset?.value);

        let max = 100;
        if (unit.includes('%')) {
            max = 100;
        } else if (Number.isFinite(dmax)) {
            max = dmax;
        } else if (Number.isFinite(nmax)) {
            max = nmax;
        } else if (Number.isFinite(cmax)) {
            max = cmax;
        }

        createChart(
            chartType,
            title,
            [],
            [val],
            chartId,
            'var(--chart-color)',
            {},
            {},
            {
                mode: 'singlePercent',
                index: 0,
                max: max,
                labels: ['Mesure', 'Reste'],
                colors: ['var(--chart-color)', 'var(--chart-grid-color, #334155)']
            }
        );
    } else {
        const targetDate = panel.dataset.targetDate || '';
        const now = new Date();
        const today = now.toDateString();

        const slugMatch = panelId.match(/panel-(.+)$/);
        const slug = slugMatch ? slugMatch[1] : '';
        const paramId = panel.dataset.paramId || slug;

        let spinner = panel.querySelector('.chart-loading-spinner');
        if (!spinner) {
            spinner = document.createElement('div');
            spinner.className = 'chart-loading-spinner';
            spinner.innerHTML = '<div style="width: 30px; height: 30px; border: 3px solid var(--border-color); border-top-color: var(--chart-color); border-radius: 50%; animation: spin 1s linear infinite;"></div><style>@keyframes spin { to { transform: rotate(360deg); } }</style>';
            spinner.style.position = 'absolute';
            spinner.style.top = '50%';
            spinner.style.left = '50%';
            spinner.style.transform = 'translate(-50%, -50%)';
            spinner.style.zIndex = '100';
            panel.appendChild(spinner);
            panel.style.position = 'relative';
        }
        spinner.style.display = 'block';
        if (canvas) canvas.style.opacity = '0.5';

        try {
            const fetchLimit = 2000;
            const dateParam = targetDate ? `&date=${encodeURIComponent(targetDate)}` : '';
            const res = await fetch(`/api_history?param=${encodeURIComponent(paramId)}&limit=${fetchLimit}${dateParam}`);
            if (!res.ok) throw new Error('Fetch failed');
            const dataArr = await res.json();
            if (dataArr.error) throw new Error(dataArr.error);

            if (dataArr.length === 0) {
                if (canvas) canvas.style.display = 'none';
                if (noDataPlaceholder) noDataPlaceholder.style.display = 'flex';
                spinner.style.display = 'none';
                return;
            } else {
                if (canvas) canvas.style.display = 'block';
                if (noDataPlaceholder) noDataPlaceholder.style.display = 'none';
            }

            const rawData = [];
            dataArr.forEach((item) => {
                const timeStr = item.time_iso;
                const val = Number(item.value);
                if (timeStr) {
                    const d = new Date(timeStr);
                    if (!isNaN(d.getTime())) {
                        rawData.push({ time: d, value: val });
                    }
                }
            });

            const generated = generateChartData(rawData);

            if (canvas) canvas.style.opacity = '1';
            spinner.style.display = 'none';

            createChart(
                chartType,
                title,
                generated.labels,
                generated.data,
                chartId,
                'var(--chart-color)',
                thresholds,
                view,
                {
                    initialZoomMs: 2 * 60 * 1000
                }
            );

            setupRealtimeSyncButton(panel, chartId, title);

        } catch (err) {
            console.error('Error fetching chart data:', err);
            if (canvas) canvas.style.opacity = '1';
            if (spinner) spinner.style.display = 'none';
        }
    }
}

function setupRealtimeSyncButton(panel, chartId, title) {
    let syncBtn = panel.querySelector('.sync-realtime-btn');
    if (!syncBtn) {
        syncBtn = document.createElement('button');
        syncBtn.className = 'sync-realtime-btn btn-primary';
        syncBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>';
        syncBtn.title = "Retourner aux données récentes";
        syncBtn.style.position = 'absolute';
        syncBtn.style.top = '10px';
        syncBtn.style.right = '10px';
        syncBtn.style.zIndex = '10';
        syncBtn.style.display = 'none';
        syncBtn.style.padding = '8px';
        syncBtn.style.borderRadius = '50%';
        syncBtn.style.border = 'none';
        syncBtn.style.background = 'var(--primary-color, var(--chart-color, #275afe))';
        syncBtn.style.color = '#fff';
        syncBtn.style.cursor = 'pointer';
        syncBtn.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        syncBtn.style.display = 'flex';
        syncBtn.style.alignItems = 'center';
        syncBtn.style.justifyContent = 'center';

        syncBtn.onclick = () => {
            const canvas = document.getElementById(chartId);
            if (canvas && canvas.chartInstance) {
                const chart = canvas.chartInstance;
                chart.resetZoom();
                chart.update();

                syncBtn.style.display = 'none';
            }
        };

        const canvas = document.getElementById(chartId);
        if (canvas) {
            canvas.parentElement.appendChild(syncBtn);
            canvas.addEventListener('chartInteract', () => {
                syncBtn.style.display = 'block';
            });
        }
    } else {
        syncBtn.style.display = 'none';
    }
}

function buildScatter({
    title,
    labels,
    data,
    color
}) {
    return [{
        label: title,
        data,
        borderColor: resolveColor(color),
        backgroundColor: resolveColor(color),
        _origBorderColor: color,
        _origBackgroundColor: color,
        pointRadius: 6,
        showLine: false
    }];
}

function updatePanelPieChart(panelId, chartId, title) {
    updatePanelChart(panelId, chartId, title);
}


function createChart(
    type,
    title = "Titre",
    labels = [],
    data = [],
    target,
    color = "#275afe",
    thresholds = {},
    view = {},
    extra = {}
) {
    if (type === "pie") type = "doughnut";

    const config = makeBaseConfig({ type, title, view });

    const isPie = (type === "pie" || type === "doughnut");
    const singleMode = isPie && extra?.mode === "singlePercent";

    const isTimeSeries = Array.isArray(data) && data.length > 0 && typeof data[0] === 'object' && data[0] !== null && 'x' in data[0];

    if (!isTimeSeries && !isPie) {
        delete config.options.parsing;
        delete config.options.normalized;
        if (config.options.plugins?.decimation) delete config.options.plugins.decimation;
        config.data.labels = labels;
        if (config.options.scales?.x) {
            delete config.options.scales.x.type;
            delete config.options.scales.x.time;
        }
    }

    if (!isPie) {

        if (type === 'scatter') {
            config.type = 'line';
            config.data.datasets.push(
                ...buildScatter({
                    title,
                    labels: config.data.labels,
                    data: data,
                    color
                })
            );
        } else {
            config.data.datasets.push(
                ...buildLine({
                    type,
                    title,
                    labels: config.data.labels,
                    data: data,
                    color
                })
            );
        }

        applyThresholdBands(config, data, thresholds, view);
    } else if (singleMode) {
        const idx = Number.isFinite(extra?.index) ? extra.index : 0;
        const raw = Number((data ?? [])[idx]);

        const m = Number(extra?.max);
        const safeMax = Number.isFinite(m) && m > 0 ? m : 100;
        const val = Number.isFinite(raw) ? Math.max(0, Math.min(raw, safeMax)) : 0;

        const pieLabels = extra?.labels?.length ? extra.labels : ["Mesure", "Reste"];
        const pieColors = extra?.colors?.length ? extra.colors : ["#22c55e", "#334155"];

        config.data.labels = pieLabels;

        config.data.datasets.push(
            ...buildPie({
                title,
                labels: pieLabels,
                data: [val],
                colors: pieColors,
                mode: "singlePercent",
                max: safeMax
            })
        );

        config.options.plugins.tooltip = {
            callbacks: {
                label: (ctx) => {
                    const ds = ctx.dataset?.data ?? [];
                    const sum = ds.reduce((a, b) => a + Number(b || 0), 0);
                    const v = Number(ctx.raw || 0);
                    const pct = sum > 0 ? (v / sum) * 100 : 0;
                    return `${ctx.label}: ${v} (${pct.toFixed(1)}%)`;
                }
            }
        };
    } else {
        const v = rev(data);
        const pieLabels = config.data.labels;
        const pieColors = extra?.colors?.length
            ? extra.colors
            : pieLabels.map((_, i) => `hsl(${(i * 360) / Math.max(1, pieLabels.length)} 80% 55%)`);

        config.data.datasets.push(
            ...buildPie({
                title,
                labels: pieLabels,
                data: v,
                colors: pieColors
            })
        );
    }

    if (extra?.options) {
        const { scales, ...otherOpts } = extra.options;
        config.options = { ...config.options, ...otherOpts };

        if (scales) {
            config.options.scales = config.options.scales || {};
            if (scales.x) {
                config.options.scales.x = { ...(config.options.scales.x || {}), ...scales.x };
            }
            if (scales.y) {
                config.options.scales.y = { ...(config.options.scales.y || {}), ...scales.y };
            }
        }
    }

    if (isTimeSeries && extra?.initialZoomMs && data.length > 1 && config.options.scales?.x) {
        const lastTimestamp = data[data.length - 1].x;
        const zoomStart = lastTimestamp - extra.initialZoomMs;
        config.options.scales.x.min = zoomStart;
        config.options.scales.x.max = lastTimestamp;
    }

    return renderChart(target, config);
}

document.addEventListener('change', function (e) {
    const datePicker = e.target.closest('.modal-date-picker');
    if (!datePicker) return;

    const panel = datePicker.closest('.modal-grid');
    if (!panel) return;

    const targetDate = datePicker.value || '';
    panel.dataset.targetDate = targetDate;

    const detailPrefix = panel.id.replace('panel-', 'detail-');
    const sourceDetail = document.getElementById(detailPrefix);
    if (sourceDetail) {
        const sourcePanel = sourceDetail.querySelector('.modal-grid');
        if (sourcePanel) {
            sourcePanel.setAttribute('data-target-date', targetDate);
            const sourcePicker = sourcePanel.querySelector('.modal-date-picker');
            if (sourcePicker) {
                sourcePicker.value = targetDate;
            }
        }
    }

    const chartId = panel.querySelector('canvas.modal-chart')?.dataset.id;
    const display = panel.dataset.display || '';

    if (chartId) {
        updatePanelChart(panel.id, chartId, display);
    }
});

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.chart-type-btn');
    if (!btn) return;

    const form = btn.closest('.chart-type-form');
    if (!form) return;

    e.preventDefault();

    form.querySelectorAll('.chart-type-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const panel = btn.closest('.modal-grid');
    if (panel) {
        panel.dataset.chart = btn.value;
        const chartId = panel.querySelector('canvas.modal-chart')?.dataset.id;
        const display = panel.dataset.display || '';
        if (chartId) {
            updatePanelChart(panel.id, chartId, display);
        }

        const slugMatch = panel.id.match(/panel-(.+)$/);
        const slug = slugMatch ? slugMatch[1] : (panel.dataset.slug || '');
        if (slug) {
            document.dispatchEvent(new CustomEvent('updateSparkline', { detail: { slug: slug, type: btn.value } }));

            const detailPrefix = panel.id.replace('panel-', 'detail-');
            const sourceDetail = document.getElementById(detailPrefix);
            if (sourceDetail) {
                const sourcePanel = sourceDetail.querySelector('.modal-grid');
                if (sourcePanel) {
                    sourcePanel.setAttribute('data-chart', btn.value);
                    const sourceForm = sourcePanel.querySelector('.chart-type-form');
                    if (sourceForm) {
                        sourceForm.querySelectorAll('.chart-type-btn').forEach(b => b.classList.remove('active'));
                        const newActive = sourceForm.querySelector(`.chart-type-btn[value="${btn.value}"]`);
                        if (newActive) newActive.classList.add('active');
                    }
                }
            }

            const cards = document.querySelectorAll(`article.card[data-slug="${slug}"]`);
            cards.forEach(c => {
                c.dataset.chartType = btn.value;
                const configStr = c.getAttribute('data-chart');
                if (configStr) {
                    try {
                        const config = JSON.parse(configStr);
                        config.type = btn.value;
                        c.setAttribute('data-chart', JSON.stringify(config));
                    } catch (e) { }
                }
            });
        }
    }

    const formData = new FormData(form);
    formData.set('chart_type', btn.value);
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).catch(console.error);
});

(function () {
    setInterval(async function () {
        const modal = document.querySelector(".modal");
        if (!modal || !modal.classList.contains("show-modal")) return;

        const canvas = modal.querySelector("canvas.modal-chart");
        if (!canvas || !canvas.chartInstance) return;

        const panel = canvas.closest('.modal-grid');
        if (!panel) return;

        if (panel.dataset.targetDate) return;

        const slugMatch = panel.id.match(/panel-(.+)$/);
        const slug = slugMatch ? slugMatch[1] : (panel.dataset.slug || '');
        if (!slug) return;

        try {
            const res = await fetch('/api_live_metrics');
            if (!res.ok) return;
            const metrics = await res.json();
            if (metrics.error) return;

            const metric = metrics.find(m => m.slug === slug);
            if (!metric || !metric.value || typeof metric.time_iso === 'undefined') return;

            const time = new Date(metric.time_iso).getTime();
            const val = Number(metric.value);

            const chart = canvas.chartInstance;

            if (metric.chart_type === 'pie' || metric.chart_type === 'doughnut') {
                const max = parseFloat(panel.dataset.max || panel.dataset.nmax || panel.dataset.dmax) || 100;
                chart.data.datasets[0].data = [val, Math.max(0, max - val)];
                chart.update('none');
            } else {
                const ds = chart.data.datasets[0];
                if (!ds || !ds.data || !ds.data.length || isNaN(time)) return;

                const lastPoint = ds.data[ds.data.length - 1];
                if (lastPoint && time > lastPoint.x) {
                    ds.data.push({ x: time, y: val });
                    chart.update('none');
                }
            }

            const valueText = panel.querySelector('.modal-value-text');
            if (valueText) valueText.textContent = metric.value;

        } catch (e) {
            console.error('Modal live metrics fetch error:', e);
        }
    }, 1000);
})();