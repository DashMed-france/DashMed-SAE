<?php

namespace modules\views\pages\Monitoring;

class MonitoringView
{
    private array $patientMetrics;

    public function __construct(array $patientMetrics = [])
    {
        $this->patientMetrics = $patientMetrics;
    }

    public function show(): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="fr">

        <head>
            <meta charset="UTF-8">
            <title>DashMed - Monitoring</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/monitoring.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">

            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/components/popup.css">
            <link rel="stylesheet" href="assets/css/components/aside/patient-infos.css">
            <link rel="stylesheet" href="assets/css/components/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/components/modal.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <?php include dirname(__DIR__, 2) . '/components/sidebar.php'; ?>

            <main class="container">
                <section class="dashboard-content-container">

                    <?php include dirname(__DIR__, 2) . '/components/searchbar.php'; ?>

                    <section class="cards-container">
                        <?php if (!empty($this->patientMetrics)): ?>
                            <?php foreach ($this->patientMetrics as $row): ?>
                                <?php
                                $row = $row['view_data'] ?? [];

                                $display = $row['display_name'] ?? '—';
                                $value = $row['value'] ?? '';
                                $unit = $row['unit'] ?? '';
                                $timeISO = $row['time_iso'] ?? '';
                                $time = $row['time_formatted'] ?? '—';

                                $slug = $row['slug'] ?? 'param';
                                $chartType = $row['chart_type'] ?? 'line';

                                // Classes et Labels pré-calculés
                                $stateLabel = $row['state_label'] ?? '—';
                                $stateClass = $row['card_class'] ?? '';
                                $stateClassModal = $row['modal_class'] ?? '';
                                $critFlag = $row['is_crit_flag'] ?? false;

                                // Données JSON ChartJS
                                $chartConfig = $row['chart_config'] ?? '{}';
                                $chartAllowed = $row['chart_allowed'] ?? ['line'];

                                // Données pour les attributs data-* (HTML entities)
                                $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
                                ?>

                                <article class="card <?= $stateClass ?>" data-display="<?= $h($display) ?>"
                                    data-value="<?= $h($value) ?>" data-crit="<?= $critFlag ? '1' : '0' ?>"
                                    data-detail-id="<?= $h('detail-' . $slug) ?>" data-slug="<?= $h($slug) ?>"
                                    data-chart='<?= $h($chartConfig) ?>'>


                                    <h3><?= $h($display) ?></h3>
                                    <p class="value"><?= $h($value) ?><?= $unit ? ' ' . $h($unit) : '' ?></p>
                                    <?php if ($critFlag): ?>
                                        <p class="tag tag--danger">Valeur critique 🚨</p><?php endif; ?>
                                </article>

                                <div id="detail-<?= $h($slug) ?>" style="display:none">
                                    <div id="panel-<?= $h($slug) ?>" class="modal-grid" data-idx="0" data-unit="<?= $h($unit) ?>"
                                        data-chart="<?= $h($chartType) ?>" data-chart-allowed="<?= $h(json_encode($chartAllowed)) ?>">

                                        <div class="row">
                                            <h2 class="modal-title"><?= $h($display) ?></h2>
                                            <form method="POST" action="">
                                                <input type="hidden" name="parameter_id" value="<?= $h($row['parameter_id'] ?? '') ?>">
                                                <select name="chart_type" onchange="this.form.submit()">
                                                    <?php
                                                    $allowed = $chartAllowed;
                                                    $labels = [
                                                        'line' => 'Ligne',
                                                        'bar' => 'Barres',
                                                        'pie' => 'Camembert',
                                                        'doughnut' => 'Donut',
                                                        'scatter' => 'Nuage',
                                                        'value' => 'Valeur seule'
                                                    ];
                                                    ?>
                                                    <?php foreach ($allowed as $c): ?>
                                                        <option value="<?= $h($c) ?>" <?= $c === $chartType ? 'selected' : '' ?>>
                                                            <?= $h($labels[$c] ?? ucfirst($c)) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="hidden" name="chart_pref_submit" value="1">
                                            </form>
                                        </div>
                                        <div class="row">
                                            <p class="modal-tactical-informations">
                                                <span class="modal-value"><?= $h($value) ?><?= $unit ? ' ' . $h($unit) : '' ?></span>
                                                — <span data-field="time"
                                                    data-time="<?= $h($timeISO) ?>"><?= $time ? $h($time) : '—' ?></span>
                                            </p>
                                            <p class="modal-state <?= $h($stateClassModal) ?>" data-field="state"><?= $h($stateLabel) ?>
                                            </p>
                                        </div>

                                        <canvas class="modal-chart" data-id="modal-chart-<?= $h($slug) ?>"></canvas>


                                        <div class="row">
                                            <button type="button" class="nav-btn" data-panel="<?= $h('panel-' . $slug) ?>"
                                                data-chart="<?= $h('modal-chart-' . $slug) ?>" data-title="<?= $h($display) ?>"
                                                data-step="1">
                                                ◀︎ Précédente
                                            </button>

                                            <button type="button" class="nav-btn" data-panel="<?= $h('panel-' . $slug) ?>"
                                                data-chart="<?= $h('modal-chart-' . $slug) ?>" data-title="<?= $h($display) ?>"
                                                data-step="-1">
                                                Suivante ▶︎
                                            </button>
                                        </div>

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
            <script src="assets/js/component/modal/navigation.js"></script>
            <script src="assets/js/component/modal/modal.js"></script>
        </body>

        </html>
        <?php
    }
}