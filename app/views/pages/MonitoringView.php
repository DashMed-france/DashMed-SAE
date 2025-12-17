<?php

namespace modules\views\pages;

class MonitoringView
{
    private array $consultationsPassees;
    private array $consultationsFutures;
    private array $metrics;

    public function __construct(array $consultationsPassees = [], array $consultationsFutures = [], array $metrics = [])
    {
        $this->consultationsPassees = $consultationsPassees;
        $this->consultationsFutures = $consultationsFutures;
        $this->metrics = $metrics;
    }

    public function show(): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>DashMed - Dashboard</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/monitoring.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/card2.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/components/popup.css">
            <link rel="stylesheet" href="assets/css/components/aside/calendar.css">
            <link rel="stylesheet" href="assets/css/components/aside/patient-infos.css">
            <link rel="stylesheet" href="assets/css/components/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/components/aside/Evenement.css">
            <link rel="stylesheet" href="assets/css/components/modal.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>
        <body>
        <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

        <main class="container">
            <section class="dashboard-content-container">

                <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>

                <section class="cards-container">
                    <?php if (!empty($this->metrics)): ?>
                        <?php foreach ($this->metrics as $row): ?>
                            <?php
                            $paramId = (string)($row['parameter_id'] ?? '');
                            $display = (string)($row['display_name'] ?? $paramId);
                            $value   = (string)($row['value'] ?? '');
                            $valNum  = is_numeric($row['value'] ?? null) ? (float)$row['value'] : null;

                            $timeRaw = $row['timestamp'] ?? null;
                            $timeISO = $timeRaw ? date('c', strtotime($timeRaw)) : null;
                            $time    = $timeRaw ? date('H:i', strtotime($timeRaw)) : null;

                            $critFlag = !empty($row['alert_flag']) && (int)$row['alert_flag'] === 1;

                            $unit    = $row['unit'] ?? '';
                            $desc    = $row['description'] ?? '—';
                            $nmin    = isset($row['normal_min']) ? (float)$row['normal_min'] : null;
                            $nmax    = isset($row['normal_max']) ? (float)$row['normal_max'] : null;
                            $cmin    = isset($row['critical_min']) ? (float)$row['critical_min'] : null;
                            $cmax    = isset($row['critical_max']) ? (float)$row['critical_max'] : null;
                            $dmin    = isset($row['display_min']) ? (float)$row['display_min'] : null;
                            $dmax    = isset($row['display_max']) ? (float)$row['display_max'] : null;

                            $history = $row['history'] ?? [];

                            $h    = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
                            $slug = preg_replace('/[^a-zA-Z0-9_-]/', '-', $display);

                            $stateLabel = '—';
                            $stateClass = '';
                            $stateClassModal = '';

                            if ($valNum === null) {
                                $stateLabel = '—';
                            } else {
                                $isCritical = $critFlag
                                        || ($cmin !== null && $valNum <= $cmin)
                                        || ($cmax !== null && $valNum >= $cmax);

                                if ($isCritical) {
                                    $stateLabel = 'Constante critique 🚨';
                                    $stateClass = 'card--alert';
                                } else {
                                    $inNormal = ($nmin !== null && $nmax !== null)
                                            ? ($valNum >= $nmin && $valNum <= $nmax)
                                            : true;

                                    $nearEdge = false;
                                    if ($nmin !== null && $nmax !== null && $nmax > $nmin) {
                                        $width  = $nmax - $nmin;
                                        $margin = 0.10 * $width;
                                        if ($valNum >= $nmin && $valNum <= $nmax) {
                                            if (($valNum - $nmin) <= $margin || ($nmax - $valNum) <= $margin) {
                                                $nearEdge = true;
                                            }
                                        }
                                    }

                                    if (!$inNormal || $nearEdge) {
                                        $stateLabel = 'Prévention d’alerte ⚠️';
                                        $stateClass = 'card--warn';
                                    } else {
                                        $stateLabel = 'Constante normale ✅';
                                    }
                                }
                            }
                            if (str_contains($stateLabel, 'critique')) {
                                $stateClassModal = 'alert';
                            } elseif (str_contains($stateLabel, 'Prévention') || str_contains($stateLabel, '⚠️')) {
                                $stateClassModal = 'warn';
                            } elseif (str_contains($stateLabel, 'normale') || str_contains($stateLabel, 'stable')) {
                                $stateClassModal = 'stable';
                            }

