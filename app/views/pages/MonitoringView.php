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
            <link rel="stylesheet" href="assets/css/components/card2.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/components/popup.css">
            <link rel="stylesheet" href="assets/css/components/aside/patient-infos.css">
            <link rel="stylesheet" href="assets/css/components/aside/doctor-list.css">
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
                                $paramId = (string) ($row['parameter_id'] ?? '');
                                $display = (string) ($row['display_name'] ?? $paramId);
                                $value = (string) ($row['value'] ?? '');
                                $valNum = is_numeric($row['value'] ?? null) ? (float) $row['value'] : null;

                                $timeRaw = $row['timestamp'] ?? null;
                                $timeISO = $timeRaw ? date('c', strtotime($timeRaw)) : null;
                                $time = $timeRaw ? date('H:i', strtotime($timeRaw)) : null;

                                $critFlag = !empty($row['alert_flag']) && (int) $row['alert_flag'] === 1;

                                $unit = $row['unit'] ?? '';
                                $desc = $row['description'] ?? '—';
                                $nmin = isset($row['normal_min']) ? (float) $row['normal_min'] : null;
                                $nmax = isset($row['normal_max']) ? (float) $row['normal_max'] : null;
                                $cmin = isset($row['critical_min']) ? (float) $row['critical_min'] : null;
                                $cmax = isset($row['critical_max']) ? (float) $row['critical_max'] : null;
                                $dmin = isset($row['display_min']) ? (float) $row['display_min'] : null;
                                $dmax = isset($row['display_max']) ? (float) $row['display_max'] : null;

                                $history = $row['history'] ?? [];

                                $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
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
                                            $width = $nmax - $nmin;
                                            $margin = 0.10 * $width;
                                            if ($valNum >= $nmin && $valNum <= $nmax) {
                                                if (($valNum - $nmin) <= $margin || ($nmax - $valNum) <= $margin) {
                                                    $nearEdge = true;
                                                }
                                            }
                                        }

                                        if (!$inNormal || $nearEdge) {
                                            $stateLabel = 'Prévention d\'alerte ⚠️';
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
                                $chartType = 'line'; // Type forcé à changer plus tard par la requête
                                ?>

                                <article class="card <?= $stateClass ?>" data-display="<?= $h($display) ?>"
                                    data-value="<?= $h($value) ?>" data-crit="<?= $critFlag ? '1' : '0' ?>"
                                    data-detail-id="<?= $h('detail-' . $slug) ?>" data-slug="<?= $h($slug) ?>" data-chart='<?= $h(json_encode([
                                              "type" => $chartType,
                                              "title" => $display,
                                              "labels" => array_map(
                                                  fn($hrow) => date("H:i", strtotime($hrow["timestamp"] ?? "now")),
                                                  $row["history"] ?? []
                                              ),
                                              "data" => array_map(
                                                  fn($hrow) => (float) ($hrow["value"] ?? 0),
                                                  $row["history"] ?? []
                                              ),
                                              "target" => "modal-chart-" . $slug,
                                              "color" => "#4f46e5",
                                              "thresholds" => [
                                                  "nmin" => $nmin,
                                                  "nmax" => $nmax,
                                                  "cmin" => $cmin,
                                                  "cmax" => $cmax
                                              ],
                                              "view" => [
                                                  "min" => $dmin,
                                                  "max" => $dmax
                                              ],
                                          ])) ?>'>


                                    <h3><?= $h($display) ?></h3>
                                    <p class="value"><?= $h($value) ?><?= $unit ? ' ' . $h($unit) : '' ?></p>
                                    <?php if ($critFlag): ?>
                                        <p class="tag tag--danger">Valeur critique 🚨</p><?php endif; ?>
                                </article>

                                <div id="detail-<?= $h($slug) ?>" style="display:none">
                                    <div id="panel-<?= $h($slug) ?>" class="modal-grid" data-idx="0" data-unit="<?= $h($unit) ?>"
                                        data-nmin="<?= $nmin !== null ? $h($nmin) : '' ?>"
                                        data-nmax="<?= $nmax !== null ? $h($nmax) : '' ?>"
                                        data-cmin="<?= $cmin !== null ? $h($cmin) : '' ?>"
                                        data-cmax="<?= $cmax !== null ? $h($cmax) : '' ?>"
                                        data-dmin="<?= $dmin !== null ? $h($dmin) : '' ?>"
                                        data-dmax="<?= $dmax !== null ? $h($dmax) : '' ?>" data-chart="<?= $h($chartType) ?>"
                                        data-chart-allowed="<?= $h(json_encode($row['chart_allowed'] ?? ['line', 'bar', 'pie'])) ?>">

                                        <div class="row">
                                            <h2 class="modal-title"><?= $h($display) ?></h2>
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
                                            <?php
                                            $printedAny = false;
                                            foreach (($row['history'] ?? []) as $i => $hrow):
                                                $hVal = (string) ($hrow['value'] ?? '');
                                                $hTimeRaw = $hrow['timestamp'] ?? null;
                                                $hTimeISO = $hTimeRaw ? date('c', strtotime($hTimeRaw)) : null;
                                                $hFlag = (int) ($hrow['alert_flag'] ?? 0);
                                                $printedAny = true;
                                                ?>
                                                <li data-time="<?= $hTimeISO ? $h($hTimeISO) : '' ?>" data-value="<?= $h($hVal) ?>"
                                                    data-flag="<?= $hFlag === 1 ? '1' : '0' ?>"></li>
                                            <?php endforeach; ?>
                                            <?php if (!$printedAny): ?>
                                                <li data-time="<?= $timeISO ? $h($timeISO) : '' ?>" data-value="<?= $h($value) ?>"
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
            <script src="assets/js/component/modal/navigation.js"></script>
            <script src="assets/js/component/modal/modal.js"></script>
        </body>

        </html>
        <?php
    }
}