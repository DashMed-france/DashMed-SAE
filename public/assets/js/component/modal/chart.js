function createChart(
    type,
    title = "Titre",
    labels = [],
    data = [],
    target,
    color = '#275afe',
    thresholds = {},
    view = {}
) {

    labels = [...labels].reverse();
    data = [...data].reverse();

    console.log(
        "type :" + type + "\n",
        "title :" + title + "\n",
        "label :" + labels + "\n",
        "data :" + data + "\n",
        "target :" + target + "\n",
        "color :" + color + "\n",
        "treshold :" + thresholds + "\n"
    )
    const dataset = {
        labels: labels,
        datasets: [{
            label: title,
            data: data,
            borderColor: color,
            backgroundColor: color + '20',
            tension: 0.3,
            fill: false,
            pointRadius: 5,
            pointBackgroundColor: color
        }]
    };

    const config = {
        type: type,
        data: dataset,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        filter: (item) => !(item.text || '').startsWith('_band_')
                    }
                },
                title: {
                    display: false,
                    text: title
                }
            },
            scales: {
                y: {
                    min: view.min ?? undefined,
                    max: view.max ?? undefined,
                    grace: 0
                }
            }
        },
    };

    const addBand = (yTop, yBottom, bg) => {
        const t = Number(yTop), b = Number(yBottom);
        if (!Number.isFinite(t) || !Number.isFinite(b)) return;
        const topArr = Array(labels.length).fill(t);
        const botArr = Array(labels.length).fill(b);

        dataset.datasets.push({
            label: '_band_top_',
            data: topArr,
            borderWidth: 0,
            pointRadius: 0,
            fill: false,
            tension: 0,
            order: -10
        });

        dataset.datasets.push({
            label: '_band_fill_',
            data: botArr,
            borderWidth: 0,
            pointRadius: 0,
            backgroundColor: bg,
            fill: '-1',
            tension: 0,
            order: -10
        });
    };

    const nmin = Number(thresholds.nmin);
    const nmax = Number(thresholds.nmax);
    const cmin = Number(thresholds.cmin);
    const cmax = Number(thresholds.cmax);

    const vals = data.filter(Number.isFinite);
    let yMin = Math.min(...vals);
    let yMax = Math.max(...vals);
    [nmin, cmin].forEach(v => Number.isFinite(v) && (yMin = Math.min(yMin, v)));
    [nmax, cmax].forEach(v => Number.isFinite(v) && (yMax = Math.max(yMax, v)));

    if (Number.isFinite(cmin))
        addBand(cmin, view.min ?? yMin, 'rgba(239,68,68,0.12)');

    if (Number.isFinite(cmin) && Number.isFinite(nmin) && cmin < nmin)
        addBand(nmin, cmin, 'rgba(234,179,8,0.12)');

    if (Number.isFinite(nmin) && Number.isFinite(nmax) && nmin < nmax)
        addBand(nmax, nmin, 'rgba(34,197,94,0.12)');

    if (Number.isFinite(nmax) && Number.isFinite(cmax) && nmax < cmax)
        addBand(cmax, nmax, 'rgba(234,179,8,0.12)');

    if (Number.isFinite(cmax))
        addBand(view.max ?? yMax, cmax, 'rgba(239,68,68,0.12)');

    const El = document.getElementById(target);
    if (!El) { console.error('Canvas introuvable:', target); return; }
    if (El.chartInstance) El.chartInstance.destroy();
    El.chartInstance = new Chart(El, config);
}