                            $slug = strtolower(trim(preg_replace('/\s+/', '-', $display)));
                            ?>

                            <article
                                    class="card <?= $stateClass ?>"
                                    onclick='
                                            openModal(<?= json_encode($display) ?>, <?= json_encode($value) ?>, <?= $critFlag ? "true" : "false" ?>);
                                            (function(){
                                            var detailsSrc = document.getElementById(<?= json_encode("detail-" . $slug) ?>);
                                            var modalDetails = document.getElementById("modalDetails");
                                            modalDetails.innerHTML = detailsSrc ? detailsSrc.innerHTML : "<p>Aucun détail disponible.</p>";

                                            var canvas = modalDetails.querySelector(".modal-chart");
                                            if (!canvas) return;
                                            canvas.id = canvas.dataset.id;

                                            createChart(
                                            "line",
                                    <?= json_encode($display) ?>,
                                    <?= json_encode(array_map(fn($hrow) => date("H:i", strtotime($hrow["timestamp"] ?? "now")), $row['history'] ?? [])) ?>,
                                    <?= json_encode(array_map(fn($hrow) => (float)($hrow["value"] ?? 0), $row["history"] ?? [])) ?>,
                                    <?= json_encode("modal-chart-" . $slug) ?>,
                                            "#4f46e5",
                                            {
                                            nmin: <?= $nmin !== null ? json_encode((float)$nmin) : "null" ?>,
                                            nmax: <?= $nmax !== null ? json_encode((float)$nmax) : "null" ?>,
                                            cmin: <?= $cmin !== null ? json_encode((float)$cmin) : "null" ?>,
                                            cmax: <?= $cmax !== null ? json_encode((float)$cmax) : "null" ?>
                                            },
                                            {
                                            min: <?= $dmin !== null ? json_encode((float)$dmin) : "null" ?>,
                                            max: <?= $dmax !== null ? json_encode((float)$dmax) : "null" ?> }
                                            );
                                            })();
                                            '
                            >
                                <h3><?= $h($display) ?></h3>
                                <p class="value"><?= $h($value) ?><?= $unit ? ' ' . $h($unit) : '' ?></p>
                                <?php if ($critFlag): ?><p class="tag tag--danger">Valeur critique 🚨</p><?php endif; ?>
                            </article>

                            <div id="detail-<?= $h($slug) ?>" style="display:none">
                                <div id="panel-<?= $h($slug) ?>"
                                     class="modal-grid"
                                     data-idx="0"
                                     data-unit="<?= $h($unit) ?>"
                                     data-nmin="<?= $nmin !== null ? $h($nmin) : '' ?>"
                                     data-nmax="<?= $nmax !== null ? $h($nmax) : '' ?>"
                                     data-cmin="<?= $cmin !== null ? $h($cmin) : '' ?>"
                                     data-cmax="<?= $cmax !== null ? $h($cmax) : '' ?>"
                                >
                                    <div class="row">
                                        <h2 class="modal-title"><?= $h($display) ?></h2>
                                    </div>
                                    <div class="row">
                                        <p class="modal-tactical-informations">
                                            <span class="modal-value"><?= $h($value) ?><?= $unit ? ' '.$h($unit) : '' ?></span>
                                            — <span data-field="time" data-time="<?= $h($timeISO) ?>"><?= $time ? $h($time) : '—' ?></span>
                                        </p>
                                        <p class="modal-state <?= $h($stateClassModal) ?>" data-field="state"><?= $h($stateLabel) ?></p>
                                    </div>

