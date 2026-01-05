const rev = (arr) => [...(arr ?? [])].reverse();
const finiteVals = (arr) => (arr ?? []).map(Number).filter(Number.isFinite);

function makeBaseConfig({ type, title, labels, view }) {
    return {
        type,
        data: { labels, datasets: [] },
        options: {
            responsive: true,
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
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        filter: (item) => !(item.text || '').startsWith('_band_')
                    }
                },
                title: { display: false, text: title }
            }
        }
    };
}

function applyThresholdBands(
    config,
    dataArr,
    thresholds = {},
    view = {}
) {
    const cartesian = ['line', 'bar', 'scatter'].includes(config.type);
    if (!cartesian) return;

    config.options.scales = {
        y: {
            min: view.min ?? undefined,
            max: view.max ?? undefined,
            grace: 0
        }
    };

    if (config.type === 'bar') return;

    const labelsLen = config.data.labels.length;

    const addBand = (yTop, yBottom, bg) => {
        const t = Number(yTop), b = Number(yBottom);
        if (!Number.isFinite(t) || !Number.isFinite(b)) return;

        config.data.datasets.push(
            {
                type: 'line',
                label: '_band_top_',
                data: Array(labelsLen).fill(t),
                borderWidth: 0,
                pointRadius: 0,
                pointHoverRadius: 0,
                hoverRadius: 0,
                hitRadius: 0,
                fill: false,
                tension: 0,
                order: 100
            },
            {
                type: 'line',
                label: '_band_fill_',
                data: Array(labelsLen).fill(b),
                borderWidth: 0,
                pointRadius: 0,
                pointHoverRadius: 0,
                hoverRadius: 0,
                hitRadius: 0,
                backgroundColor: bg,
                fill: '-1',
                tension: 0,
                order: 100
            }
        );
    };

    const nmin = Number(thresholds.nmin);
    const nmax = Number(thresholds.nmax);
    const cmin = Number(thresholds.cmin);
    const cmax = Number(thresholds.cmax);

    const vals = finiteVals(dataArr);
    if (!vals.length && ![nmin, nmax, cmin, cmax].some(Number.isFinite)) return;

    let yMin = vals.length ? Math.min(...vals) : 0;
    let yMax = vals.length ? Math.max(...vals) : 1;

    [nmin, cmin].forEach(v => Number.isFinite(v) && (yMin = Math.min(yMin, v)));
    [nmax, cmax].forEach(v => Number.isFinite(v) && (yMax = Math.max(yMax, v)));

    if (Number.isFinite(cmin)) addBand(cmin, view.min ?? yMin, 'rgba(239,68,68,0.15)');
    if (Number.isFinite(cmin) && Number.isFinite(nmin) && cmin < nmin) addBand(nmin, cmin, 'rgba(234,179,8,0.15)');
    if (Number.isFinite(nmin) && Number.isFinite(nmax) && nmin < nmax) addBand(nmax, nmin, 'rgba(34,197,94,0.15)');
    if (Number.isFinite(nmax) && Number.isFinite(cmax) && nmax < cmax) addBand(cmax, nmax, 'rgba(234,179,8,0.15)');
    if (Number.isFinite(cmax)) addBand(view.max ?? yMax, cmax, 'rgba(239,68,68,0.15)');
}

function renderChart(
    target,
    config
) {
    const varRegex = /var\((--[^)]+)\)/;
    const getCssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    const resolveCssVars = (obj, depth = 0) => {
        if (!obj || typeof obj !== 'object' || depth > 10) return;
        if (Array.isArray(obj)) {
            obj.forEach(item => resolveCssVars(item, depth + 1));
            return;
        }
        for (const key in obj) {
            const val = obj[key];
            if (typeof val === 'string' && val.startsWith('var(--')) {
                const match = val.match(varRegex);
                if (match) obj[key] = getCssVar(match[1]);
            } else if (typeof val === 'object') {
                if (key === 'data' && Array.isArray(val) && typeof val[0] === 'number') continue; // Skip numeric data
                resolveCssVars(val, depth + 1);
            }
        }
    };

    resolveCssVars(config);

    const el = document.getElementById(target);
    if (!el) { console.error('Canvas introuvable:', target); return null; }
    if (el.chartInstance) el.chartInstance.destroy();
    el.chartInstance = new Chart(el, config);
    return el.chartInstance;
}

