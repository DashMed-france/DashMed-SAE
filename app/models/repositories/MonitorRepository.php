<?php

namespace modules\models\repositories;

use modules\models\BaseRepository;
use PDO;

/**
 * Class MonitorRepository
 *
 * Handles retrieval of patient monitoring metrics and history.
 *
 * @package DashMed\Modules\Models\Repositories
 * @author DashMed Team
 * @license Proprietary
 */
class MonitorRepository extends BaseRepository
{
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
        parent::__construct($pdo);
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
            error_log("MonitorRepository::getRawHistory Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves raw buffered history for a specific patient and parameter.
     *
     * This method fetches historical data into memory. It is suitable for small
     * datasets but may cause memory exhaustion on massive time ranges.
     *
     * @param int $patientId The unique ID of the patient.
     * @param string $parameterId The target medical parameter (e.g., 'FC', 'SpO2').
     * @param string|null $targetDate Optional target date (YYYY-MM-DD or ISO 8601), limits data up to this exact date/time.
     * @param int $limit Maximum number of records to return. 0 disables the limit but is risky for large sets.
     * 
     * @return array<int, array{parameter_id: string, value: float|string|null, timestamp: string, alert_flag: string|int}> Ordered chronologically DESC.
     */
    public function getRawHistoryByParameter(
        int $patientId,
        string $parameterId,
        ?string $targetDate = null,
        int $limit = 5000
    ): array {
        try {
            $dateCondition = '';
            $isDateTime = false;
            if ($targetDate !== null) {
                if (
                    preg_match(
                        '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/',
                        $targetDate
                    ) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)
                ) {
                    $dateCondition = 'AND `timestamp` <= :targetDateEnd';
                    $isDateTime = true;
                }
            }

            $sql = "
            SELECT 
                parameter_id,
                value,
                `timestamp`,
                alert_flag
            FROM {$this->table}
            WHERE id_patient = :id
              AND parameter_id = :paramId
              AND archived = 0
              $dateCondition
            ORDER BY `timestamp` DESC
            LIMIT :limit
        ";
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':id', $patientId, \PDO::PARAM_INT);
            $st->bindValue(':paramId', $parameterId, \PDO::PARAM_STR);
            if ($isDateTime && $targetDate !== null) {
                $formattedDate = str_replace('T', ' ', $targetDate);
                if (strlen($formattedDate) === 10) {
                    $formattedDate .= ' 23:59:59';
                } elseif (strlen($formattedDate) === 16) {
                    $formattedDate .= ':59';
                }
                $st->bindValue(':targetDateEnd', $formattedDate, \PDO::PARAM_STR);
            }
            if ($limit > 0) {
                $st->bindValue(':limit', $limit, \PDO::PARAM_INT);
            }

            $st->execute();
            return $st->fetchAll();
        } catch (\PDOException $e) {
            error_log("MonitorRepository::getRawHistoryByParameter Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Streams raw history for a specific patient and parameter using an unbuffered query.
     *
     * This method utilizes PDO unbuffered queries to yield rows one by one directly
     * from the driver, ensuring the PHP memory footprint remains perfectly flat (O(1))
     * regardless of how many millions of rows are returned.
     *
     * @param int $patientId The unique ID of the patient.
     * @param string $parameterId The target medical parameter (e.g., 'FC', 'SpO2').
     * @param string|null $targetDate Optional target date (YYYY-MM-DD or ISO 8601), limits data up to this exact date/time.
     * @param int $limit Maximum number of records to stream. 0 means unlimited.
     * 
     * @return \Generator<int, array{parameter_id: string, value: string|null, timestamp: string, alert_flag: string|int}> Ordered chronologically DESC.
     */
    public function streamRawHistoryByParameter(
        int $patientId,
        string $parameterId,
        ?string $targetDate = null,
        int $limit = 0
    ): \Generator {
        try {
            $dateCondition = '';
            $isDateTime = false;
            if ($targetDate !== null) {
                if (
                    preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $targetDate) || 
                    preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)
                ) {
                    $dateCondition = 'AND `timestamp` <= :targetDateEnd';
                    $isDateTime = true;
                }
            }
            
            $limitSql = $limit > 0 ? 'LIMIT :limit' : '';

            $sql = "
            SELECT 
                parameter_id,
                value,
                `timestamp`,
                alert_flag
            FROM {$this->table}
            WHERE id_patient = :id
              AND parameter_id = :paramId
              AND archived = 0
              $dateCondition
            ORDER BY `timestamp` DESC
            $limitSql
            ";

            // Configure PDO to use unbuffered queries for this statement
            $this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $st = $this->pdo->prepare($sql);
            
            $st->bindValue(':id', $patientId, \PDO::PARAM_INT);
            $st->bindValue(':paramId', $parameterId, \PDO::PARAM_STR);
            
            if ($isDateTime && $targetDate !== null) {
                $formattedDate = str_replace('T', ' ', $targetDate);
                if (strlen($formattedDate) === 10) {
                    $formattedDate .= ' 23:59:59';
                } elseif (strlen($formattedDate) === 16) {
                    $formattedDate .= ':59';
                }
                $st->bindValue(':targetDateEnd', $formattedDate, \PDO::PARAM_STR);
            }
            if ($limit > 0) {
                $st->bindValue(':limit', $limit, \PDO::PARAM_INT);
            }

            $st->execute();

            while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
                yield $row;
            }

        } catch (\PDOException $e) {
            error_log("MonitorRepository::streamRawHistoryByParameter Error: " . $e->getMessage());
        } finally {
            // Restore buffered queries for other application parts
            $this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }

    /**
     * Counts the total number of records available for a specific patient and parameter.
     *
     * This count is crucial for feeding the mathematically accurate LTTB downsampling 
     * algorithm before an unbuffered stream begins, as streams cannot be counted mid-flight.
     *
     * @param int $patientId The unique ID of the patient.
     * @param string $parameterId The target medical parameter.
     * @param string|null $targetDate Optional target date (YYYY-MM-DD or ISO 8601), limits up to this date/time.
     * 
     * @return int The total chronological row count.
     */
    public function countRawHistoryByParameter(
        int $patientId,
        string $parameterId,
        ?string $targetDate = null
    ): int {
        try {
            $dateCondition = '';
            $isDateTime = false;
            if ($targetDate !== null) {
                if (
                    preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $targetDate) || 
                    preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)
                ) {
                    $dateCondition = 'AND `timestamp` <= :targetDateEnd';
                    $isDateTime = true;
                }
            }

            $sql = "
            SELECT count(*) as total
            FROM {$this->table}
            WHERE id_patient = :id
              AND parameter_id = :paramId
              AND archived = 0
              $dateCondition
            ";
            
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':id', $patientId, \PDO::PARAM_INT);
            $st->bindValue(':paramId', $parameterId, \PDO::PARAM_STR);
            
            if ($isDateTime && $targetDate !== null) {
                $formattedDate = str_replace('T', ' ', $targetDate);
                if (strlen($formattedDate) === 10) {
                    $formattedDate .= ' 23:59:59';
                } elseif (strlen($formattedDate) === 16) {
                    $formattedDate .= ':59';
                }
                $st->bindValue(':targetDateEnd', $formattedDate, \PDO::PARAM_STR);
            }

            $st->execute();
            $res = $st->fetch(\PDO::FETCH_ASSOC);
            return (int) ($res['total'] ?? 0);

        } catch (\PDOException $e) {
            error_log("MonitorRepository::countRawHistoryByParameter Error: " . $e->getMessage());
            return 0;
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
            error_log("MonitorRepository::getAllChartTypes Error: " . $e->getMessage());
            return [];
        }
    }
}
