const finiteVals = (arr) => (arr ?? []).map(Number).filter(Number.isFinite);

function downsampleData(rawData, rangeMinutes) {
    if (!rawData || rawData.length === 0) return { labels: [], data: [] };

    rawData.sort((a, b) => a.time.getTime() - b.time.getTime());

    const MAX_POINTS = 120;

    if (rawData.length <= MAX_POINTS) {
        return {
            labels: rawData.map(d => d.label),
            data: rawData.map(d => d.value)
        };
    }

    let bucketMinutes;
    if (rangeMinutes >= 480) {
        bucketMinutes = 30;
    } else if (rangeMinutes >= 240) {
        bucketMinutes = 15;
    } else if (rangeMinutes >= 60) {
        bucketMinutes = 1;
    } else {
        return {
            labels: rawData.map(d => d.label),
            data: rawData.map(d => d.value)
        };
    }

    const buckets = new Map();

    rawData.forEach(({ time, value }) => {
        const bucketTime = Math.floor(time.getTime() / (bucketMinutes * 60000)) * (bucketMinutes * 60000);
        if (!buckets.has(bucketTime)) {
            buckets.set(bucketTime, { sum: 0, count: 0, time: new Date(bucketTime) });
        }
        const bucket = buckets.get(bucketTime);
        bucket.sum += value;
        bucket.count++;
    });

    const sortedBuckets = Array.from(buckets.entries())
        .sort((a, b) => a[0] - b[0])
        .map(([, bucket]) => bucket);

    const today = new Date().toDateString();
    const labels = [];
    const data = [];

    sortedBuckets.forEach(bucket => {
        const avg = bucket.sum / bucket.count;
        const d = bucket.time;
        const isToday = d.toDateString() === today;

        let label;
        if (bucketMinutes >= 60 * 24) {
            label = d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
        } else if (isToday) {
            label = d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        } else {
            label = d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }) + ' ' +
                d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        }

        labels.push(label);
        data.push(Math.round(avg * 100) / 100);
    });

    return { labels, data };
}

function makeBaseConfig({ type, title, labels, view }) {
    const config = {
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
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                },
                title: { display: false, text: title }
            }
        }
    };

    if (['line', 'bar', 'scatter'].includes(type)) {
        config.options.scales = {
            x: { ticks: { font: { size: 14 } } },
            y: { ticks: { font: { size: 14 } } }
        };
    }

    return config;
}

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

    const bands = [];
    if (Number.isFinite(cmin)) bands.push({ top: cmin, bottom: view.min ?? yMin, color: 'var(--chart-band-red)' });
    if (Number.isFinite(cmin) && Number.isFinite(nmin) && cmin < nmin) bands.push({ top: nmin, bottom: cmin, color: 'var(--chart-band-yellow)' });
    if (Number.isFinite(nmin) && Number.isFinite(nmax) && nmin < nmax) bands.push({ top: nmax, bottom: nmin, color: 'var(--chart-band-green)' });
    if (Number.isFinite(nmax) && Number.isFinite(cmax) && nmax < cmax) bands.push({ top: cmax, bottom: nmax, color: 'var(--chart-band-yellow)' });
    if (Number.isFinite(cmax)) bands.push({ top: view.max ?? yMax, bottom: cmax, color: 'var(--chart-band-red)' });

    if (!bands.length) return;

    config.plugins = config.plugins || [];
    config.plugins.push({
        id: 'thresholdBands',
        beforeDraw: (chart) => {
            const ctx = chart.ctx;
            const yScale = chart.scales.y;
            const chartArea = chart.chartArea;

            const style = getComputedStyle(document.documentElement);
            const getCssColor = (colorVar) => {
                if (colorVar.startsWith('var(')) {
                    const varName = colorVar.match(/var\((--[^)]+)\)/)?.[1];
                    return varName ? style.getPropertyValue(varName).trim() : colorVar;
                }
                return colorVar;
            };

            bands.forEach(band => {
                const yTop = yScale.getPixelForValue(band.top);
                const yBottom = yScale.getPixelForValue(band.bottom);

                ctx.save();
                ctx.fillStyle = getCssColor(band.color);
                ctx.fillRect(chartArea.left, yTop, chartArea.right - chartArea.left, yBottom - yTop);
                ctx.restore();
            });
        }
    });
}


function applyThemeColors(chart, style = null) {
    if (!chart) return;
    if (!style) style = getComputedStyle(document.documentElement);

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
                if (key === 'data' && Array.isArray(val) && typeof val[0] === 'number') continue;
                resolveCssVars(val, depth + 1);
            }
        }
    };

    resolveCssVars(config);

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
            const style = getComputedStyle(document.documentElement);
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
        pointRadius: 0,
        pointHoverRadius: 4,
        pointBackgroundColor: color,
        barPercentage: 0.6,
        categoryPercentage: 0.7
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
        const palette = (colors?.length ? colors : ['#60a5fa', '#334155']);

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
    const noDataPlaceholder = panel.querySelector('.modal-no-data-placeholder');
    const canvas = document.getElementById(chartId);
    const valueContainer = panel.querySelector('.modal-value-only');

    if (!list.length) {
        if (canvas) canvas.style.display = 'none';
        if (valueContainer) valueContainer.style.display = 'none';
        if (noDataPlaceholder) noDataPlaceholder.style.display = 'flex';
        return;
    }

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

    if (canvas) canvas.style.display = 'block';
    if (valueContainer) valueContainer.style.display = 'none';
    if (noDataPlaceholder) noDataPlaceholder.style.display = 'none';

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
            '#60a5fa',
            {},
            {},
            {
                mode: 'singlePercent',
                index: 0,
                max: max,
                labels: ['Mesure', 'Reste'],
                colors: ['#60a5fa', '#334155']
            }
        );
    } else {
        const rangeValue = panel.dataset.rangeMinutes || '15';
        const now = new Date();
        const today = now.toDateString();
        const useFilter = rangeValue !== 'all';
        const rangeMinutes = useFilter ? parseInt(rangeValue, 10) : 0;
        const cutoff = useFilter ? new Date(now.getTime() - rangeMinutes * 60 * 1000) : null;

        const rawData = [];
        list.forEach((item) => {
            const time = item.dataset.time || '';
            const val = Number(item.dataset.value);
            if (time) {
                const d = new Date(time);
                if (!isNaN(d.getTime()) && (!useFilter || d >= cutoff)) {
                    const isToday = d.toDateString() === today;
                    let label;
                    if (isToday) {
                        label = d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                    } else {
                        label = d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }) + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                    }
                    rawData.push({ time: d, value: val, label });
                }
            }
        });

        const { labels, data } = downsampleData(rawData, rangeMinutes);

        createChart(
            chartType,
            title,
            labels,
            data,
            chartId,
            '#60a5fa',
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
    const config = makeBaseConfig({ type, title, labels, view });

    const isPie = (type === "pie" || type === "doughnut");
    const singleMode = isPie && extra?.mode === "singlePercent";

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

    return renderChart(target, config);
}

document.addEventListener('change', function (e) {
    const select = e.target.closest('.modal-timerange-select');
    if (!select) return;

    const panel = select.closest('.modal-grid');
    if (!panel) return;

    const range = select.value || '15';
    panel.dataset.rangeMinutes = range;

    const chartId = panel.querySelector('canvas.modal-chart')?.dataset.id;
    const display = panel.dataset.display || '';

    if (chartId) {
        updatePanelChart(panel.id, chartId, display);
    }
});