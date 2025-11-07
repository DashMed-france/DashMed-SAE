function createChart(
    type,
    title = "Titre",
    labels = [],
    data = [],
    target,
    color = '#275afe'
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
                },
                title: {
                    display: false,
                    text: title
                }
            }
        },
    };

    const El = document.getElementById(target);

    if (!El) { console.error('Canvas introuvable:', target); return; }

    if (El.chartInstance) El.chartInstance.destroy();

    El.chartInstance = new Chart(El, config);
}