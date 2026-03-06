const historyCache = {};

function applyThemeColors(chartInstance) {
    // ECharts handles theme partly through initialization, but we can set option
    if (!chartInstance) return;
    const style = getComputedStyle(document.body || document.documentElement);
    const gridColor = style.getPropertyValue('--chart-grid-color').trim();
    const tickColor = style.getPropertyValue('--chart-tick-color').trim();
    const tooltipBg = style.getPropertyValue('--chart-tooltip-bg').trim();
    const tooltipText = style.getPropertyValue('--chart-tooltip-text').trim();

    chartInstance.setOption({
        tooltip: {
            backgroundColor: tooltipBg,
            textStyle: { color: tooltipText }
        },
        xAxis: {
            splitLine: { lineStyle: { color: gridColor } },
            axisLabel: { color: tickColor }
        },
        yAxis: {
            splitLine: { lineStyle: { color: gridColor } },
            axisLabel: { color: tickColor }
        }
    });
}

const getCssVar = (name) => {
    let val = getComputedStyle(document.body || document.documentElement).getPropertyValue(name).trim();
    if (!val) return name;
    let depth = 0;
    while (val.startsWith('var(') && depth < 5) {
        const match = val.match(/var\((--[^)]+)\)/);
        if (match) {
            val = getComputedStyle(document.body || document.documentElement).getPropertyValue(match[1]).trim();
        } else break;
        depth++;
    }
    return val;
};

const resolveColor = (color) => {
    if (typeof color === 'string' && color.startsWith('var(')) {
        const match = color.match(/var\((--[^)]+)\)/);
        return match ? getCssVar(match[1]) : color;
    }
    return color;
};

