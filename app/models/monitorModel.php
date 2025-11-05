<?php
namespace modules\models;

use Database;
use PDO;

class monitorModel
{
    private PDO $pdo;
    private string $table;

    public function __construct(?PDO $pdo = null, string $table = 'patient_data')
    {
        $this->pdo   = $pdo ?? Database::getInstance();
        $this->table = $table;
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function getLatestMetricsForPatient(int $idPatient): array
    {
        $sql = "
            SELECT 
                pd.parameter_id,
                pd.value,
                pd.`timestamp`,
                pd.alert_flag,
                pr.display_name,
                pr.category,
                pr.unit,
                pr.description,
                pr.normal_min,
                pr.normal_max,
                pr.critical_min
            FROM {$this->table} pd
            INNER JOIN (
                SELECT parameter_id, MAX(`timestamp`) AS ts
                FROM {$this->table}
                WHERE id_patient = :id1 AND archived = 0
                GROUP BY parameter_id
            ) last 
                ON last.parameter_id = pd.parameter_id
                AND last.ts = pd.`timestamp`
            LEFT JOIN parameter_reference pr 
                ON pr.parameter_id = pd.parameter_id
            WHERE pd.id_patient = :id2 
              AND pd.archived = 0
            ORDER BY pr.category, pr.display_name
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([':id1' => $idPatient, ':id2' => $idPatient]);
        return $st->fetchAll();
    }
}