// assets/js/component/modal/card-sparklines.js
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

        const extra = {
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false },
                    title: { display: false }
                },
                scales: {
                    x: {
                        display: false,
                        grid: { display: false }
                    },
                    y: {
                        display: true,
                        position: 'left',
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.5)',
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
                        maintainAspectRatio: true,
                        cutout: type === 'doughnut' ? '75%' : '0%',
                        layout: { padding: 5 },
                        plugins: { legend: { display: false }, tooltip: { enabled: false } }
                    }
                }
            );
        } else {
            if (type === 'line' || type === 'scatter') {
                extra.options.elements = {
                    point: { radius: 0, hoverRadius: 0, hitRadius: 0 },
                    line: {
                        borderWidth: 2,
                        tension: 0.2,
                        fill: 'start',
                        backgroundColor: 'rgba(79, 70, 229, 0.2)',
                        borderColor: '#4f46e5'
                    }
                };
            }
            if (type === 'bar') {
                extra.options.scales.x.display = true;
                extra.options.scales.x.grid = { display: false };
                extra.options.scales.x.ticks = { display: false };
            }

            createChart(type, title, labels, data, canvasId, "#4f46e5", thresholds, view, extra);
        }
    });
})();