async function updatePanelChart(panelId, chartId, title) {
    const root = document.getElementById('modalDetails');
    if (!root) return;

    const panel = root.querySelector('#' + panelId);
    if (!panel) return;

    const list = panel.querySelectorAll('ul[data-hist]>li');
    const noDataPlaceholder = panel.querySelector('.modal-no-data-placeholder');
    const canvas = document.getElementById(chartId);
    const valueContainer = panel.querySelector('.modal-value-only');

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

    if (valueContainer) valueContainer.style.display = 'none';

    const parseDatasetNumber = (val) => (val !== undefined && val !== null && val !== '') ? Number(val) : NaN;
    const thresholds = {
        nmin: parseDatasetNumber(panel.dataset.nmin),
        nmax: parseDatasetNumber(panel.dataset.nmax),
        cmin: parseDatasetNumber(panel.dataset.cmin),
        cmax: parseDatasetNumber(panel.dataset.cmax)
    };
    const view = {
        min: parseDatasetNumber(panel.dataset.dmin),
        max: parseDatasetNumber(panel.dataset.dmax)
    };

    if (chartType === 'pie' || chartType === 'doughnut') {
        const item = list[idx];
        const val = Number(item?.dataset?.value);
        let max = 100;
        if (unit.includes('%')) max = 100;
        else if (Number.isFinite(view.max)) max = view.max;

        createEChart(chartType, title, [[Date.now(), val]], chartId, 'var(--chart-color)', thresholds, view, { mode: 'singlePercent', max });
    } else {
        const targetDate = panel.dataset.targetDate || '';
        const slugMatch = panelId.match(/panel-(.+)$/);
        const slug = slugMatch ? slugMatch[1] : '';
        const paramId = panel.dataset.paramId || slug;

        let spinner = panel.querySelector('.chart-loading-spinner');
        if (!spinner) {
            spinner = document.createElement('div');
            spinner.className = 'chart-loading-spinner';
            spinner.innerHTML = '<div style="width: 30px; height: 30px; border: 3px solid var(--border-color); border-top-color: var(--chart-color); border-radius: 50%; animation: spin 1s linear infinite;"></div><style>@keyframes spin { to { transform: rotate(360deg); } }</style>';
            spinner.style.position = 'absolute';
            spinner.style.top = '50%';
            spinner.style.left = '50%';
            spinner.style.transform = 'translate(-50%, -50%)';
            spinner.style.zIndex = '100';
            panel.appendChild(spinner);
            panel.style.position = 'relative';
        }
        spinner.style.display = 'block';
        if (canvas) canvas.style.opacity = '0.5';

        try {
            const dateParam = targetDate ? `&date=${encodeURIComponent(targetDate)}` : '';
            const cacheKey = `${paramId}-${targetDate || 'now'}`;

            let dataArr;
            if (historyCache[cacheKey]) {
                dataArr = historyCache[cacheKey];
            } else {
                // We no longer send limit=0 by default. 
                // The server automatically downsamples for the chart if needed.
                const res = await fetch(`${window.location.origin}/api_history?param=${encodeURIComponent(paramId)}${dateParam}`);
                if (!res.ok) throw new Error('Fetch failed');
                dataArr = await res.json();
                if (dataArr.error) throw new Error(dataArr.error);
                historyCache[cacheKey] = dataArr;
            }

            // --- Configure CSV Download Link ---
            const csvBtn = panel.querySelector('.btn-csv-download');
            if (csvBtn) {
                csvBtn.href = `${window.location.origin}/api_history?param=${encodeURIComponent(paramId)}${dateParam}&raw=1&format=csv`;
            }

            if (dataArr.length === 0) {
                if (canvas) canvas.style.display = 'none';
                if (noDataPlaceholder) noDataPlaceholder.style.display = 'flex';
                spinner.style.display = 'none';
                return;
            } else {
                if (canvas) canvas.style.display = 'block';
                if (noDataPlaceholder) noDataPlaceholder.style.display = 'none';
            }

            const rawData = [];
            dataArr.forEach((item) => {
                const timeStr = item.time_iso;
                const val = Number(item.value);
                if (timeStr) {
                    const d = new Date(timeStr);
                    if (!isNaN(d.getTime())) {
                        rawData.push([d.getTime(), val]);
                    }
                }
            });

            rawData.sort((a, b) => a[0] - b[0]);

            if (canvas) canvas.style.opacity = '1';
            spinner.style.display = 'none';

            const durationVal = panel.dataset.displayDuration || '0.0333';
            let initialZoom = 2 * 60 * 1000;
            if (durationVal !== 'all') {
                initialZoom = parseFloat(durationVal) * 3600 * 1000;
            } else {
                initialZoom = 0; 
            }

            createEChart(chartType, title, rawData, chartId, 'var(--chart-color)', thresholds, view, { initialZoomMs: initialZoom });
            setupRealtimeSyncButton(panel, chartId, title);

        } catch (err) {
            console.error('Error fetching chart data:', err);
            if (canvas) canvas.style.opacity = '1';
            if (spinner) spinner.style.display = 'none';
        }
    }
}

function updatePanelPieChart(panelId, chartId, title) {
    updatePanelChart(panelId, chartId, title);
}

