(function () {
    if (!window.Chart || typeof createChart !== "function") return;

    const cards = document.querySelectorAll("article.card");
    if (!cards.length) return;

    cards.forEach((card) => {
        const slug = card.dataset.slug;
        if (!slug) return;

        const type = card.dataset.chartType || 'line';
        if (type === 'value') return;

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

        const labels = rawData.map(d => d.time.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" }));
        const data = rawData.map(d => d.value);

        if (canvas) canvas.style.display = 'block';
        if (noDataPlaceholder) noDataPlaceholder.style.display = 'none';

        const title = card.dataset.display || "";

        const nmin = Number(card.dataset.nmin);
        const nmax = Number(card.dataset.nmax);
        const cmin = Number(card.dataset.cmin);
        const cmax = Number(card.dataset.cmax);
        const dmin = Number(card.dataset.dmin);
        const dmax = Number(card.dataset.dmax);

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
                        }
                    },
                    title: { display: false }
                },
                scales: {
                    x: {
                        display: true,
                        grid: { display: true, color: gridColor },
                        ticks: {
                            display: true,
                            color: tickColor,
                            font: { size: 12, weight: '600' }
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
                        fill: 'start',
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
    });
})();
