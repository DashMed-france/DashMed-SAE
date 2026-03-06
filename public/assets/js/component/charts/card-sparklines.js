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

        const parseDatasetNumber = (val) => (val !== undefined && val !== null && val !== '') ? Number(val) : NaN;
        const thresholds = {
            nmin: parseDatasetNumber(card.dataset.nmin),
            nmax: parseDatasetNumber(card.dataset.nmax),
            cmin: parseDatasetNumber(card.dataset.cmin),
            cmax: parseDatasetNumber(card.dataset.cmax)
        };
        const view = {
            min: parseDatasetNumber(card.dataset.dmin),
            max: parseDatasetNumber(card.dataset.dmax)
        };

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

            const markArea = [];
            const nmin = thresholds.nmin;
            const nmax = thresholds.nmax;
            const cmin = thresholds.cmin;
            const cmax = thresholds.cmax;
            const c_red = resolveColor('var(--chart-band-red)');
            const c_yellow = resolveColor('var(--chart-band-yellow)');
            const c_green = resolveColor('var(--chart-band-green)');

            const bMin = view.min !== undefined && view.min !== null && !isNaN(view.min) ? view.min : 0;
            const bMax = view.max !== undefined && view.max !== null && !isNaN(view.max) ? view.max : 250; // fallback max

            if (Number.isFinite(cmax) && Number.isFinite(nmin) && cmax <= nmin) {
                // Inverted scale (e.g. Glasgow)
                markArea.push([{ yAxis: cmax, itemStyle: { color: c_red } }, { yAxis: bMin }]);
                markArea.push([{ yAxis: nmin, itemStyle: { color: c_yellow } }, { yAxis: cmax }]);
                const greenTop = Number.isFinite(nmax) ? nmax : bMax;
                if (greenTop > nmin) markArea.push([{ yAxis: greenTop, itemStyle: { color: c_green } }, { yAxis: nmin }]);
                if (Number.isFinite(nmax) && bMax > nmax) markArea.push([{ yAxis: bMax, itemStyle: { color: c_yellow } }, { yAxis: nmax }]);
            } else if (Number.isFinite(nmax) && Number.isFinite(cmin) && nmax <= cmin) {
                // Inverted scale 2
                const greenBottom = Number.isFinite(nmin) ? nmin : bMin;
                if (Number.isFinite(nmin) && greenBottom > bMin) markArea.push([{ yAxis: greenBottom, itemStyle: { color: c_yellow } }, { yAxis: bMin }]);
                if (nmax > greenBottom) markArea.push([{ yAxis: nmax, itemStyle: { color: c_green } }, { yAxis: greenBottom }]);
                markArea.push([{ yAxis: cmin, itemStyle: { color: c_yellow } }, { yAxis: nmax }]);
                markArea.push([{ yAxis: bMax, itemStyle: { color: c_red } }, { yAxis: cmin }]);
            } else {
                // Standard scale
                if (Number.isFinite(cmin)) markArea.push([{ yAxis: cmin, itemStyle: { color: c_red } }, { yAxis: bMin }]);

                let greenBottom = bMin;
                if (Number.isFinite(nmin)) {
                    let bottomEdge = Number.isFinite(cmin) ? cmin : bMin;
                    greenBottom = nmin;
                    if (nmin > bottomEdge) markArea.push([{ yAxis: nmin, itemStyle: { color: c_yellow } }, { yAxis: bottomEdge }]);
                }

                let greenTop = bMax;
                if (Number.isFinite(nmax)) {
                    let topEdge = Number.isFinite(cmax) ? cmax : bMax;
                    greenTop = nmax;
                    if (topEdge > nmax) markArea.push([{ yAxis: topEdge, itemStyle: { color: c_yellow } }, { yAxis: nmax }]);
                }

                if (greenTop > greenBottom) {
                    markArea.push([{ yAxis: greenTop, itemStyle: { color: c_green } }, { yAxis: greenBottom }]);
                }

                if (Number.isFinite(cmax)) {
                    markArea.push([{ yAxis: bMax, itemStyle: { color: c_red } }, { yAxis: cmax }]);
                }
            }

            options = {
                grid: {
                    top: 10,
                    bottom: 0,
                    left: 0,
                    right: 15,
                    containLabel: true
                },
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: tooltipBg,
                    textStyle: { color: tooltipText },
                    borderColor: tooltipBorder,
                    formatter: function (params) {
                        const date = new Date(parseInt(params[0].value[0]));
                        if (isNaN(date.getTime())) return '';
                        const dateStr = date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        const timeStr = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                        const val = params[0].value[1];
                        return `${dateStr} ${timeStr}<br/><b>${val}</b>`;
                    }
                },
                xAxis: {
                    type: 'time', // Fix: Use 'time' instead of 'category' for proper zoom
                    boundaryGap: false,
                    show: true,
                    z: 5,
                    splitLine: { show: true, lineStyle: { color: gridColor, type: 'solid' } },
                    axisLabel: {
                        show: true,
                        color: tickColor,
                        formatter: '{HH}:{mm}',
                        interval: 'auto',
                        hideOverlap: true
                    },
                    axisTick: { show: false },
                    axisLine: { show: false }
                },
                yAxis: {
                    type: 'value',
                    min: view.min,
                    max: view.max,
                    show: true,
                    z: 5,
                    splitLine: { show: true, lineStyle: { color: gridColor, type: 'solid' } },
                    axisLabel: { show: true, color: tickColor },
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
                    markArea: {
                        silent: true,
                        data: markArea
                    }
                }]
            };

            const durationVal = card.dataset.displayDuration;
            if (durationVal && durationVal !== 'all' && rawData.length > 0) {
                const hours = parseFloat(durationVal);
                if (!isNaN(hours)) {
                    const lastTime = rawData[rawData.length - 1][0];
                    const minTime = lastTime - (hours * 3600 * 1000);
                    options.dataZoom = [
                        { type: 'inside', startValue: minTime, endValue: lastTime }
                    ];
                }
            } else if (durationVal === 'all') {
                options.dataZoom = [
                    { type: 'inside', start: 0, end: 100 }
                ];
            }
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

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('card-interval-select')) {
            const select = e.target;
            const card = select.closest('article.card');
            if (!card) return;

            const val = select.value;
            const slug = card.dataset.slug;

            // Update local value
            card.dataset.cardDisplayDuration = val;

            // Persist preference for this specific card
            const formData = new FormData();
            formData.append('parameter_id', slug);
            formData.append('chart_type', val);
            formData.append('chart_pref_submit', '1');
            formData.append('preference_type', 'card_duration');
            fetch(window.location.href, { method: 'POST', body: formData }).catch(console.error);

            // Update chart zoom
            const canvas = card.querySelector(".card-spark-canvas");
            if (canvas && canvas.chartInstance) {
                const chart = canvas.chartInstance;
                if (val === 'all') {
                    chart.dispatchAction({ type: 'dataZoom', start: 0, end: 100 });
                } else {
                    const hours = parseFloat(val);
                    if (!isNaN(hours)) {
                        const opt = chart.getOption();
                        const data = opt.series[0].data;
                        if (data && data.length > 0) {
                            const lastTime = data[data.length - 1][0];
                            const minTime = lastTime - (hours * 3600 * 1000);
                            chart.dispatchAction({ type: 'dataZoom', startValue: minTime, endValue: lastTime });
                        }
                    }
                }
            }
        }
    });

    window.addEventListener('DashMedDurationChange', function (e) {
        // This event comes from the MODAL select.
        // User says: 'quand je change le temps de la modale ça... change aussi celui de la card mais lui ça le mémorise'
        // Actually, if we want them independent, the modal change SHOULD NOT affect the card.
        // I'll comment this out or make it only update open modals.
    });


    window.addEventListener('DashMedMetricsUpdate', function (event) {
        if (!document.querySelector("article.card")) return;
        try {
            const metrics = event.detail;
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

                            /**
                             * Append new data point to the DOM for synchronization.
                             * Maintaining full history as per user requirements.
                             */
                            dataList.appendChild(li);

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

                                        const updateObj = { series: [{ data: ds }] };

                                        // Auto-scroll logic for sparkline too? 
                                        // Sparklines are usually fixed to the duration.
                                        const durationVal = card.dataset.displayDuration;
                                        if (durationVal && durationVal !== 'all') {
                                            const hours = parseFloat(durationVal);
                                            const minTime = timeMs - (hours * 3600 * 1000);
                                            updateObj.dataZoom = [{ type: 'inside', startValue: minTime, endValue: timeMs }];
                                        }

                                        chart.setOption(updateObj);
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
    });

})();

