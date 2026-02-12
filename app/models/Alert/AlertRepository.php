<?php

declare(strict_types=1);

namespace modules\models\alert;

use PDO;
use PDOException;
use assets\includes\Database;

/**
 * Repository pour récupérer les alertes de dépassement de seuil.
 */
final class AlertRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * @return AlertItem[]
     */
    public function getOutOfThresholdAlerts(int $patientId): array
    {
        try {
            $stmt = $this->pdo->prepare($this->getAlertsSql());
            $stmt->execute([':patient_id' => $patientId]);

            $alerts = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                /** @var array<string, mixed> $row */
                $alerts[] = AlertItem::fromRow($row);
            }
            return $alerts;
        } catch (PDOException $e) {
            error_log('[AlertRepository] ' . $e->getMessage());
            return [];
        }
    }

    public function hasAlerts(int $patientId): bool
    {
        try {
            $sql = "
                SELECT 1 FROM patient_data pd
                JOIN parameter_reference pr ON pr.parameter_id = pd.parameter_id
                WHERE pd.id_patient = :patient_id AND pd.archived = 0 AND pd.value IS NOT NULL
                  AND ((pr.normal_min IS NOT NULL AND pd.value <= pr.normal_min)
                    OR (pr.normal_max IS NOT NULL AND pd.value >= pr.normal_max))
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':patient_id' => $patientId]);
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            error_log('[AlertRepository] ' . $e->getMessage());
            return false;
        }
    }

    private function getAlertsSql(): string
    {
        return "
            SELECT m.parameter_id, m.value, m.timestamp,
                   r.display_name, r.unit, r.normal_min, r.normal_max, r.critical_min, r.critical_max
            FROM (
                SELECT pd.parameter_id, pd.value, pd.timestamp
                FROM patient_data pd
                WHERE pd.id_patient = :patient_id AND pd.archived = 0 AND pd.value IS NOT NULL
                  AND pd.timestamp = (
                      SELECT MAX(p2.timestamp) FROM patient_data p2
                      WHERE p2.parameter_id = pd.parameter_id AND p2.id_patient = pd.id_patient AND p2.archived = 0
                  )
            ) m
            JOIN parameter_reference r ON r.parameter_id = m.parameter_id
            WHERE (r.normal_min IS NOT NULL AND m.value <= r.normal_min)
               OR (r.normal_max IS NOT NULL AND m.value >= r.normal_max)
            ORDER BY
                CASE WHEN (r.critical_min IS NOT NULL AND m.value <= r.critical_min)
                       OR (r.critical_max IS NOT NULL AND m.value >= r.critical_max) THEN 0 ELSE 1 END,
                m.timestamp DESC
        ";
    }
}
