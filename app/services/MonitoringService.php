<?php

namespace modules\services;

use modules\models\monitoring\MonitorModel;

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
     * @param array<int, array<string, mixed>> $metrics    Raw metrics data
     * @param array<int, array<string, mixed>> $rawHistory Raw history data
     * @param array{
     *   charts?: array<string, string>,
     *   orders?: array<string, array<string, mixed>>
     * } $prefs User preferences
     * @param bool  $showAll    Show all metrics ignoring hidden prefs
     * @return array<int, array<string, mixed>> Processed and sorted metrics
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
            $rawParamId = $m['parameter_id'] ?? '';
            $pid = is_scalar($rawParamId) ? (string) $rawParamId : '';

            $m['history'] = $historyByParam[$pid] ?? [];

            if (($m['value'] === null || $m['value'] === '') && !empty($m['history'])) {
                $latest = $m['history'][0];
                $m['value'] = $latest['value'];
                $m['timestamp'] = $latest['timestamp'];
                $m['alert_flag'] = $latest['alert_flag'];
            }

            $val = is_numeric($m['value']) ? (float) $m['value'] : null;
            $rawAlertM = $m['alert_flag'] ?? 0;
            $alert = is_numeric($rawAlertM) ? (int) $rawAlertM : 0;

            if ($alert === 1) {
                $m['status'] = MonitorModel::STATUS_CRITICAL;
            } elseif ($val !== null) {
                $rawCmin = $m['critical_min'] ?? null;
                $cmin = is_numeric($rawCmin) ? (float) $rawCmin : null;
                $rawCmax = $m['critical_max'] ?? null;
                $cmax = is_numeric($rawCmax) ? (float) $rawCmax : null;
                $rawNmin = $m['normal_min'] ?? null;
                $nmin = is_numeric($rawNmin) ? (float) $rawNmin : null;
                $rawNmax = $m['normal_max'] ?? null;
                $nmax = is_numeric($rawNmax) ? (float) $rawNmax : null;

                if (($cmin !== null && $val <= $cmin) || ($cmax !== null && $val >= $cmax)) {
                    $m['status'] = MonitorModel::STATUS_CRITICAL;
                } elseif (($nmin !== null && $val <= $nmin) || ($nmax !== null && $val >= $nmax)) {
                    $m['status'] = MonitorModel::STATUS_WARNING;
                }
            }

            $prio = $this->calculatePriority($m);
            $m['priority'] = $prio;

            if (!$showAll) {
                $isHidden = !empty($orderPrefs[$pid]['is_hidden']);
                if ($isHidden) {
                    if ($prio >= 1) {
                        $m['force_shown'] = true;
                    } else {
                        continue;
                    }
                }
            }

            $userChart = $chartPrefs[$pid] ?? null;
            $defaultChart = $m['default_chart'] ?? 'line';
            $m['chart_type'] = $userChart ?: $defaultChart;

            $m['display_order'] = $orderPrefs[$pid]['display_order'] ?? 9999;

            $str = $m['allowed_charts_str'] ?? '';
            $m['chart_allowed'] = is_string($str) && $str !== '' ? explode(',', $str) : ['line'];

            $m['view_data'] = $this->prepareViewData($m);

            $processed[] = $m;
        }

        usort($processed, function ($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $b['priority'] <=> $a['priority'];
            }
            if ($a['display_order'] !== $b['display_order']) {
                return $a['display_order'] <=> $b['display_order'];
            }
            $catA = is_string($a['category'] ?? null) ? $a['category'] : '';
            $catB = is_string($b['category'] ?? null) ? $b['category'] : '';
            if ($catA !== $catB) {
                return strcmp($catA, $catB);
            }
            $dispA = is_string($a['display_name'] ?? null) ? $a['display_name'] : '';
            $dispB = is_string($b['display_name'] ?? null) ? $b['display_name'] : '';
            return strcmp($dispA, $dispB);
        });

        return $processed;
    }

    /**
     * Calculates display priority based on status.
     *
     * @param array<string, mixed> $m Metric data
     * @return int Priority (2=critical, 1=warning, 0=normal)
     */
    public function calculatePriority(array $m): int
    {
        $status = $m['status'] ?? MonitorModel::STATUS_NORMAL;
        if ($status === MonitorModel::STATUS_CRITICAL) {
            return 2;
        }
        if ($status === MonitorModel::STATUS_WARNING) {
            return 1;
        }
        return 0;
    }

    /**
     * Prepares all view data (CSS classes, labels, etc.).
     *
     * @param array<string, mixed> $row Complete metric data
     * @return array<string, mixed> Formatted view data
     */
    public function prepareViewData(array $row): array
    {
        $viewData = [];

        $viewData['parameter_id'] = $row['parameter_id'] ?? '';
        $rawDispName = $row['display_name'] ?? ($row['parameter_id'] ?? '');
        $viewData['display_name'] = is_string($rawDispName) ? $rawDispName : '';

        $rawVal = $row['value'] ?? null;
        if ($rawVal === null || $rawVal === '' || $rawVal === 'null') {
            $viewData['value'] = '—';
            $viewData['unit'] = '';
        } else {
            $viewData['value'] = $rawVal;
            $viewData['unit'] = $row['unit'] ?? '';
        }
        $viewData['description'] = $row['description'] ?? '—';
        $dispNameStr = $viewData['display_name'];
        $slugResult = preg_replace('/[^a-zA-Z0-9_-]/', '-', $dispNameStr);
        $viewData['slug'] = strtolower(trim(is_string($slugResult) ? $slugResult : ''));

        $timeRaw = $row['timestamp'] ?? null;
        $timeRawStr = is_string($timeRaw) ? $timeRaw : null;
        $viewData['time_iso'] = $timeRawStr ? date('c', (int) strtotime($timeRawStr)) : null;
        $viewData['time_formatted'] = $timeRawStr ? date('H:i', (int) strtotime($timeRawStr)) : '—';


        $rawNmin = $row['normal_min'] ?? null;
        $nmin = is_numeric($rawNmin) ? (float) $rawNmin : null;
        $rawNmax = $row['normal_max'] ?? null;
        $nmax = is_numeric($rawNmax) ? (float) $rawNmax : null;
        $rawCmin = $row['critical_min'] ?? null;
        $cmin = is_numeric($rawCmin) ? (float) $rawCmin : null;
        $rawCmax = $row['critical_max'] ?? null;
        $cmax = is_numeric($rawCmax) ? (float) $rawCmax : null;

        $viewData['thresholds'] = [
            "nmin" => $nmin,
            "nmax" => $nmax,
            "cmin" => $cmin,
            "cmax" => $cmax
        ];
        $rawDispMin = $row['display_min'] ?? null;
        $rawDispMax = $row['display_max'] ?? null;
        $viewData['view_limits'] = [
            "min" => is_numeric($rawDispMin) ? (float) $rawDispMin : null,
            "max" => is_numeric($rawDispMax) ? (float) $rawDispMax : null
        ];

        $viewData['chart_type'] = $row['chart_type'] ?? 'line';
        $viewData['chart_allowed'] = $row['chart_allowed'] ?? ['line'];
        $viewData['history_html_data'] = [];
        $histForHtml = is_array($row['history'] ?? null) ? $row['history'] : [];

        usort($histForHtml, function ($a, $b): int {
            $tsA = is_array($a) && is_string($a['timestamp'] ?? null) ? strtotime($a['timestamp']) : 0;
            $tsB = is_array($b) && is_string($b['timestamp'] ?? null) ? strtotime($b['timestamp']) : 0;
            return $tsA <=> $tsB;
        });

        $histForHtml = array_slice($histForHtml, -15);

        foreach ($histForHtml as $hItem) {
            if (!is_array($hItem)) {
                continue;
            }
            $ts = $hItem['timestamp'] ?? null;
            $tsStr = is_string($ts) ? $ts : null;
            $rawHVal = $hItem['value'] ?? '';
            $rawHFlag = $hItem['alert_flag'] ?? 0;
            $viewData['history_html_data'][] = [
                'time_iso' => $tsStr ? date('c', (int) strtotime($tsStr)) : '',
                'value' => is_scalar($rawHVal) ? (string) $rawHVal : '',
                'flag' => (is_numeric($rawHFlag) && (int) $rawHFlag === 1) ? '1' : '0'
            ];
        }

        if (count($viewData['history_html_data']) === 0) {
            $rawTimeIso = $viewData['time_iso'];
            $rawVdVal = $viewData['value'];
            $rawAlertFlag = $row['alert_flag'] ?? 0;
            $viewData['history_html_data'][] = [
                'time_iso' => is_string($rawTimeIso) ? $rawTimeIso : '',
                'value' => is_scalar($rawVdVal) ? (string) $rawVdVal : '',
                'flag' => (is_numeric($rawAlertFlag) && (int) $rawAlertFlag === 1) ? '1' : '0'
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