function createEChart(type, title, rawData, target, color, thresholds, view, extra) {
    if (!window.echarts) return;
    const canvas = document.getElementById(target);
    if (!canvas) return;

    if (canvas.chartInstance) {
        canvas.chartInstance.dispose();
    }

    const chartInstance = echarts.init(canvas, null, {
        renderer: 'canvas',
        devicePixelRatio: window.devicePixelRatio
    });
    canvas.chartInstance = chartInstance;

    const chartColor = resolveColor(color) || '#275afe';
    const gridColor = resolveColor("var(--chart-grid-color)") || '#e5e7eb';
    const tickColor = resolveColor("var(--chart-tick-color)") || '#6b7280';
    const tooltipBg = resolveColor("var(--chart-tooltip-bg)") || '#ffffff';
    const tooltipText = resolveColor("var(--chart-tooltip-text)") || '#111827';
    const tooltipBorder = resolveColor("var(--chart-tooltip-border)") || '#e5e7eb';

    let options = {};
    const eType = type === 'scatter' ? 'scatter' : (type === 'bar' ? 'bar' : 'line');

    if (type === 'pie' || type === 'doughnut') {
        const currentVal = rawData[0][1];
        const max = extra.max || 100;
        const remaining = Math.max(0, max - currentVal);
        const radius = type === 'doughnut' ? ['40%', '70%'] : '70%';

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
                    label: { show: true, position: 'inside', formatter: '{c}' },
                }
            ]
        };
    } else {
        const markArea = [];
        const nmin = thresholds.nmin;
        const nmax = thresholds.nmax;
        const cmin = thresholds.cmin;
        const cmax = thresholds.cmax;
        const c_red = resolveColor('var(--chart-band-red)');
        const c_yellow = resolveColor('var(--chart-band-yellow)');
        const c_green = resolveColor('var(--chart-band-green)');

        const bMin = view.min !== undefined && view.min !== null && !isNaN(view.min) ? view.min : 0;
        const bMax = view.max !== undefined && view.max !== null && !isNaN(view.max) ? view.max : 250;

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

        const xMin = extra.initialZoomMs && rawData.length > 0 ? rawData[rawData.length - 1][0] - extra.initialZoomMs : undefined;

        options = {
            grid: { top: 30, bottom: 25, left: 10, right: 20, containLabel: true },
            tooltip: {
                trigger: 'axis',
                backgroundColor: tooltipBg,
                textStyle: { color: tooltipText },
                borderColor: tooltipBorder,
                formatter: function (params) {
                    if (!params.length) return '';
                    const date = new Date(params[0].value[0]);
                    const time = date.toLocaleString('fr-FR');
                    return `${time}<br/><b>${params[0].value[1]}</b>`;
                }
            },
            dataZoom: [
                {
                    type: 'inside',
                    xAxisIndex: 0,
                    startValue: xMin,
                    endValue: rawData.length > 0 ? rawData[rawData.length - 1][0] : undefined,
                    filterMode: 'filter'
                },
                {
                    type: 'slider',
                    xAxisIndex: 0,
                    show: true,
                    bottom: 0,
                    height: 20,
                    borderColor: 'transparent',
                    textStyle: { color: tickColor },
                    handleStyle: { color: chartColor },
                    fillerColor: 'rgba(39, 90, 254, 0.2)',
                    startValue: xMin,
                    endValue: rawData.length > 0 ? rawData[rawData.length - 1][0] : undefined
                }
            ],
            xAxis: {
                type: 'time',
                z: 5,
                splitLine: { show: true, lineStyle: { color: gridColor, type: 'solid', opacity: 1 } },
                axisLabel: { color: tickColor, formatter: '{HH}:{mm}', margin: 12 },
                axisTick: { show: false },
                axisLine: { show: false }
            },
            dataset: { source: [[0, 0]] }, // Dummy dataset just to help some ECharts internal logic if needed
            yAxis: {
                type: 'value',
                min: view.min,
                max: view.max,
                z: 5,
                splitLine: { show: true, lineStyle: { color: gridColor, type: 'solid', opacity: 1 } },
                axisLabel: { color: tickColor, margin: 12 },
                axisTick: { show: false },
                axisLine: { show: false }
            },
            series: [{
                data: rawData,
                type: eType,
                showSymbol: type === 'scatter',
                symbolSize: type === 'scatter' ? 6 : 0,
                smooth: true,
                sampling: null,
                large: false,
                itemStyle: { color: chartColor },
                lineStyle: { color: chartColor, width: 2 },
                areaStyle: type === 'line' ? {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: chartColor + '66' },
                        { offset: 1, color: chartColor + '00' }
                    ])
                } : undefined,
                markArea: {
                    silent: true,
                    data: markArea
                }
            }]
        };
    }

    chartInstance.setOption(options);

    chartInstance.on('dataZoom', function (evt) {
        // If the user interacts (manual zoom or pan), we break the sync
        const canvas = chartInstance.getDom();
        if (evt.batch) {
            // Check if it's a manual interaction (not our programmatic dispatch)
            // Most manual interactions in ECharts have a batch or are simple events
            // We'll set isSynced to false if the user moves away from the end
            const opt = chartInstance.getOption();
            const dz = opt.dataZoom[0];
            const end = dz.endValue;
            const lastData = rawData.length > 0 ? rawData[rawData.length - 1][0] : 0;

            // If the viewed end is significantly before the last data point, it's not synced
            if (lastData - end > 1000) {
                canvas.dataset.isSynced = "false";
                const panel = canvas.closest('.modal-grid');
                if (panel) {
                    const syncBtn = panel.querySelector('.sync-realtime-btn');
                    if (syncBtn) syncBtn.style.display = 'flex';
                }
            }
        }
        canvas.dispatchEvent(new CustomEvent('chartInteract'));
    });

    const ro = new ResizeObserver(() => chartInstance.resize());
    ro.observe(canvas.parentElement);
}

