<?php

/**
 * Composant affichant les cartes de monitoring.
 *
 * Variables attendues :
 * - $patientMetrics : array - Liste des métriques à afficher
 * - $chartTypes : array - Configuration des types de graphiques
 * - $userLayout : array - Préférences de layout utilisateur
 * - $idPrefix : string (optionnel) - Préfixe pour les IDs
 * - $useCustomLayout : bool (optionnel) - Applique les dimensions/positions personnalisées
 */

declare(strict_types=1);

$idPrefix = $idPrefix ?? '';
$useCustomLayout = $useCustomLayout ?? false;
$useCustomSize = $useCustomSize ?? false;

$DEFAULT_WIDTH = 4;
$DEFAULT_HEIGHT = 3;
$WIDGETS_PER_ROW = 3;

$layoutMap = [];
if (!empty($userLayout)) {
    foreach ($userLayout as $layoutItem) {
        $layoutMap[$layoutItem['parameter_id']] = $layoutItem;
    }
}

$useDefaultLayout = empty($layoutMap);
$defaultLayoutIndex = 0;

$escape = static fn(mixed $value): string => htmlspecialchars(
    is_scalar($value) ? (string) $value : '',
    ENT_QUOTES,
    'UTF-8'
);

if (!empty($patientMetrics)) : ?>
    <?php foreach ($patientMetrics as $row) : ?>
        <?php
        $viewData = $row['view_data'] ?? [];
        $parameterId = $row['parameter_id'] ?? '';

        $display = $viewData['display_name'] ?? '—';
        $value = $viewData['value'] ?? '';
        $unit = $viewData['unit'] ?? '';
        $slug = $viewData['slug'] ?? 'param';
        $chartType = $viewData['chart_type'] ?? 'line';
        $stateClass = $viewData['card_class'] ?? '';
        $critFlag = (bool) ($viewData['is_crit_flag'] ?? false);
        $chartConfig = $viewData['chart_config'] ?? '{}';
        $chartAllowed = $viewData['chart_allowed'] ?? ['line'];

        $dmax = $viewData['view_limits']['max'] ?? null;
        $nmax = $viewData['thresholds']['nmax'] ?? null;
        $cmax = $viewData['thresholds']['cmax'] ?? null;
        $gaugeMax = str_contains((string) $unit, '%') ? 100 : (
            is_numeric($dmax) ? (float) $dmax : (
                is_numeric($nmax) ? (float) $nmax : (
                    is_numeric($cmax) ? (float) $cmax : 100
                )
            )
        );

        $isValueOnly = ($chartType === 'value');
        if ($isValueOnly) {
            $stateClass .= ' card--value-only';
        }

        $gridStyle = '';
        $layout = $layoutMap[$parameterId] ?? null;
        $w = max(1, (int) ($layout['grid_w'] ?? $DEFAULT_WIDTH));
        $h = max(1, (int) ($layout['grid_h'] ?? $DEFAULT_HEIGHT));

        if ($useCustomLayout) {
            if ($layout !== null) {
                $x = (int) ($layout['grid_x'] ?? 0);
                $y = (int) ($layout['grid_y'] ?? 0);
                $gridStyle = sprintf(
                    'grid-column: %d / span %d; grid-row: %d / span %d;',
                    $x + 1,
                    $w,
                    $y + 1,
                    $h
                );
            } elseif ($useDefaultLayout) {
                $x = ($defaultLayoutIndex % $WIDGETS_PER_ROW) * $DEFAULT_WIDTH;
                $y = (int) floor($defaultLayoutIndex / $WIDGETS_PER_ROW) * $DEFAULT_HEIGHT;
                $gridStyle = sprintf(
                    'grid-column: %d / span %d; grid-row: %d / span %d;',
                    $x + 1,
                    $DEFAULT_WIDTH,
                    $y + 1,
                    $DEFAULT_HEIGHT
                );
                $defaultLayoutIndex++;
            }
        } elseif ($useCustomSize) {
            $gridStyle = sprintf('grid-column: auto / span %d; grid-row: auto / span %d;', $w, $h);
        }
        ?>

        <article class="card <?= $stateClass ?>" style="<?= $gridStyle ?>" data-display="<?= $escape($display) ?>"
            data-value="<?= $escape($value) ?>" data-crit="<?= $critFlag ? '1' : '0' ?>"
            data-detail-id="<?= $escape($idPrefix . 'detail-' . $slug) ?>" data-slug="<?= $escape($slug) ?>"
            data-chart='<?= $escape($chartConfig) ?>' data-chart-type="<?= $escape($chartType) ?>"
            data-max="<?= $escape($gaugeMax) ?>" data-dmin="<?= $escape($viewData['view_limits']['min'] ?? '') ?>"
            data-dmax="<?= $escape($viewData['view_limits']['max'] ?? '') ?>"
            data-nmin="<?= $escape($viewData['thresholds']['nmin'] ?? '') ?>"
            data-nmax="<?= $escape($viewData['thresholds']['nmax'] ?? '') ?>"
            data-cmin="<?= $escape($viewData['thresholds']['cmin'] ?? '') ?>"
            data-cmax="<?= $escape($viewData['thresholds']['cmax'] ?? '') ?>">

            <div class="card-header">
                <h3>
                    <?= $escape($display) ?>
                </h3>
                <?php if (!$isValueOnly) : ?>
                    <p class="value">
                        <?= $escape($value) ?>
                        <?= $unit !== '' ? ' ' . $escape($unit) : '' ?>
                    </p>
                <?php endif; ?>

            </div>

            <?php if ($isValueOnly) : ?>
                <div class="card-value-only-container">
                    <p class="big-value">
                        <?= $escape($value) ?>
                    </p>
                    <p class="unit">
                        <?= $escape($unit) ?>
                    </p>
                </div>
            <?php else : ?>
                <div class="card-spark">
                    <canvas class="card-spark-canvas" id="<?= $escape($idPrefix) ?>spark-<?= $escape($slug) ?>">
                    </canvas>
                    <div class="no-data-placeholder" style="display:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 60" class="no-data-svg">
                            <path d="M10 45 L25 35 L40 40 L55 25 L70 30 L85 20" stroke="currentColor"
                                  stroke-width="2" fill="none"
                                stroke-dasharray="4,3" opacity="0.3" />
                            <circle cx="50" cy="35" r="12" fill="none" stroke="currentColor"
                                    stroke-width="2" opacity="0.4" />
                            <line x1="45" y1="30" x2="55" y2="40" stroke="currentColor"
                                  stroke-width="2" opacity="0.4" />
                            <line x1="55" y1="30" x2="45" y2="40" stroke="currentColor"
                                  stroke-width="2" opacity="0.4" />
                        </svg>
                        <span class="no-data-text">Aucune donnée</span>
                    </div>
                </div>
            <?php endif; ?>

            <ul class="card-spark-data" data-spark style="display:none">
                <?php
                $history = $viewData['history_html_data'] ?? [];
                foreach ($history as $historyItem) :
                    ?>
                    <li data-time="<?= $escape($historyItem['time_iso'] ?? '') ?>"
                        data-value="<?= $escape($historyItem['value'] ?? '') ?>"
                        data-flag="<?= $escape($historyItem['flag'] ?? '') ?>"></li>
                <?php endforeach; ?>
            </ul>
        </article>

        <div id="<?= $escape($idPrefix) ?>detail-<?= $escape($slug) ?>" style="display:none">
            <div id="<?= $escape($idPrefix) ?>panel-<?= $escape($slug) ?>" class="modal-grid" data-idx="0"
                data-unit="<?= $escape($unit) ?>" data-chart="<?= $escape($chartType) ?>"
                data-chart-allowed="<?= $escape(json_encode($chartAllowed)) ?>"
                data-nmin="<?= $escape($viewData['thresholds']['nmin'] ?? '') ?>"
                data-nmax="<?= $escape($viewData['thresholds']['nmax'] ?? '') ?>"
                data-cmin="<?= $escape($viewData['thresholds']['cmin'] ?? '') ?>"
                data-cmax="<?= $escape($viewData['thresholds']['cmax'] ?? '') ?>"
                data-dmin="<?= $escape($viewData['view_limits']['min'] ?? '') ?>"
                data-dmax="<?= $escape($viewData['view_limits']['max'] ?? '') ?>"
                data-display="<?= $escape($display) ?>"
                data-value="<?= $escape($value) ?>" data-unit-raw="<?= $escape($unit) ?>">

                <div class="modal-header-row">
                    <h2 class="modal-title">
                        <?= $escape($display) ?>
                    </h2>
                    <div class="modal-header-center">
                        <form method="POST" action="" class="modal-form">
                            <input type="hidden" name="parameter_id" value="<?= $escape($parameterId) ?>">
                            <select name="chart_type" class="modal-select" onchange="this.form.submit()">
                                <?php foreach ($chartAllowed as $allowedType) : ?>
                                    <option
                                        value="<?= $escape($allowedType) ?>"
                                        <?= $allowedType === $chartType ? 'selected' : '' ?>>
                                        <?= $escape($chartTypes[$allowedType] ?? ucfirst($allowedType)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="chart_pref_submit" value="1">
                        </form>
                    </div>
                    <select class="modal-select modal-timerange-select">
                        <option value="1">1 min</option>
                        <option value="5">5 min</option>
                        <option value="15" selected>15 min</option>
                        <option value="30">30 min</option>
                        <option value="60">1h</option>
                        <option value="120">2h</option>
                        <option value="240">4h</option>
                        <option value="480">8h</option>
                        <option value="720">12h</option>
                    </select>
                </div>

                <div class="modal-value-only">
                    <div class="modal-value-only-content">
                        <span class="modal-value-text"></span>
                        <span class="modal-unit-text"></span>
                    </div>
                </div>

                <canvas class="modal-chart chart-<?= $escape($chartType) ?>" tabindex="-1"
                    data-id="<?= $escape($idPrefix) ?>modal-chart-<?= $escape($slug) ?>"></canvas>

                <div class="modal-no-data-placeholder" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 120" class="no-data-svg-modal">
                        <path d="M20 90 L50 70 L80 80 L110 50 L140 60 L170 40" stroke="currentColor" stroke-width="3"
                            fill="none" stroke-dasharray="8,6" opacity="0.3" />
                        <circle cx="100" cy="65" r="25" fill="none" stroke="currentColor"
                                stroke-width="3" opacity="0.4" />
                        <line x1="90" y1="55" x2="110" y2="75" stroke="currentColor"
                              stroke-width="3" opacity="0.4" />
                        <line x1="110" y1="55" x2="90" y2="75" stroke="currentColor"
                              stroke-width="3" opacity="0.4" />
                    </svg>
                    <span class="no-data-text-modal">Aucune donnée disponible</span>
                </div>

                <ul data-hist style="display:none">
                    <?php foreach ($viewData['history_html_data'] ?? [] as $historyItem) : ?>
                        <li data-time="<?= $escape($historyItem['time_iso'] ?? '') ?>"
                            data-value="<?= $escape($historyItem['value'] ?? '') ?>"
                            data-flag="<?= $escape($historyItem['flag'] ?? '') ?>"></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>

<?php else : ?>
    <article class="card">
        <h3>Aucune donnée</h3>
        <p class="value">—</p>
    </article>
<?php endif; ?>