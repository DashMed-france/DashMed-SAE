<?php

/**
 * Composant de cartes de monitoring.
 * Attend $patientMetrics (array) comme variable.
 */

if (!empty($patientMetrics)): ?>
    <?php foreach ($patientMetrics as $row): ?>
        <?php
        $row = $row['view_data'] ?? [];

        $display = $row['display_name'] ?? '—';
        $value = $row['value'] ?? '';
        $unit = $row['unit'] ?? '';
        $timeISO = $row['time_iso'] ?? '';
        $time = $row['time_formatted'] ?? '—';

        $slug = $row['slug'] ?? 'param';
        $chartType = $row['chart_type'] ?? 'line';

        $stateLabel = $row['state_label'] ?? '—';
        $stateClass = $row['card_class'] ?? '';
        $stateClassModal = $row['modal_class'] ?? '';
        $critFlag = $row['is_crit_flag'] ?? false;

        $chartConfig = $row['chart_config'] ?? '{}';
        $chartAllowed = $row['chart_allowed'] ?? ['line'];

        $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        ?>

        <?php
        $dmax = $row['view_limits']['max'] ?? null;
        $nmax = $row['thresholds']['nmax'] ?? null;
        $cmax = $row['thresholds']['cmax'] ?? null;

        $gaugeMax = 100;
        if (str_contains($unit, '%')) {
            $gaugeMax = 100;
        } elseif (is_numeric($dmax)) {
            $gaugeMax = $dmax;
        } elseif (is_numeric($nmax)) {
            $gaugeMax = $nmax;
        } elseif (is_numeric($cmax)) {
            $gaugeMax = $cmax;
        }

        $isValueOnly = ($chartType === 'value');
        if ($isValueOnly) {
            $stateClass .= ' card--value-only';
        }
        ?>

        <article class="card <?= $stateClass ?>" data-display="<?= $h($display) ?>" data-value="<?= $h($value) ?>"
            data-crit="<?= $critFlag ? '1' : '0' ?>" data-detail-id="<?= $h('detail-' . $slug) ?>" data-slug="<?= $h($slug) ?>"
            data-chart='<?= $h($chartConfig) ?>' data-chart-type="<?= $h($chartType) ?>" data-max="<?= $h($gaugeMax) ?>"
            data-dmin="<?= $h($row['view_limits']['min'] ?? '') ?>" data-dmax="<?= $h($row['view_limits']['max'] ?? '') ?>"
            data-nmin="<?= $h($row['thresholds']['nmin'] ?? '') ?>" data-nmax="<?= $h($row['thresholds']['nmax'] ?? '') ?>"
            data-cmin="<?= $h($row['thresholds']['cmin'] ?? '') ?>" data-cmax="<?= $h($row['thresholds']['cmax'] ?? '') ?>">

            <div class="card-header">
                <h3><?= $h($display) ?></h3>
                <?php if (!$isValueOnly): ?>
                    <p class="value"><?= $h($value) ?><?= $unit ? ' ' . $h($unit) : '' ?></p>
                <?php endif; ?>
                <?php if ($critFlag): ?>
                    <p class="tag tag--danger">Valeur critique 🚨</p>
                <?php endif; ?>
            </div>

            <?php if ($isValueOnly): ?>
                <div class="card-value-only-container">
                    <p class="big-value"><?= $h($value) ?></p>
                    <p class="unit"><?= $h($unit) ?></p>
                </div>
            <?php else: ?>
                <div class="card-spark">
                    <canvas class="card-spark-canvas" id="spark-<?= $h($slug) ?>"></canvas>
                </div>
            <?php endif; ?>

            <ul class="card-spark-data" data-spark style="display:none">
                <?php
                $hist = $row['history_html_data'] ?? [];
                $hist = array_slice($hist, -24);
                foreach ($hist as $hData):
                    ?>
                    <li data-time="<?= $h($hData['time_iso']) ?>" data-value="<?= $h($hData['value']) ?>"
                        data-flag="<?= $h($hData['flag']) ?>"></li>
                <?php endforeach; ?>
            </ul>
        </article>


        <div id="detail-<?= $h($slug) ?>" style="display:none">
            <div id="panel-<?= $h($slug) ?>" class="modal-grid" data-idx="0" data-unit="<?= $h($unit) ?>"
                data-chart="<?= $h($chartType) ?>" data-chart-allowed="<?= $h(json_encode($chartAllowed)) ?>"
                data-nmin="<?= $h($row['thresholds']['nmin'] ?? '') ?>" data-nmax="<?= $h($row['thresholds']['nmax'] ?? '') ?>"
                data-cmin="<?= $h($row['thresholds']['cmin'] ?? '') ?>" data-cmax="<?= $h($row['thresholds']['cmax'] ?? '') ?>"
                data-dmin="<?= $h($row['view_limits']['min'] ?? '') ?>" data-dmax="<?= $h($row['view_limits']['max'] ?? '') ?>"
                data-display="<?= $h($display) ?>" data-value="<?= $h($value) ?>" data-unit-raw="<?= $h($unit) ?>">

                <div class="modal-header-row">
                    <h2 class="modal-title"><?= $h($display) ?></h2>
                    <div class="modal-header-center">
                        <form method="POST" action="" class="modal-form">
                            <input type="hidden" name="parameter_id" value="<?= $h($row['parameter_id'] ?? '') ?>">
                            <select name="chart_type" class="modal-select" onchange="this.form.submit()">
                                <?php
                                $allowed = $chartAllowed;
                                $availableLabels = $chartTypes ?? [];
                                ?>
                                <?php foreach ($allowed as $c): ?>
                                    <option value="<?= $h($c) ?>" <?= $c === $chartType ? 'selected' : '' ?>>
                                        <?= $h($availableLabels[$c] ?? ucfirst($c)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="chart_pref_submit" value="1">
                        </form>
                    </div>
                </div>

                <div class="modal-value-only">
                    <div class="modal-value-only-content">
                        <span class="modal-value-text"></span>
                        <span class="modal-unit-text"></span>
                    </div>
                </div>

                <canvas class="modal-chart" data-id="modal-chart-<?= $h($slug) ?>"></canvas>

                <ul data-hist style="display:none">
                    <?php foreach ($row['history_html_data'] ?? [] as $hData): ?>
                        <li data-time="<?= $h($hData['time_iso']) ?>" data-value="<?= $h($hData['value']) ?>"
                            data-flag="<?= $h($hData['flag']) ?>"></li>
                    <?php endforeach; ?>
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