                                    <canvas class="modal-chart" data-id="modal-chart-<?= $h($slug) ?>"></canvas>


                                    <div class="row">
                                        <button type="button"
                                                onclick="
                                                        (function(){
                                                        var root=document.getElementById('modalDetails');
                                                        var c=root.querySelector('#panel-<?= $h($slug) ?>');
                                                        if(!c) return;
                                                        var list=c.querySelectorAll('ul[data-hist]>li');
                                                        if(!list.length) return;
                                                        var idx=parseInt(c.getAttribute('data-idx')||'0',10)+1;
                                                        if(idx>=list.length) idx=list.length-1;
                                                        c.setAttribute('data-idx', idx);
                                                        var it=list[idx];
                                                        var time=it.dataset.time||'';
                                                        var val=it.dataset.value||'';
                                                        var flag=it.dataset.flag==='1';

                                                        var timeEl=c.querySelector('[data-field=time]');
                                                        if(timeEl){
                                                        timeEl.setAttribute('data-time', time);
                                                        timeEl.textContent = formatTime(time);
                                                        }

                                                        var nmin=parseFloat(c.dataset.nmin), nmax=parseFloat(c.dataset.nmax),
                                                        cmin=parseFloat(c.dataset.cmin), cmax=parseFloat(c.dataset.cmax);
                                                        var num=parseFloat(val);
                                                        var state='—';
                                                        if(!isNaN(num)){
                                                        var isCrit = flag || (!isNaN(cmin)&&num<=cmin) || (!isNaN(cmax)&&num>=cmax);
                                                        if(isCrit){ state='Constante critique 🚨'; }
                                                        else{
                                                        var inNorm = (!isNaN(nmin)&&!isNaN(nmax)) ? (num>=nmin && num<=nmax) : true;
                                                        var near=false;
                                                        if(!isNaN(nmin)&&!isNaN(nmax)&&nmax>nmin){
                                                        var w=nmax-nmin, m=0.10*w;
                                                        if(num>=nmin&&num<=nmax){
                                                        if((num-nmin)<=m || (nmax-num)<=m) near=true;
                                                        }
                                                        }
                                                        state = (!inNorm || near) ? 'Prévention d’alerte ⚠️' : 'Constante normale ✅';
                                                        }
                                                        }
                                                        c.querySelector('[data-field=state]').textContent=state;

                                                        const stateEl = c.querySelector('[data-field=state]');
                                                        if (stateEl) {
                                                        stateEl.className = 'modal-state';
                                                        if (state.includes('critique')) stateEl.classList.add('alert');
                                                        else if (state.includes('Prévention') || state.includes('⚠️')) stateEl.classList.add('warn');
                                                        else if (state.includes('normale') || state.includes('stable')) stateEl.classList.add('stable');
                                                        }

                                                        var unit=c.dataset.unit||'';
                                                        var valueEl = document.getElementById('modalDetails').querySelector('.modal-value');
                                                        if (valueEl) valueEl.textContent = val + (unit?(' '+unit):'') + (flag?' — critique 🚨':'');
                                                        })();
                                                        ">
                                            ◀︎ Précédente
                                        </button>

