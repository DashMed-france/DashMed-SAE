<?php
namespace modules\models\Monitoring;

use Database;
use PDO;

/**
 * Model for managing patient monitoring data.
 *
 * This model handles retrieval of patient metrics, historical data,
 * and chart type information from the database.
 */
class MonitorModel
{
    /**
     * PDO database connection instance.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Database table name for patient data.
     *
     * @var string
     */
    private string $table;

    /**
     * Status constant for normal values.
     *
     * @var string
     */
    public const STATUS_NORMAL = 'normal';

    /**
     * Status constant for warning values.
     *
     * @var string
     */
    public const STATUS_WARNING = 'warning';

    /**
     * Status constant for critical values.
     *
     * @var string
     */
    public const STATUS_CRITICAL = 'critical';

    /**
     * Status constant for unknown values.
     *
     * @var string
     */
    public const STATUS_UNKNOWN = 'unknown';

    /**
     * Constructor for MonitorModel.
     *
     * Initializes the database connection and table name. Sets default
     * fetch mode to associative array.
     *
     * @param PDO|null $pdo Optional PDO instance for dependency injection
     * @param string $table Database table name for patient data
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
     * Fetches the most recent measurement for each parameter along with
     * reference values, allowed chart types, and calculated status.
     * Returns an empty array on SQL error to avoid blocking display.
     *
     * @param int $patientId The patient's unique identifier
     * @return array Array of metrics with parameter details and status, or empty array on error
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

            return $st->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Retrieves raw historical data for a patient.
     *
     * Fetches all non-archived measurements for the patient, ordered by
     * timestamp in descending order (most recent first).
     *
     * @param int $patientId The patient's unique identifier
     * @return array Array of historical measurements, or empty array on error
     */
    public function getRawHistory(int $patientId): array
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
        ";
            $st = $this->pdo->prepare($sql);
            $st->execute([':id' => $patientId]);
            return $st->fetchAll();
        } catch (\PDOException $e) {
            error_log("MonitorModel::getRawHistory Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all available chart types from the database.
     *
     * Queries the `chart_types` table to obtain chart type identifiers
     * and their corresponding labels, ordered alphabetically by label.
     *
     * @return array Associative array where key is chart type (e.g., 'line') and value is label (e.g., 'Line'),
     *               or empty array on SQL error
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