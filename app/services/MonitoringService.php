<?php

namespace modules\services;

use modules\models\entities\Indicator;
use modules\models\repositories\MonitorRepository;

/**
 * Class MonitoringService
 *
 * Service for processing and organizing monitoring metrics.
 * Applies user preferences, calculates priorities, and formats data for the view.
 *
 * @package DashMed\Modules\Services
 * @author DashMed Team
 * @license Proprietary
 */
class MonitoringService
{
    /**
     * Processes and organizes raw metrics by applying user preferences.
     *
     * @param array<int, Indicator> $metrics    Raw metrics data
     * @param array<int, array<string, mixed>> $rawHistory Raw history data
     * @param array{
     *   charts?: array<string, string>,
     *   orders?: array<string, array<string, mixed>>
     * } $prefs User preferences
     * @param bool  $showAll    Show all metrics ignoring hidden prefs
     * @return array<int, Indicator> Processed and sorted metrics
     */
    public function processMetrics(array $metrics, array $rawHistory, array $prefs, bool $showAll = false): array
    {
        $historyByParam = [];
        foreach ($rawHistory as $r) {
            $rawPid = $r['parameter_id'] ?? '';
            $pid = is_scalar($rawPid) ? (string) $rawPid : '';
            if (!isset($historyByParam[$pid])) {
                $historyByParam[$pid] = [];
            }
            $rawTs = $r['timestamp'] ?? '';
            $rawVal = $r['value'] ?? null;
            $rawAlert = $r['alert_flag'] ?? 0;
            $historyByParam[$pid][] = [
                'timestamp' => is_string($rawTs) ? $rawTs : '',
                'value' => $rawVal,
                'alert_flag' => is_numeric($rawAlert) ? (int) $rawAlert : 0,
            ];
        }

        $processed = [];
        $chartPrefs = $prefs['charts'] ?? [];
        $orderPrefs = $prefs['orders'] ?? [];

        foreach ($metrics as $m) {
            $pid = $m->getId();

            $hist = $historyByParam[$pid] ?? [];
            $m->setHistory($hist);

            if (($m->getValue() === null) && !empty($hist)) {
                $latest = $hist[0];
                $val = $latest['value'];
                $m->setValue(is_numeric($val) ? (float) $val : null);
                $m->setTimestamp($latest['timestamp']);
                $m->setAlertFlag($latest['alert_flag']);
            }

            $val = $m->getValue();
            $alert = $m->getAlertFlag();

            if ($alert === 1) {
                $m->setStatus(MonitorRepository::STATUS_CRITICAL);
            } elseif ($val !== null) {
                $cmin = $m->getCriticalMin();
                $cmax = $m->getCriticalMax();
                $nmin = $m->getNormalMin();
                $nmax = $m->getNormalMax();

                if (($cmin !== null && $val <= $cmin) || ($cmax !== null && $val >= $cmax)) {
                    $m->setStatus(MonitorRepository::STATUS_CRITICAL);
                } elseif (($nmin !== null && $val <= $nmin) || ($nmax !== null && $val >= $nmax)) {
                    $m->setStatus(MonitorRepository::STATUS_WARNING);
                }
            }

            $prio = $this->calculatePriority($m);
            $m->setPriority($prio);

            if (!$showAll) {
                $isHidden = !empty($orderPrefs[$pid]['is_hidden']);
                if ($isHidden) {
                    if ($prio >= 1) {
                        $m->setForceShown(true);
                    } else {
                        continue;
                    }
                }
            }

            $userChart = $chartPrefs[$pid] ?? null;
            $defaultChart = $m->getDefaultChart();
            $m->setChartType($userChart ?: $defaultChart);

            $order = $orderPrefs[$pid]['display_order'] ?? 9999;
            $m->setDisplayOrder(is_numeric($order) ? (int) $order : 9999);

            $viewData = $this->prepareViewData($m);
            $m->setViewData($viewData);

            $processed[] = $m;
        }

        usort($processed, function (Indicator $a, Indicator $b) {
            if ($a->getDisplayOrder() !== $b->getDisplayOrder()) {
                return $a->getDisplayOrder() <=> $b->getDisplayOrder();
            }
            return strcmp($a->getDisplayName(), $b->getDisplayName());
        });

        return $processed;
    }

    /**
     * Calculates display priority based on status.
     *
     * @param Indicator $m Metric data
     * @return int Priority (2=critical, 1=warning, 0=normal)
     */
    public function calculatePriority(Indicator $m): int
    {
        $status = $m->getStatus();
        if ($status === MonitorRepository::STATUS_CRITICAL) {
            return 2;
        }
        if ($status === MonitorRepository::STATUS_WARNING) {
            return 1;
        }
        return 0;
    }

