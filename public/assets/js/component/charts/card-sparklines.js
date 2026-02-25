(function () {
    if (!window.Chart || typeof createChart !== "function") return;

    const cards = document.querySelectorAll("article.card");
    if (!cards.length) return;

    /**
     * Extracts historical data and thresholds from a dashboard card
     * and generates a miniaturized Sparkline chart preview via Chart.js.
     * 
     * @param {HTMLElement} card - The HTML <article> element of the indicator card.
     * @returns {void}
     */
    window.renderSparkline = function (card) {
        const slug = card.dataset.slug;
        if (!slug) return;

        const type = card.dataset.chartType || 'line';

        const valueOnlyContainer = card.querySelector('.card-value-only-container');
        const sparkContainer = card.querySelector('.card-spark');
        const headerValue = card.querySelector('.card-header .value');

        /**
         * CSS Layout Toggling:
         * To avoid DOM manipulation issues when dynamically changing chart types, 
         * both the text-only layout and the canvas layout are pre-rendered into the DOM.
         * We isolate their visibility here instead of destroying/creating elements.
         */
        if (type === 'value') {
            if (valueOnlyContainer) valueOnlyContainer.style.display = 'flex';
            if (sparkContainer) sparkContainer.style.display = 'none';
            if (headerValue) headerValue.style.display = 'none';
            return;
        } else {
            if (valueOnlyContainer) valueOnlyContainer.style.display = 'none';
            if (sparkContainer) sparkContainer.style.display = 'block';
            if (headerValue) headerValue.style.display = 'flex';
        }

        const dataList = card.querySelector("ul[data-spark]");
        const canvas = card.querySelector("canvas.card-spark-canvas");

        if (!canvas || !dataList) return;

        const canvasId = canvas.id;

        const items = dataList.querySelectorAll("li");
        const noDataPlaceholder = card.querySelector(".no-data-placeholder");

        if (!items.length) {
            if (canvas) canvas.style.display = 'none';
            if (noDataPlaceholder) noDataPlaceholder.style.display = 'flex';
            return;
        }

        const rawData = [];

        items.forEach((item) => {
            const time = item.dataset.time || "";
            const val = Number(item.dataset.value);

            if (!time || !Number.isFinite(val)) return;

            const d = new Date(time);
            if (isNaN(d.getTime())) return;

            rawData.push({ time: d, value: val });
        });

        if (!rawData.length) {
            if (canvas) canvas.style.display = 'none';
            if (noDataPlaceholder) noDataPlaceholder.style.display = 'flex';
            return;
        }

        rawData.sort((a, b) => a.time.getTime() - b.time.getTime());

        const data = rawData.map(d => ({ x: d.time.getTime(), y: d.value }));
        const labels = [];

        if (canvas) canvas.style.display = 'block';
        if (noDataPlaceholder) noDataPlaceholder.style.display = 'none';

        const title = card.dataset.display || "";

        const parseDatasetNumber = (val) => (val !== undefined && val !== null && val !== '') ? Number(val) : NaN;
        const nmin = parseDatasetNumber(card.dataset.nmin);
        const nmax = parseDatasetNumber(card.dataset.nmax);
        const cmin = parseDatasetNumber(card.dataset.cmin);
        const cmax = parseDatasetNumber(card.dataset.cmax);
        const dmin = parseDatasetNumber(card.dataset.dmin);
        const dmax = parseDatasetNumber(card.dataset.dmax);

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

        const gridColor = 'var(--chart-grid-color)';
        const tickColor = 'var(--chart-tick-color)';

        const extra = {
            options: {
                maintainAspectRatio: false,
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'var(--chart-tooltip-bg)',
                        titleColor: 'var(--chart-tooltip-text)',
                        bodyColor: 'var(--chart-tooltip-text)',
                        borderColor: 'var(--chart-tooltip-border)',
                        borderWidth: 1,
                        filter: function (item) {
                            return !item.dataset.label || !item.dataset.label.startsWith('_');
                        },
                        callbacks: {
                            title: function (context) {
                                if (!context.length) return '';
                                const raw = context[0].parsed.x;
                                if (!raw) return context[0].label || '';
                                const d = new Date(raw);
                                return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                            }
                        }
                    },
                    title: { display: false }
                },
                scales: {
                    x: {
                        type: 'time',
                        display: true,
                        grid: { display: true, color: gridColor },
                        time: {
                            tooltipFormat: 'HH:mm:ss',
                            displayFormats: {
                                millisecond: 'HH:mm:ss',
                                second: 'HH:mm:ss',
                                minute: 'HH:mm:ss',
                                hour: 'HH:mm:ss'
                            }
                        },
                        ticks: {
                            display: true,
                            color: tickColor,
                            font: { size: 12, weight: '600' },
                            callback: function (value) {
                                const d = new Date(value);
                                return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                            }
                        }
                    },
                    y: {
                        display: true,
                        position: 'left',
                        grid: {
                            color: gridColor,
                            drawBorder: false,
                        },
                        ticks: {
                            color: tickColor,
                            font: { size: 15, weight: '600' },
                            maxTicksLimit: 4,
                            padding: 5
                        },
                        border: { display: false }
                    }
                },
                layout: { padding: { left: 10, right: 10, top: 10, bottom: 5 } }
            }
        };

        if (type === 'pie' || type === 'doughnut') {
            const currentVal = data[0];
            const max = parseFloat(card.dataset.max) || 100;
            const remaining = Math.max(0, max - currentVal);

            createChart(
                type,
                title,
                [],
                [currentVal],
                canvasId,
                "var(--chart-color)",
                {},
                {},
                {
                    mode: 'singlePercent',
                    max: max,
                    labels: ['Mesure', 'Reste'],
                    colors: ['var(--chart-color)', 'rgba(0,0,0,0.1)'],
                    options: {
                        maintainAspectRatio: false,
                        cutout: type === 'doughnut' ? '75%' : '0%',
                        layout: { padding: 5 },
                        plugins: {
                            legend: { display: true, position: 'bottom', labels: { boxWidth: 10, padding: 10, color: tickColor } },
                            tooltip: {
                                enabled: true,
                                backgroundColor: 'var(--chart-tooltip-bg)',
                                titleColor: 'var(--chart-tooltip-text)',
                                bodyColor: 'var(--chart-tooltip-text)',
                                borderColor: 'var(--chart-tooltip-border)',
                                borderWidth: 1
                            }
                        }
                    }
                }
            );
        } else {
            if (type === 'line') {
                extra.options.elements = {
                    point: { radius: 0, hoverRadius: 4, hitRadius: 10 },
                    line: {
                        borderWidth: 3,
                        tension: 0.3,
                        fill: false,
                        backgroundColor: 'var(--bg-primary-subtle)',
                        borderColor: 'var(--chart-color)'
                    }
                };
            } else if (type === 'scatter') {
                extra.options.elements = {
                    point: { radius: 4, hoverRadius: 6, hitRadius: 10, backgroundColor: 'var(--chart-color)' },
                    line: {
                        borderWidth: 0,
                        tension: 0,
                        fill: false,
                        borderColor: 'var(--chart-color)'
                    }
                };
            }

            createChart(type, title, labels, data, canvasId, "var(--chart-color)", thresholds, view, extra);
        }
    };

    cards.forEach((card) => {
        window.renderSparkline(card);
    });

    document.addEventListener('updateSparkline', function (e) {
        const { slug, type } = e.detail;
        const cards = document.querySelectorAll(`article.card[data-slug="${slug}"]`);
        cards.forEach(card => {
            card.dataset.chartType = type;
            window.renderSparkline(card);
        });
    });

    setInterval(async function () {
        if (!document.querySelector("article.card")) return;
        try {
            const res = await fetch('/api_live_metrics');
            if (!res.ok) return;
            const metrics = await res.json();
            if (metrics.error) return;

            metrics.forEach(metric => {
                const card = document.querySelector(`article.card[data-slug="${metric.slug}"]`);
                if (!card) return;

                const valueEl = card.querySelector('.value span:first-child');
                if (valueEl && metric.value !== '') valueEl.textContent = metric.value;

                const bigValueEl = card.querySelector('.big-value');
                if (bigValueEl && metric.value !== '') bigValueEl.textContent = metric.value;

                const unitEls = card.querySelectorAll('.unit');
                unitEls.forEach(el => { if (metric.unit) el.textContent = metric.unit; });

                const criticalIcon = card.querySelector('.status-critical');
                const warningIcon = card.querySelector('.status-warning');

                if (metric.state_class && metric.state_class.includes('card--alert')) {
                    if (criticalIcon) criticalIcon.style.display = 'flex';
                    if (warningIcon) warningIcon.style.display = 'none';
                    card.classList.add('card--alert');
                    card.classList.remove('card--warn');
                } else if (metric.state_class && metric.state_class.includes('card--warn')) {
                    if (criticalIcon) criticalIcon.style.display = 'none';
                    if (warningIcon) warningIcon.style.display = 'flex';
                    card.classList.add('card--warn');
                    card.classList.remove('card--alert');
                } else {
                    if (criticalIcon) criticalIcon.style.display = 'none';
                    if (warningIcon) warningIcon.style.display = 'none';
                    card.classList.remove('card--alert', 'card--warn');
                }

                const canvas = card.querySelector("canvas.card-spark-canvas");
                if (metric.chart_type === 'pie' || metric.chart_type === 'doughnut') {
                    if (canvas && canvas.chartInstance && metric.value !== '') {
                        const chart = canvas.chartInstance;
                        const currentVal = Number(metric.value);
                        const max = parseFloat(card.dataset.max) || 100;
                        chart.data.datasets[0].data = [currentVal, Math.max(0, max - currentVal)];
                        chart.update('none');
                    }
                } else {
                    const dataList = card.querySelector("ul[data-spark]");
                    if (dataList && metric.time_iso && metric.value !== '') {
                        const existing = dataList.querySelector(`li[data-time="${metric.time_iso}"]`);
                        if (!existing) {
                            const li = document.createElement('li');
                            li.dataset.time = metric.time_iso;
                            li.dataset.value = metric.value;
                            li.dataset.flag = metric.is_crit_flag ? '1' : '0';

                            dataList.appendChild(li);
                            while (dataList.children.length > 50) {
                                dataList.removeChild(dataList.firstElementChild);
                            }

                            const canvas = card.querySelector("canvas.card-spark-canvas");
                            if (canvas && canvas.chartInstance && canvas.chartInstance.data.datasets.length > 0) {
                                const chart = canvas.chartInstance;
                                const timeMs = new Date(metric.time_iso).getTime();
                                const val = Number(metric.value);

                                if (isNaN(timeMs)) return;

                                const ds = chart.data.datasets[0];
                                if (!ds || !ds.data) return;

                                // Add new measure if not already present
                                const exists = ds.data.some(p => p.x === timeMs);
                                if (!exists) {
                                    ds.data.push({ x: timeMs, y: val });
                                    ds.data.sort((a, b) => a.x - b.x);

                                    // Maintain fixed observation window for sparklines
                                    if (ds.data.length > 100) ds.data.shift();

                                    chart.update('none');
                                }
                            } else {
                                window.renderSparkline(card);
                            }
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Live metrics fetch error:', e);
        }
    }, 1000);
})();