function setupRealtimeSyncButton(panel, chartId, title) {
    let syncBtn = panel.querySelector('.sync-realtime-btn');
    if (!syncBtn) {
        syncBtn = document.createElement('button');
        syncBtn.className = 'sync-realtime-btn btn-primary';
        syncBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>';
        syncBtn.title = "Retourner aux données récentes";
        syncBtn.style.position = 'absolute';
        syncBtn.style.top = '10px';
        syncBtn.style.right = '10px';
        syncBtn.style.zIndex = '10';
        syncBtn.style.display = 'none';
        syncBtn.style.padding = '8px';
        syncBtn.style.borderRadius = '50%';
        syncBtn.style.border = 'none';
        syncBtn.style.background = 'var(--primary-color, var(--chart-color, #275afe))';
        syncBtn.style.color = '#fff';
        syncBtn.style.cursor = 'pointer';
        syncBtn.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        syncBtn.style.display = 'flex';
        syncBtn.style.alignItems = 'center';
        syncBtn.style.justifyContent = 'center';

        syncBtn.onclick = () => {
            const canvas = document.getElementById(chartId);
            if (canvas && canvas.chartInstance) {
                const chart = canvas.chartInstance;
                const opt = chart.getOption();
                const dz = opt.dataZoom[0];

                // Keep the current duration
                const duration = (dz.endValue || Date.now()) - (dz.startValue || (Date.now() - 120000));

                const lastData = opt.series[0].data;
                const lastTime = lastData.length > 0 ? lastData[lastData.length - 1][0] : Date.now();

                canvas.dataset.isSynced = "true";

                chart.dispatchAction({
                    type: 'dataZoom',
                    startValue: lastTime - duration,
                    endValue: lastTime
                });

                syncBtn.style.display = 'none';
            }
        };

        const canvas = document.getElementById(chartId);
        if (canvas) {
            canvas.dataset.isSynced = "true"; // Start as synced
            canvas.parentElement.appendChild(syncBtn);
        }
    } else {
        const canvas = document.getElementById(chartId);
        if (canvas) canvas.dataset.isSynced = "true";
        syncBtn.style.display = 'none';
    }
}

document.addEventListener('change', function (e) {
    const datePicker = e.target.closest('.modal-date-picker');
    if (!datePicker) return;

    const panel = datePicker.closest('.modal-grid');
    if (!panel) return;

    const targetDate = datePicker.value || '';
    panel.dataset.targetDate = targetDate;

    const chartId = panel.querySelector('.modal-chart')?.dataset.id;
    const display = panel.dataset.display || '';

    if (chartId) {
        updatePanelChart(panel.id, chartId, display);
    }
});

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.chart-type-btn');
    if (!btn) return;

    if (btn.classList.contains('modal-chart-btn')) {
        e.preventDefault();
        const group = btn.closest('.chart-type-group');
        if (group) {
            group.querySelectorAll('.modal-chart-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        const panel = btn.closest('.modal-grid');
        if (panel) {
            panel.dataset.chart = btn.dataset.modalChartType;
            const chartId = panel.querySelector('.modal-chart')?.dataset.id;
            const display = panel.dataset.display || '';
            if (chartId) {
                updatePanelChart(panel.id, chartId, display);
            }

            const paramId = panel.dataset.paramId;
            if (paramId) {
                const formData = new FormData();
                formData.append('parameter_id', paramId);
                formData.append('chart_type', btn.dataset.modalChartType);
                formData.append('chart_pref_submit', '1');
                formData.append('is_modal_pref', '1');
                fetch(window.location.href, { method: 'POST', body: formData }).catch(console.error);
            }
        }
        return;
    }
});