    /**
     * Prepares all view data (CSS classes, labels, etc.).
     *
     * @param Indicator $row Indicator entity
     * @return array<string, mixed> Formatted view data
     */
    public function prepareViewData(Indicator $row): array
    {
        $viewData = [];

        $viewData['parameter_id'] = $row->getId();
        $viewData['display_name'] = $row->getDisplayName();

        $val = $row->getValue();
        if ($val === null) {
            $viewData['value'] = '—';
            $viewData['unit'] = '';
        } else {
            $viewData['value'] = $val;
            $viewData['unit'] = $row->getUnit();
        }
        $viewData['description'] = $row->getDescription() ?? '—';

        $dispNameStr = $viewData['display_name'];
        $slugResult = preg_replace('/[^a-zA-Z0-9_-]/', '-', $dispNameStr);
        $viewData['slug'] = strtolower(trim(is_string($slugResult) ? $slugResult : ''));

        $timeRaw = $row->getTimestamp();
        $viewData['time_iso'] = $timeRaw ? date('c', (int) strtotime($timeRaw)) : null;
        $viewData['time_formatted'] = $timeRaw ? date('H:i', (int) strtotime($timeRaw)) : '—';

        $nmin = $row->getNormalMin();
        $nmax = $row->getNormalMax();
        $cmin = $row->getCriticalMin();
        $cmax = $row->getCriticalMax();

        $viewData['thresholds'] = [
            "nmin" => $nmin,
            "nmax" => $nmax,
            "cmin" => $cmin,
            "cmax" => $cmax
        ];

        $viewData['view_limits'] = [
            "min" => $row->getDisplayMin(),
            "max" => $row->getDisplayMax()
        ];

        $viewData['chart_type'] = $row->getChartType();
        $viewData['chart_allowed'] = $row->getAllowedCharts();

        $viewData['history_html_data'] = [];
        $histForHtml = $row->getHistory();

        usort($histForHtml, function ($a, $b): int {
            $tsA = is_string($a['timestamp'] ?? null) ? strtotime($a['timestamp']) : 0;
            $tsB = is_string($b['timestamp'] ?? null) ? strtotime($b['timestamp']) : 0;
            return $tsA <=> $tsB;
        });

        $histForHtml = array_slice($histForHtml, -15);

        foreach ($histForHtml as $hItem) {
            $ts = $hItem['timestamp'] ?? null;
            $tsStr = is_string($ts) ? $ts : null;
            $rawHVal = $hItem['value'] ?? '';
            $rawHFlag = $hItem['alert_flag'] ?? 0;
            $viewData['history_html_data'][] = [
                'time_iso' => $tsStr ? date('c', (int) strtotime($tsStr)) : '',
                'value' => is_numeric($rawHVal) || is_string($rawHVal) ? (string) $rawHVal : '',
                'flag' => (is_numeric($rawHFlag) && (int) $rawHFlag === 1) ? '1' : '0'
            ];
        }

        if (count($viewData['history_html_data']) === 0) {
            $rawTimeIso = $viewData['time_iso'];
            $rawVdVal = $viewData['value'];
            $rawAlertFlag = $row->getAlertFlag();
            $viewData['history_html_data'][] = [
                'time_iso' => is_string($rawTimeIso) ? $rawTimeIso : '',
                'value' => (string) $rawVdVal,
                'flag' => ($rawAlertFlag === 1) ? '1' : '0'
            ];
        }

        $lastHistItem = end($viewData['history_html_data']);
        if ($lastHistItem['value'] !== '') {
            $viewData['value'] = $lastHistItem['value'];
            $viewData['time_iso'] = $lastHistItem['time_iso'];
            $viewData['time_formatted'] = $viewData['time_iso'] !== ''
                ? date('H:i', (int) strtotime($viewData['time_iso']))
                : '—';
        }

        $valNum = is_numeric($viewData['value']) ? (float) $viewData['value'] : null;
        $critFlag = $lastHistItem['flag'] === '1';

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
                $stateClassModal = 'alert';
            } else {
                $isWarning = ($nmin !== null && $valNum <= $nmin)
                    || ($nmax !== null && $valNum >= $nmax);

                if ($isWarning) {
                    $stateLabel = 'Prévention d\'alerte ⚠️';
                    $stateClass = 'card--warn';
                    $stateClassModal = 'warn';
                } else {
                    $stateLabel = 'Constante normale ✅';
                    $stateClassModal = 'stable';
                }
            }
        }

        $viewData['state_label'] = $stateLabel;
        $viewData['card_class'] = $stateClass;
        $viewData['modal_class'] = $stateClassModal;
        $viewData['is_crit_flag'] = ($stateClass === 'card--alert');

        $viewData['chart_config'] = json_encode([
            "type" => $viewData['chart_type'],
            "title" => $viewData['display_name'],
            "labels" => array_map(
                static function (array $hrow): string {
                    $ts = $hrow['time_iso'];
                    return $ts !== '' ? date('H:i', (int) strtotime($ts)) : 'now';
                },
                $viewData['history_html_data']
            ),
            "data" => array_map(
                static function (array $hrow): float {
                    $val = $hrow['value'];
                    return is_numeric($val) ? (float) $val : 0.0;
                },
                $viewData['history_html_data']
            ),
            "target" => "modal-chart-" . $viewData['slug'],
            "color" => "#4f46e5",
            "thresholds" => $viewData['thresholds'],
            "view" => $viewData['view_limits'],
        ]);

        return $viewData;
    }
}
