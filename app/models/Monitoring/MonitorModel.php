<?php

namespace modules\models\monitoring;

use assets\includes\Database;
use PDO;

/**
 * Class MonitorModel
 *
 * Handles retrieval of patient monitoring metrics and history.
 *
 * @package DashMed\Modules\Models\Monitoring
 * @author DashMed Team
 * @license Proprietary
 */
class MonitorModel
{
    /** @var PDO Database connection */
    private PDO $pdo;

    /** @var string Table name */
    private string $table;

    /** @var string Status: Normal */
    public const STATUS_NORMAL = 'normal';

    /** @var string Status: Warning */
    public const STATUS_WARNING = 'warning';

    /** @var string Status: Critical */
    public const STATUS_CRITICAL = 'critical';

    /** @var string Status: Unknown */
    public const STATUS_UNKNOWN = 'unknown';

    /**
     * Constructor
     *
     * @param PDO|null $pdo Database connection (optional)
     * @param string $table Table name
     */
    public function __construct(?PDO $pdo = null, string $table = 'patient_data')
    {
        $this->pdo = $pdo ?? Database::getInstance();
        $this->table = $table;
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves the latest metrics for a patient.
     *
     * Returns an empty array on SQL error to prevent blocking display.
     *
     * @param int $patientId Patient ID
     * @return array<int, \modules\models\entities\Indicator> List of metrics or empty array
     */
    public function getLatestMetrics(int $patientId): array
    {
        try {
            $sql = "
        SELECT
            pr.parameter_id,
            pd.value,
            pd.`timestamp`,
            pd.alert_flag,

            pr.display_name,
            pr.category,
            pr.unit,
            pr.description,
            pr.normal_min,
            pr.normal_max,
            pr.critical_min,
            pr.critical_max,
            pr.display_min,
            pr.display_max,

            pr.default_chart,
            
            (
                SELECT GROUP_CONCAT(chart_type ORDER BY chart_type)
                FROM parameter_chart_allowed
                WHERE parameter_id = pr.parameter_id
            ) AS allowed_charts_str,

            -- Status calculation (Logic preserved for View)
            CASE
                WHEN pd.value IS NULL THEN 'unknown'
                WHEN (
                    pd.alert_flag = 1
                    OR (pr.critical_min IS NOT NULL AND pd.value < pr.critical_min)
                    OR (pr.critical_max IS NOT NULL AND pd.value > pr.critical_max)
                ) THEN '" . self::STATUS_CRITICAL . "'
                WHEN (
                    (pr.normal_min IS NOT NULL AND pd.value < pr.normal_min)
                    OR (pr.normal_max IS NOT NULL AND pd.value > pr.normal_max)
                ) THEN '" . self::STATUS_WARNING . "'
                ELSE '" . self::STATUS_NORMAL . "'
                END AS status

        FROM parameter_reference pr

        -- Last measurement for patient
        LEFT JOIN (
            SELECT pd1.*
            FROM {$this->table} pd1
            INNER JOIN (
                SELECT parameter_id, MAX(`timestamp`) AS ts
                FROM {$this->table}
                WHERE id_patient = :id_pat_inner AND archived = 0
                GROUP BY parameter_id
            ) last
              ON last.parameter_id = pd1.parameter_id
             AND last.ts = pd1.`timestamp`
            WHERE pd1.id_patient = :id_pat_outer AND pd1.archived = 0
        ) pd
          ON pd.parameter_id = pr.parameter_id

        ORDER BY
            pr.category ASC,
            pr.display_name ASC
        ";

            $st = $this->pdo->prepare($sql);
            $st->execute([
                ':id_pat_inner' => $patientId,
                ':id_pat_outer' => $patientId,
            ]);

            $rows = $st->fetchAll();
            $indicators = [];

            foreach ($rows as $row) {
                $allowedCharts = [];
                if (!empty($row['allowed_charts_str'])) {
                    $allowedCharts = explode(',', $row['allowed_charts_str']);
                } else {
                    $allowedCharts = ['line'];
                }

                $indicators[] = new \modules\models\entities\Indicator(
                    (string) ($row['parameter_id'] ?? ''),
                    isset($row['value']) ? (float) $row['value'] : null,
                    isset($row['timestamp']) ? (string) $row['timestamp'] : null,
                    isset($row['alert_flag']) ? (int) $row['alert_flag'] : 0,
                    (string) ($row['display_name'] ?? ''),
                    (string) ($row['category'] ?? ''),
                    (string) ($row['unit'] ?? ''),
                    isset($row['description']) ? (string) $row['description'] : null,
                    isset($row['normal_min']) ? (float) $row['normal_min'] : null,
                    isset($row['normal_max']) ? (float) $row['normal_max'] : null,
                    isset($row['critical_min']) ? (float) $row['critical_min'] : null,
                    isset($row['critical_max']) ? (float) $row['critical_max'] : null,
                    isset($row['display_min']) ? (float) $row['display_min'] : null,
                    isset($row['display_max']) ? (float) $row['display_max'] : null,
                    (string) ($row['default_chart'] ?? 'line'),
                    $allowedCharts,
                    (string) ($row['status'] ?? self::STATUS_UNKNOWN)
                );
            }

            return $indicators;
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Retrieves raw history for a patient.
     *
     * @param int $patientId Patient ID
     * @param int $limit Max records
     * @return array<int, array{parameter_id: string, value: float|null, timestamp: string, alert_flag: int}>
     */
    public function getRawHistory(int $patientId, int $limit = 5000): array
    {
        try {
            $sql = "
            SELECT 
                parameter_id,
                value,
                `timestamp`,
                alert_flag
            FROM {$this->table}
            WHERE id_patient = :id
              AND archived = 0
            ORDER BY `timestamp` DESC
            LIMIT :limit
        ";
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':id', $patientId, \PDO::PARAM_INT);
            $st->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll();
        } catch (\PDOException $e) {
            error_log("MonitorModel::getRawHistory Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves the complete list of available chart types.
     *
     * @return array<string, string> Associative array (type => label)
     */
    public function getAllChartTypes(): array
    {
        try {
            $sql = "SELECT chart_type, label FROM chart_types ORDER BY label ASC";
            $st = $this->pdo->prepare($sql);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (\PDOException $e) {
            error_log("MonitorModel::getAllChartTypes Error: " . $e->getMessage());
            return [];
        }
    }
}
