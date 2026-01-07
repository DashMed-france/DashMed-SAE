<?php

namespace modules\services;

use modules\models\Monitoring\MonitorModel;

class MonitoringService
{
    /**
     * Traite et organise les métriques brutes en appliquant les préférences utilisateur.
     *
     * @param array $metrics Données brutes des paramètres (récupérées depuis le modèle).
     * @param array $rawHistory Historique brut des mesures pour tous les paramètres.
     * @param array $prefs Préférences utilisateur contenant les choix de graphiques et l'ordre d'affichage.
     * @return array Liste des métriques traitées, enrichies et triées, prêtes pour l'affichage.
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

        $MAX_HISTORY = 20;
        foreach ($historyByParam as $pid => $list) {
            $historyByParam[$pid] = array_slice($list, 0, $MAX_HISTORY);
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

            $this->refineStatus($m);

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
     * Calcule la priorité d'affichage en fonction du statut (critique, warning, normal).
     *
     * @param array $m Données du paramètre
     * @return int Priorité (2=critique, 1=warning, 0=normal)
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
     * Prépare toutes les données d'affichage pour la vue (classes CSS, labels, etc.).
     *
     * @param array $row Données complètes du paramètre
     * @return array Données formatées pour la vue
     */
    public function prepareViewData(array $row): array
    {
        $viewData = [];

        // 1. Formatage basique
        $viewData['parameter_id'] = $row['parameter_id'] ?? '';
        $viewData['display_name'] = $row['display_name'] ?? ($row['parameter_id'] ?? '');

        $rawVal = $row['value'] ?? null;
        if ($rawVal === null || $rawVal === '' || $rawVal === 'null') {
            $viewData['value'] = '—';
            $viewData['unit'] = ''; // Hide unit if no value
        } else {
            $viewData['value'] = $rawVal;
            $viewData['unit'] = $row['unit'] ?? '';
        }
        $viewData['description'] = $row['description'] ?? '—';
        $viewData['slug'] = strtolower(trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $viewData['display_name'])));

        // 2. Formatage des dates
        $timeRaw = $row['timestamp'] ?? null;
        $viewData['time_iso'] = $timeRaw ? date('c', strtotime($timeRaw)) : null;
        $viewData['time_formatted'] = $timeRaw ? date('H:i', strtotime($timeRaw)) : '—';

        // 3. Logique d'état et classes CSS
        $valNum = is_numeric($viewData['value']) ? (float) $viewData['value'] : null;
        $critFlag = !empty($row['alert_flag']) && (int) $row['alert_flag'] === 1;

        $nmin = isset($row['normal_min']) ? (float) $row['normal_min'] : null;
        $nmax = isset($row['normal_max']) ? (float) $row['normal_max'] : null;
        $cmin = isset($row['critical_min']) ? (float) $row['critical_min'] : null;
        $cmax = isset($row['critical_max']) ? (float) $row['critical_max'] : null;

        // Seuils pour les graphiques
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

        // Calcul Labels et Classes
        $stateLabel = '—';
        $stateClass = ''; // Pour la carte
        $stateClassModal = ''; // Pour le détail modal

        if ($valNum === null) {
            $stateLabel = '—';
        } else {
            // Est-ce critique ?
            $isCritical = $critFlag
                || ($cmin !== null && $valNum <= $cmin)
                || ($cmax !== null && $valNum >= $cmax);

            if ($isCritical) {
                $stateLabel = 'Constante critique 🚨';
                $stateClass = 'card--alert';
                $stateClassModal = 'alert';
            } else {
                $inNormal = ($nmin !== null && $nmax !== null)
                    ? ($valNum >= $nmin && $valNum <= $nmax)
                    : true;

                $nearEdge = false;
                if ($nmin !== null && $nmax !== null && $nmax > $nmin) {
                    $width = $nmax - $nmin;
                    $margin = 0.10 * $width; // 10% de marge
                    if ($valNum >= $nmin && $valNum <= $nmax) {
                        if (($valNum - $nmin) <= $margin || ($nmax - $valNum) <= $margin) {
                            $nearEdge = true;
                        }
                    }
                }

                if (!$inNormal || $nearEdge) {
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

    /**
     * Affine le statut (critique/warning/normal) en ajoutant la logique "Near Edge" qui n'est pas en SQL.
     * Met à jour $row['status'] si nécessaire.
     */
    private function refineStatus(array &$row): void
    {
        $currentStatus = $row['status'] ?? MonitorModel::STATUS_NORMAL;
        $valNum = is_numeric($row['value'] ?? null) ? (float) $row['value'] : null;

        if ($currentStatus === MonitorModel::STATUS_CRITICAL) {
            return;
        }

        if ($valNum === null) {
            return;
        }

        $nmin = isset($row['normal_min']) ? (float) $row['normal_min'] : null;
        $nmax = isset($row['normal_max']) ? (float) $row['normal_max'] : null;

        if ($nmin !== null && $nmax !== null && $nmax > $nmin) {
            if ($valNum >= $nmin && $valNum <= $nmax) {
                $width = $nmax - $nmin;
                $margin = 0.10 * $width;
                if (($valNum - $nmin) <= $margin || ($nmax - $valNum) <= $margin) {
                    $row['status'] = MonitorModel::STATUS_WARNING;
                    $row['is_near_edge'] = true;
                }
            }
        }
    }
}
