(function () {
    if (!window.echarts) return;

    const cards = document.querySelectorAll("article.card");
    if (!cards.length) return;

    const getCssVar = (name) => {
        let val = getComputedStyle(document.body || document.documentElement).getPropertyValue(name).trim();
        if (!val) return name;
        return val;
    };

    const resolveColor = (color) => {
        if (typeof color === 'string' && color.startsWith('var(')) {
            const match = color.match(/var\((--[^)]+)\)/);
            return match ? getCssVar(match[1]) : color;
        }
        return color;
    };

    window.renderSparkline = function (card) {
        const slug = card.dataset.slug;
        if (!slug) return;

        const type = card.dataset.chartType || 'line';

        const valueOnlyContainer = card.querySelector('.card-value-only-container');
        const sparkContainer = card.querySelector('.card-spark');
        const headerValue = card.querySelector('.card-header .value');

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
        const canvas = card.querySelector(".card-spark-canvas");

        if (!canvas || !dataList) return;

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

            rawData.push([d.getTime(), val]);
        });

        if (!rawData.length) {
            if (canvas) canvas.style.display = 'none';
            if (noDataPlaceholder) noDataPlaceholder.style.display = 'flex';
            return;
        }

        rawData.sort((a, b) => a[0] - b[0]);

        if (canvas) canvas.style.display = 'block';
        if (noDataPlaceholder) noDataPlaceholder.style.display = 'none';

        const chartColor = resolveColor("var(--chart-color)") || '#275afe';
        const gridColor = resolveColor("var(--chart-grid-color)") || '#e5e7eb';
        const tickColor = resolveColor("var(--chart-tick-color)") || '#6b7280';
        const tooltipBg = resolveColor("var(--chart-tooltip-bg)") || '#ffffff';
        const tooltipText = resolveColor("var(--chart-tooltip-text)") || '#111827';
        const tooltipBorder = resolveColor("var(--chart-tooltip-border)") || '#e5e7eb';

        if (canvas.chartInstance) {
            canvas.chartInstance.dispose();
        }

        const chartInstance = echarts.init(canvas, null, {
            renderer: 'canvas',
            devicePixelRatio: window.devicePixelRatio
        });
        canvas.chartInstance = chartInstance;

        let options = {};

        if (type === 'pie' || type === 'doughnut') {
            const currentVal = rawData[0][1];
            const max = parseFloat(card.dataset.max) || 100;
            const remaining = Math.max(0, max - currentVal);
            const radius = type === 'doughnut' ? ['50%', '90%'] : '90%';

            options = {
                tooltip: {
                    trigger: 'item',
                    backgroundColor: tooltipBg,
                    textStyle: { color: tooltipText },
                    borderColor: tooltipBorder,
                },
                series: [
                    {
                        type: 'pie',
                        radius: radius,
                        center: ['50%', '50%'],
                        data: [
                            { value: currentVal, name: 'Mesure', itemStyle: { color: chartColor } },
                            { value: remaining, name: 'Reste', itemStyle: { color: 'rgba(0,0,0,0.1)' } }
                        ],
                        label: { show: false },
                        silent: false
                    }
                ]
            };
        } else {
            const eType = type === 'line' ? 'line' : (type === 'bar' ? 'bar' : 'scatter');

            options = {
                grid: {
                    top: 5,
                    bottom: 5,
                    left: 0,
                    right: 0
                },
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: tooltipBg,
                    textStyle: { color: tooltipText },
                    borderColor: tooltipBorder,
                    formatter: function (params) {
                        const date = new Date(params[0].value[0]);
                        const val = params[0].value[1];
                        const time = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                        return `${time}<br/><b>${val}</b>`;
                    }
                },
                xAxis: {
                    type: 'time',
                    show: false,
                    splitLine: { show: false },
                    axisLabel: { show: false },
                    axisTick: { show: false },
                    axisLine: { show: false }
                },
                yAxis: {
                    type: 'value',
                    show: false,
                    splitLine: { show: false },
                    axisLabel: { show: false },
                    axisTick: { show: false },
                    axisLine: { show: false }
                },
                series: [{
                    data: rawData,
                    type: eType,
                    showSymbol: type === 'scatter',
                    symbolSize: type === 'scatter' ? 4 : 0,
                    smooth: true,
                    itemStyle: { color: chartColor },
                    lineStyle: { color: chartColor, width: 2 },
                    areaStyle: type === 'line' ? {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: chartColor + '66' },
                            { offset: 1, color: chartColor + '00' }
                        ])
                    } : undefined
                }]
            };
        }

        chartInstance.setOption(options);

        // Resize observer instead of resize window
        const ro = new ResizeObserver(() => {
            chartInstance.resize();
        });
        ro.observe(canvas.parentElement);
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

    let source = new EventSource('/api_stream');

    source.onmessage = function (event) {
        if (!document.querySelector("article.card")) return;
        try {
            const metrics = JSON.parse(event.data);
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

                const canvas = card.querySelector(".card-spark-canvas");
                if (metric.chart_type === 'pie' || metric.chart_type === 'doughnut') {
                    if (canvas && canvas.chartInstance && metric.value !== '') {
                        const chart = canvas.chartInstance;
                        const currentVal = Number(metric.value);
                        const max = parseFloat(card.dataset.max) || 100;
                        const remaining = Math.max(0, max - currentVal);

                        chart.setOption({
                            series: [{
                                data: [
                                    { value: currentVal, name: 'Mesure', itemStyle: { color: resolveColor("var(--chart-color)") } },
                                    { value: remaining, name: 'Reste', itemStyle: { color: 'rgba(0,0,0,0.1)' } }
                                ]
                            }]
                        });
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

                            if (canvas && canvas.chartInstance) {
                                const chart = canvas.chartInstance;
                                const timeMs = new Date(metric.time_iso).getTime();
                                const val = Number(metric.value);

                                if (isNaN(timeMs)) return;

                                const option = chart.getOption();
                                if (option.series && option.series.length > 0) {
                                    const ds = option.series[0].data || [];
                                    const exists = ds.some(p => p[0] === timeMs);
                                    if (!exists) {
                                        ds.push([timeMs, val]);
                                        ds.sort((a, b) => a[0] - b[0]);
                                        if (ds.length > 100) ds.shift();

                                        chart.setOption({
                                            series: [{ data: ds }]
                                        });
                                    }
                                }
                            } else {
                                window.renderSparkline(card);
                            }
                        }
                    }
                }
            });
        } catch (e) {
            console.error('SSE metrics fetch error:', e);
        }
    };

    source.onerror = function () {
        console.warn("SSE connection lost. Reconnecting...");
    };

})();
