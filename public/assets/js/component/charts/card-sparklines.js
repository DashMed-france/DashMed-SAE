(function () {
    if (!window.Chart || typeof createChart !== "function") return;

    const cards = document.querySelectorAll("article.card");
    if (!cards.length) return;

    cards.forEach((card) => {
        const slug = card.dataset.slug;
        if (!slug) return;

        const type = card.dataset.chartType || 'line';
        if (type === 'value') return;

        const canvasId = "spark-" + slug;
        const dataList = card.querySelector("ul[data-spark]");
        const canvas = document.getElementById(canvasId);

        if (!canvas || !dataList) return;

        const items = dataList.querySelectorAll("li");
        if (!items.length) return;

        const labels = [];
        const data = [];

        items.forEach((item) => {
            const time = item.dataset.time || "";
            const val = Number(item.dataset.value);

            if (!time || !Number.isFinite(val)) return;

            const d = new Date(time);
            if (isNaN(d.getTime())) return;

            labels.push(d.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" }));
            data.push(val);
        });

        if (!labels.length || !data.length) return;

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

        // Use CSS variables for dynamic theme support
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
                        ticks: { display: true, color: tickColor }
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
                            font: { size: 10 },
                            maxTicksLimit: 4,
                            padding: 5
                        },
                        border: { display: false }
                    }
                },
                layout: { padding: { left: 0, right: 0, top: 10, bottom: 0 } }
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
                "#4f46e5",
                {},
                {},
                {
                    mode: 'singlePercent',
                    max: max,
                    labels: ['Mesure', 'Reste'],
                    colors: ['#4f46e5', 'rgba(0,0,0,0.1)'],
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
                        borderWidth: 2,
                        tension: 0.2,
                        fill: 'start',
                        backgroundColor: 'rgba(79, 70, 229, 0.2)',
                        borderColor: '#4f46e5'
                    }
                };
            } else if (type === 'scatter') {
                extra.options.elements = {
                    point: { radius: 4, hoverRadius: 6, hitRadius: 10, backgroundColor: '#4f46e5' },
                    line: {
                        borderWidth: 0,
                        tension: 0,
                        fill: false,
                        borderColor: '#4f46e5'
                    }
                };
            }


            createChart(type, title, labels, data, canvasId, "#4f46e5", thresholds, view, extra);
        }
    });
})();