                                        <button type="button"
                                                onclick="
                                                        (function(){
                                                        var root=document.getElementById('modalDetails');
                                                        var c=root.querySelector('#panel-<?= $h($slug) ?>');
                                                        if(!c) return;
                                                        var list=c.querySelectorAll('ul[data-hist]>li');
                                                        if(!list.length) return;
                                                        var idx=parseInt(c.getAttribute('data-idx')||'0',10)-1;
                                                        if(idx<0) idx=0;
                                                        c.setAttribute('data-idx', idx);
                                                        var it=list[idx];
                                                        var time=it.dataset.time||'';
                                                        var val=it.dataset.value||'';
                                                        var flag=it.dataset.flag==='1';

                                                        var timeEl=c.querySelector('[data-field=time]');
                                                        if(timeEl){
                                                        timeEl.setAttribute('data-time', time);
                                                        timeEl.textContent = formatTime(time);
                                                        }

                                                        var nmin=parseFloat(c.dataset.nmin), nmax=parseFloat(c.dataset.nmax),
                                                        cmin=parseFloat(c.dataset.cmin), cmax=parseFloat(c.dataset.cmax);
                                                        var num=parseFloat(val);
                                                        var state='—';
                                                        if(!isNaN(num)){
                                                        var isCrit = flag || (!isNaN(cmin)&&num<=cmin) || (!isNaN(cmax)&&num>=cmax);
                                                        if(isCrit){ state='Constante critique 🚨'; }
                                                        else{
                                                        var inNorm = (!isNaN(nmin)&&!isNaN(nmax)) ? (num>=nmin && num<=nmax) : true;
                                                        var near=false;
                                                        if(!isNaN(nmin)&&!isNaN(nmax)&&nmax>nmin){
                                                        var w=nmax-nmin, m=0.10*w;
                                                        if(num>=nmin&&num<=nmax){
                                                        if((num-nmin)<=m || (nmax-num)<=m) near=true;
                                                        }
                                                        }
                                                        state = (!inNorm || near) ? 'Prévention d’alerte ⚠️' : 'Constante normale ✅';
                                                        }
                                                        }
                                                        c.querySelector('[data-field=state]').textContent=state;

                                                        const stateEl = c.querySelector('[data-field=state]');
                                                        if (stateEl) {
                                                        stateEl.className = 'modal-state';
                                                        if (state.includes('critique')) stateEl.classList.add('alert');
                                                        else if (state.includes('Prévention') || state.includes('⚠️')) stateEl.classList.add('warn');
                                                        else if (state.includes('normale') || state.includes('stable')) stateEl.classList.add('stable');
                                                        }

                                                        var unit=c.dataset.unit||'';
                                                        var valueEl = document.getElementById('modalDetails').querySelector('.modal-value');
                                                        if (valueEl) valueEl.textContent = val + (unit?(' '+unit):'') + (flag?' — critique 🚨':'');
                                                        })();
                                                        ">
                                            Suivante ▶︎
                                        </button>
                                    </div>

                                    <ul data-hist style="display:none">
                                        <?php
                                        $printedAny = false;
                                        foreach (($row['history'] ?? []) as $i => $hrow):
                                            $hVal     = (string)($hrow['value'] ?? '');
                                            $hTimeRaw = $hrow['timestamp'] ?? null;
                                            $hTimeISO = $hTimeRaw ? date('c', strtotime($hTimeRaw)) : null;
                                            $hFlag    = (int)($hrow['alert_flag'] ?? 0);
                                            $printedAny = true;
                                            ?>
                                            <li data-time="<?= $hTimeISO ? $h($hTimeISO) : '' ?>"
                                                data-value="<?= $h($hVal) ?>"
                                                data-flag="<?= $hFlag === 1 ? '1' : '0' ?>"></li>
                                        <?php endforeach; ?>
                                        <?php if (!$printedAny): ?>
                                            <li data-time="<?= $timeISO ? $h($timeISO) : '' ?>"
                                                data-value="<?= $h($value) ?>"
                                                data-flag="<?= $critFlag ? '1' : '0' ?>"></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <article class="card">
                            <h3>Aucune donnée</h3>
                            <p class="value">—</p>
                        </article>
                    <?php endif; ?>
                </section>
        </main>
        <div class="modal" id="cardModal">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <div id="modalDetails"></div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="assets/js/component/modal/chart.js"></script>
        <script src="assets/js/component/modal/modal.js"></script>
        </body>
        </html>
        <?php
    }
}