document.addEventListener('change', function (e) {
    if (e.target.classList.contains('modal-interval-select')) {
        const select = e.target;
        const panel = select.closest('.modal-grid');
        if (!panel) return;

        const val = select.value;
        const paramId = panel.dataset.paramId;

        // Persist preference for ALL parameters (global behavior requested)
        // We'll iterate over all visible panels or just send one global request if the server supports it.
        // The current server logic saves per parameter. To make it "the same for everyone", 
        // we can either send multiple requests or update the server. 
        // User said "la même chose pour la modale et que ça soit sauvegardé", implying global.

        const allPanels = document.querySelectorAll('.modal-grid');
        allPanels.forEach(p => {
            const pId = p.dataset.paramId;
            if (pId) {
                p.dataset.displayDuration = val; // Update local dataset
                const formData = new FormData();
                formData.append('parameter_id', pId);

                formData.append('chart_type', val); // Value is the duration
                formData.append('chart_pref_submit', '1');
                formData.append('preference_type', 'duration');
                fetch(window.location.href, { method: 'POST', body: formData }).catch(console.error);
            }
        });

        // Update all open charts in UI
        const allCanvas = document.querySelectorAll('.modal-chart');
        allCanvas.forEach(canvas => {
            if (canvas.chartInstance) {
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
                const p = canvas.closest('.modal-grid');
                if (p) {
                    const syncBtn = p.querySelector('.sync-realtime-btn');
                    if (syncBtn) syncBtn.style.display = 'block';

                    // Synchronize the other selects
                    const s = p.querySelector('.modal-interval-select');
                    if (s && s !== select) s.value = val;
                }
            }
        });

        // Dashboard sparklines are now independent
    }
});



// SSE Modal Sync
(function () {
    window.addEventListener('DashMedMetricsUpdate', function (event) {
        const modal = document.querySelector(".modal");
        if (!modal || !modal.classList.contains("show-modal")) return;

        const canvas = modal.querySelector(".modal-chart");
        if (!canvas || !canvas.chartInstance) return;

        const panel = canvas.closest('.modal-grid');
        if (!panel) return;
        if (panel.dataset.targetDate) return;

        const slugMatch = panel.id.match(/panel-(.+)$/);
        const slug = slugMatch ? slugMatch[1] : (panel.dataset.slug || '');
        if (!slug) return;

        try {
            const metrics = event.detail;
            if (metrics.error) return;

            const metric = metrics.find(m => m.slug === slug);
            if (!metric || !metric.value || typeof metric.time_iso === 'undefined') return;

            const time = new Date(metric.time_iso).getTime();
            const val = Number(metric.value);
            const chart = canvas.chartInstance;

            if (metric.chart_type === 'pie' || metric.chart_type === 'doughnut') {
                const max = parseFloat(panel.dataset.max || panel.dataset.nmax || panel.dataset.dmax) || 100;
                chart.setOption({
                    series: [{
                        data: [
                            { value: val, name: 'Mesure', itemStyle: { color: resolveColor("var(--chart-color)") } },
                            { value: Math.max(0, max - val), name: 'Reste', itemStyle: { color: 'rgba(0,0,0,0.1)' } }
                        ]
                    }]
                });
            } else {
                const option = chart.getOption();
                if (option.series && option.series.length > 0) {
                    const ds = option.series[0].data || [];
                    const exists = ds.some(p => p[0] === time);
                    if (!exists) {
                        ds.push([time, val]);
                        ds.sort((a, b) => a[0] - b[0]);

                        const updateObj = { series: [{ data: ds }] };

                        // AUTO-SCROLL LOGIC
                        if (canvas.dataset.isSynced === "true") {
                            const opt = chart.getOption();
                            const dz = opt.dataZoom[0];
                            const duration = dz.endValue - dz.startValue;

                            updateObj.dataZoom = [
                                { type: 'inside', startValue: time - duration, endValue: time },
                                { type: 'slider', startValue: time - duration, endValue: time }
                            ];
                        }

                        chart.setOption(updateObj);
                    }
                }
            }

            const valueText = panel.querySelector('.modal-value-text');
            if (valueText) valueText.textContent = metric.value;

        } catch (e) {
            console.error('Modal live metrics fetch error:', e);
        }
    });
})();