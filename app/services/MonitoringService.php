<?php

namespace modules\services;

use modules\models\Monitoring\MonitorModel;

/**
 * Class MonitoringService | Service de Monitoring
 *
 * Service for processing and organizing monitoring metrics.
 * Service pour le traitement et l'organisation des métriques de monitoring.
 *
 * Applies user preferences, calculates priorities, and formats data for the view.
 * Applique les préférences utilisateur, calcule les priorités et formate les données pour la vue.
 *
 * @package DashMed\Modules\Services
 * @author DashMed Team
 * @license Proprietary
 */
class MonitoringService
{
    /**
     * Processes and organizes raw metrics by applying user preferences.
     * Traite et organise les métriques brutes en appliquant les préférences utilisateur.
     *
     * @param array $metrics    Raw metrics data | Données brutes des paramètres.
     * @param array $rawHistory Raw history data | Historique brut des mesures.
     * @param array $prefs      User preferences | Préférences utilisateur.
     * @param bool  $showAll    Show all metrics ignoring hidden prefs | Afficher tout, ignorant les masqués.
     * @return array Processed and sorted metrics | Liste des métriques traitées et triées.
     */
    public function processMetrics(array $metrics, array $rawHistory, array $prefs, bool $showAll = false): array
    {
        $historyByParam = [];
        foreach ($rawHistory as $r) {
            $pid = (string) $r['parameter_id'];
            if (!isset($historyByParam[$pid])) {
                $historyByParam[$pid] = [];
            }
            $historyByParam[$pid][] = [
                'timestamp' => $r['timestamp'],
                'value' => $r['value'],
                'alert_flag' => (int) $r['alert_flag'],
            ];
        }

        foreach ($historyByParam as $pid => $hist) {
            $historyByParam[$pid] = array_slice($hist, 0, 15);
        }

        $processed = [];
        $chartPrefs = $prefs['charts'] ?? [];
        $orderPrefs = $prefs['orders'] ?? [];

        foreach ($metrics as $m) {
            $pid = (string) ($m['parameter_id'] ?? '');

            $m['history'] = $historyByParam[$pid] ?? [];

            if (($m['value'] === null || $m['value'] === '') && !empty($m['history'])) {
                $latest = $m['history'][0];
                $m['value'] = $latest['value'];
                if (isset($latest['timestamp'])) {
                    $m['timestamp'] = $latest['timestamp'];
                }
                if (isset($latest['alert_flag'])) {
                    $m['alert_flag'] = $latest['alert_flag'];
                }
            }

            $val = is_numeric($m['value']) ? (float) $m['value'] : null;
            $alert = (int) ($m['alert_flag'] ?? 0);

            if ($alert === 1) {
                $m['status'] = MonitorModel::STATUS_CRITICAL;
            } elseif ($val !== null) {
                $cmin = isset($m['critical_min']) ? (float) $m['critical_min'] : null;
                $cmax = isset($m['critical_max']) ? (float) $m['critical_max'] : null;

                if (($cmin !== null && $val <= $cmin) || ($cmax !== null && $val >= $cmax)) {
                    $m['status'] = MonitorModel::STATUS_CRITICAL;
                }
            }

            $prio = $this->calculatePriority($m);
            $m['priority'] = $prio;

            if (!$showAll) {
                $isHidden = !empty($orderPrefs[$pid]['is_hidden']);
                if ($isHidden) {
                    if ($prio >= 1) {
                        // Keep visible if high priority even if hidden
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
            $m['chart_allowed'] = $str ? explode(',', $str) : ['line'];

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
            if ($a['category'] !== $b['category']) {
                return strcmp($a['category'] ?? '', $b['category'] ?? '');
            }
            return strcmp($a['display_name'], $b['display_name']);
        });

        return $processed;
    }

    /**
     * Calculates display priority based on status.
     * Calcule la priorité d'affichage en fonction du statut.
     *
     * @param array $m Metric data | Données du paramètre.
     * @return int Priority (2=critical, 1=warning, 0=normal) | Priorité (2=critique, 1=warning, 0=normal).
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
     * Prépare toutes les données d'affichage pour la vue (classes CSS, labels, etc.).
     *
     * @param array $row Complete metric data | Données complètes du paramètre.
     * @return array Formatted view data | Données formatées pour la vue.
     */
    public function prepareViewData(array $row): array
    {
        $viewData = [];

        $viewData['parameter_id'] = $row['parameter_id'] ?? '';
        $viewData['display_name'] = $row['display_name'] ?? ($row['parameter_id'] ?? '');

        $rawVal = $row['value'] ?? null;
        if ($rawVal === null || $rawVal === '' || $rawVal === 'null') {
            $viewData['value'] = '—';
            $viewData['unit'] = '';
        } else {
            $viewData['value'] = $rawVal;
            $viewData['unit'] = $row['unit'] ?? '';
        }
        $viewData['description'] = $row['description'] ?? '—';
        $viewData['slug'] = strtolower(trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $viewData['display_name'])));

        $timeRaw = $row['timestamp'] ?? null;
        $viewData['time_iso'] = $timeRaw ? date('c', strtotime($timeRaw)) : null;
        $viewData['time_formatted'] = $timeRaw ? date('H:i', strtotime($timeRaw)) : '—';

        $valNum = is_numeric($viewData['value']) ? (float) $viewData['value'] : null;
        $critFlag = !empty($row['alert_flag']) && (int) $row['alert_flag'] === 1;

        $nmin = isset($row['normal_min']) ? (float) $row['normal_min'] : null;
        $nmax = isset($row['normal_max']) ? (float) $row['normal_max'] : null;
        $cmin = isset($row['critical_min']) ? (float) $row['critical_min'] : null;
        $cmax = isset($row['critical_max']) ? (float) $row['critical_max'] : null;

        $viewData['thresholds'] = [
            "nmin" => $nmin,
            "nmax" => $nmax,
            "cmin" => $cmin,
            "cmax" => $cmax
        ];
        $viewData['view_limits'] = [
            "min" => isset($row['display_min']) ? (float) $row['display_min'] : null,
            "max" => isset($row['display_max']) ? (float) $row['display_max'] : null
        ];

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
                $isWarning = ($nmin !== null && $valNum < $nmin)
                    || ($nmax !== null && $valNum > $nmax);

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

        $viewData['chart_type'] = $row['chart_type'] ?? 'line';
        $viewData['chart_allowed'] = $row['chart_allowed'] ?? ['line'];
        $viewData['chart_config'] = json_encode([
            "type" => $viewData['chart_type'],
            "title" => $viewData['display_name'],
            "labels" => array_map(
                fn($hrow) => date("H:i", strtotime($hrow["timestamp"] ?? "now")),
                $row["history"] ?? []
            ),
            "data" => array_map(
                fn($hrow) => (float) ($hrow["value"] ?? 0),
                $row["history"] ?? []
            ),
            "target" => "modal-chart-" . $viewData['slug'],
            "color" => "#4f46e5",
            "thresholds" => $viewData['thresholds'],
            "view" => $viewData['view_limits'],
        ]);

        $viewData['history_html_data'] = [];
        $hist = $row['history'] ?? [];
        $printedAny = false;
        foreach ($hist as $hItem) {
            $ts = $hItem['timestamp'] ?? null;
            $viewData['history_html_data'][] = [
                'time_iso' => $ts ? date('c', strtotime($ts)) : '',
                'value' => (string) ($hItem['value'] ?? ''),
                'flag' => ((int) ($hItem['alert_flag'] ?? 0) === 1) ? '1' : '0'
            ];
            $printedAny = true;
        }

        if (!$printedAny) {
            $viewData['history_html_data'][] = [
                'time_iso' => $viewData['time_iso'],
                'value' => (string) $viewData['value'],
                'flag' => $critFlag ? '1' : '0'
            ];
        }

        return $viewData;
    }
}