(function () {
    if (window._chartThemeObserver) return;

    const updateCharts = () => {
        requestAnimationFrame(() => {
            const style = getComputedStyle(document.documentElement);
            const gridColor = style.getPropertyValue('--chart-grid-color').trim();
            const tickColor = style.getPropertyValue('--chart-tick-color').trim();
            const tooltipBg = style.getPropertyValue('--chart-tooltip-bg').trim();
            const tooltipText = style.getPropertyValue('--chart-tooltip-text').trim();
            const tooltipBorder = style.getPropertyValue('--chart-tooltip-border').trim();

            document.querySelectorAll('canvas').forEach(canvas => {
                const chart = canvas.chartInstance;
                if (!chart) return;

                if (chart.options.scales) {
                    ['x', 'y'].forEach(axis => {
                        if (chart.options.scales[axis]) {
                            if (chart.options.scales[axis].grid) {
                                chart.options.scales[axis].grid.color = gridColor;
                                chart.options.scales[axis].grid.borderColor = gridColor;
                            }
                            if (chart.options.scales[axis].ticks) {
                                chart.options.scales[axis].ticks.color = tickColor;
                                chart.options.scales[axis].ticks.textStrokeColor = tickColor; // just in case
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
        title,
        labels,
        data,
        color
    }) {
    return [{
        label: title,
        data,
        borderColor: color,
        backgroundColor: color,
        tension: 0.3,
        fill: false,
        pointRadius: 5,
        pointBackgroundColor: color
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
        const palette = (colors?.length ? colors : ['#4f46e5', '#334155']);

        return [{
            label: title,
            data: [val, Math.max(0, safeMax - val)],
            backgroundColor: palette,
            borderWidth: 0
        }];
    }

    const palette = colors?.length ? colors : labels.map((_, i) => `hsl(${(i * 360) / labels.length} 80% 55%)`);
    return [{
        label: title,
        data,
        backgroundColor: palette,
        borderWidth: 0
    }];
}

function updatePanelChart(panelId, chartId, title) {
    const root = document.getElementById('modalDetails');
    if (!root) return;

    const panel = root.querySelector('#' + panelId);
    if (!panel) return;

    const list = panel.querySelectorAll('ul[data-hist]>li');
    if (!list.length) return;

    const chartType = panel.dataset.chart || 'line';
    const idx = parseInt(panel.getAttribute('data-idx') || '0', 10);

    const unit = (panel.dataset.unit || '').trim().toLowerCase();

    if (chartType === 'value') {
        const valueRaw = panel.dataset.value || '—';
        const unitRaw = panel.dataset.unitRaw || '';

        const valueContainer = panel.querySelector('.modal-value-only');
        const valueText = panel.querySelector('.modal-value-text');
        const unitText = panel.querySelector('.modal-unit-text');

        if (valueContainer && valueText && unitText) {
            valueText.textContent = valueRaw;
            unitText.textContent = unitRaw;
            valueContainer.style.display = 'block';
        }

        const canvas = document.getElementById(chartId);
        if (canvas) canvas.style.display = 'none';
        return;
    }

    const canvas = document.getElementById(chartId);
    if (canvas) canvas.style.display = 'block';
    const valueContainer = panel.querySelector('.modal-value-only');
    if (valueContainer) valueContainer.style.display = 'none';

    const nmin = Number(panel.dataset.nmin);
    const nmax = Number(panel.dataset.nmax);
    const cmin = Number(panel.dataset.cmin);
    const cmax = Number(panel.dataset.cmax);
    const dmin = Number(panel.dataset.dmin);
    const dmax = Number(panel.dataset.dmax);

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
            '#4f46e5',
            {},
            {},
            {
                mode: 'singlePercent',
                index: 0,
                max: max,
                labels: ['Mesure', 'Reste'],
                colors: ['#4f46e5', '#334155']
            }
        );
    } else {
        const labels = [];
        const data = [];

        list.forEach((item) => {
            const time = item.dataset.time || '';
            const val = Number(item.dataset.value);
            if (time) {
                const d = new Date(time);
                if (!isNaN(d.getTime())) {
                    labels.push(d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }));
                    data.push(val);
                }
            }
        });

        createChart(
            chartType,
            title,
            labels,
            data,
            chartId,
            '#4f46e5',
            thresholds,
            view
        );
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
        borderColor: color,
        backgroundColor: color,
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
    const baseLabels = rev(labels);
    const config = makeBaseConfig({ type, title, labels: baseLabels, view });

    const isPie = (type === "pie" || type === "doughnut");
    const singleMode = isPie && extra?.mode === "singlePercent";

    if (!isPie) {
        const dataset = rev(data);

        if (type === 'scatter') {
            config.type = 'line';
            config.data.datasets.push(
                ...buildScatter({
                    title,
                    labels: config.data.labels,
                    data: dataset,
                    color
                })
            );
        } else {
            config.data.datasets.push(
                ...buildLine({
                    title,
                    labels: config.data.labels,
                    data: dataset,
                    color
                })
            );
        }

        applyThresholdBands(config, dataset, thresholds, view);
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

    return renderChart(target, config);
}