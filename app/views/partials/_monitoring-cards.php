<?php

/**
 * Partial: Monitoring Cards
 *
 * Expected variables:
 * - $patientMetrics : array - List of metrics to display
 * - $chartTypes : array - Chart types configuration
 * - $userLayout : array - User layout preferences
 * - $idPrefix : string (optional) - Prefix for IDs
 * - $useCustomLayout : bool (optional) - Applies custom dimensions/positions
 *
 * @package DashMed\Views\Partials
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
        if ($row instanceof \modules\models\entities\Indicator) {
            $viewData = $row->getViewData();
            $parameterId = $row->getId();
        } else {
            $viewData = $row['view_data'] ?? [];
            $parameterId = $row['parameter_id'] ?? '';
        }

        $display = $viewData['display_name'] ?? '—';
        $description = $viewData['description'] ?? '—';
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
                    <?= $escape($display) ?><br>
                </h3>
                    <p class="value" style="display: <?= $isValueOnly ? 'none' : 'flex' ?>; align-items: center; gap: 6px;">
                        <span><?= $escape($value) ?></span>
                        <span class="unit"><?= $unit !== '' ? ' ' . $escape($unit) : '' ?></span>

                        <span class="value-status-icon status-critical" title="Critique"
                            style="color: var(--color-critical, #EF4444); display: <?= str_contains($stateClass, 'card--alert') ? 'flex' : 'none' ?>;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2
                                2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </span>
                        <span class="value-status-icon status-warning" title="Attention"
                            style="color: var(--color-warning, #F59E0B); display: <?= str_contains($stateClass, 'card--warn') ? 'flex' : 'none' ?>;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71
                                3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </span>
                    </p>

            </div>

            <!-- 
            DOM Pre-rendering for Layout Toggling:
            Both the value container and the canvas container are rendered in the HTML to allow 
            Javascript (card-sparklines.js) to dynamically toggle their visibility via CSS (display: flex/none)
            when the user switches chart types. 
            -->
            <div class="card-value-only-container" style="display: <?= $isValueOnly ? 'flex' : 'none' ?>; flex-direction: column; justify-content: center; align-items: center; height: 100%;">
                <p class="big-value">
                    <?= $escape($value) ?>
                </p>
                <p class="unit">
                    <?= $escape($unit) ?>
                </p>
            </div>

            <div class="card-spark" style="display: <?= $isValueOnly ? 'none' : 'block' ?>;">
                <canvas class="card-spark-canvas" id="<?= $escape($idPrefix) ?>spark-<?= $escape($slug) ?>">
                </canvas>
                <div class="no-data-placeholder" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 60" class="no-data-svg">
                        <path d="M10 45 L25 35 L40 40 L55 25 L70 30 L85 20" stroke="currentColor" stroke-width="2"
                              fill="none"
                            stroke-dasharray="4,3" opacity="0.3" />
                        <circle cx="50" cy="35" r="12" fill="none" stroke="currentColor" stroke-width="2"
                                opacity="0.4" />
                        <line x1="45" y1="30" x2="55" y2="40" stroke="currentColor" stroke-width="2"
                              opacity="0.4" />
                        <line x1="55" y1="30" x2="45" y2="40" stroke="currentColor" stroke-width="2"
                              opacity="0.4" />
                    </svg>
                    <span class="no-data-text">Aucune donnée</span>
                </div>
            </div>

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
                data-unit="<?= $escape($unit) ?>" data-chart="<?= $escape($viewData['modal_chart_type'] ?? $chartType) ?>"
                data-param-id="<?= $escape($parameterId) ?>"
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
                    <div class="column">
                        <h2 class="modal-title">
                            <?= $escape($display) ?>
                        </h2>
                        <p class="modal-description">
                            <?= $escape($description) ?>
                        </p>
                    </div>

                    <div class="modal-header-center">
                        <input type="datetime-local" class="modal-input modal-date-picker"
                            title="Sélectionner une date et heure (fast travel)" max="<?= date('Y-m-d\TH:i') ?>">
                    </div>

                    <div class="modal-chart-types-container" style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                        <div class="modal-chart-types">
                            <span class="chart-type-label" style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Modale</span>
                            <div class="chart-type-group">
                                <?php foreach ($chartAllowed as $allowedType) :
                                    $icon = '';
                                    switch ($allowedType) {
                                        case 'line':
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>';
                                            break;
                                        case 'bar':
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4">
</line><line x1="6" y1="20" x2="6" y2="16"></line></svg>';
                                            break;
                                        case 'scatter':
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<circle cx="7.5" cy="7.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/><circle cx="7.5" cy="16.5" r="2.5"/>
<circle cx="16.5" cy="7.5" r="2.5"/></svg>';
                                            break;
                                        case 'pie':
                                        case 'doughnut':
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>';
                                            break;
                                        case 'value':
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><path d="M12 8v8"></path><path d="M10 10l2-2"></path>
</svg>';
                                            break;
                                        default:
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>';
                                    }
                                    ?>
                                    <button type="button" data-modal-chart-type="<?= $escape($allowedType) ?>"
                                        class="chart-type-btn modal-chart-btn <?= $allowedType === ($viewData['modal_chart_type'] ?? $chartType) ? 'active' : '' ?>"
                                            title="Modale : <?= $escape($chartTypes[$allowedType] ?? ucfirst($allowedType)) ?>">
                                        <?= $icon ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="modal-chart-types">
                            <span class="chart-type-label" style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Carte</span>
                            <form method="POST" action="" class="chart-type-form">
                                <input type="hidden" name="parameter_id" value="<?= $escape($parameterId) ?>">
                                <input type="hidden" name="chart_pref_submit" value="1">
                                <div class="chart-type-group">
                                    <?php foreach ($chartAllowed as $allowedType) :
                                        $icon = '';
                                        switch ($allowedType) {
                                            case 'line':
                                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>';
                                                break;
                                            case 'bar':
                                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4">
</line><line x1="6" y1="20" x2="6" y2="16"></line></svg>';
                                                break;
                                            case 'scatter':
                                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<circle cx="7.5" cy="7.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/><circle cx="7.5" cy="16.5" r="2.5"/>
<circle cx="16.5" cy="7.5" r="2.5"/></svg>';
                                                break;
                                            case 'pie':
                                            case 'doughnut':
                                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>';
                                                break;
                                            case 'value':
                                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><path d="M12 8v8"></path><path d="M10 10l2-2"></path>
</svg>';
                                                break;
                                            default:
                                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>';
                                        }
                                        ?>
                                        <button type="submit" name="chart_type" value="<?= $escape($allowedType) ?>"
                                            class="chart-type-btn <?= $allowedType === $chartType ? 'active' : '' ?>"
                                                title="Carte : <?= $escape($chartTypes[$allowedType] ?? ucfirst($allowedType)) ?>">
                                            <?= $icon ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal-value-only">
                    <div class="modal-value-only-content">
                        <span class="modal-value-text"></span>
                        <span class="modal-unit-text"></span>
                    </div>
                </div>

                <div class="canvas-wrapper">
                    <canvas class="modal-chart chart-<?= $escape($chartType) ?>" tabindex="-1"
                        data-id="<?= $escape($idPrefix) ?>modal-chart-<?= $escape($slug) ?>"></canvas>
                </div>

                <div class="modal-no-data-placeholder" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 120" class="no-data-svg-modal">
                        <path d="M20 90 L50 70 L80 80 L110 50 L140 60 L170 40" stroke="currentColor" stroke-width="3"
                            fill="none" stroke-dasharray="8,6" opacity="0.3" />
                        <circle cx="100" cy="65" r="25" fill="none" stroke="currentColor"
                                stroke-width="3" opacity="0.4" />
                        <line x1="90" y1="55" x2="110" y2="75" stroke="currentColor" stroke-width="3" opacity="0.4" />
                        <line x1="110" y1="55" x2="90" y2="75" stroke="currentColor" stroke-width="3" opacity="0.4" />
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
