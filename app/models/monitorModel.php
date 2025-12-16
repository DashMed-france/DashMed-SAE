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
        $this->pdo = $pdo ?? Database::getInstance();
        $this->table = $table;
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Dernière valeur par paramètre (avec référentiel).
     */
    public function getLatestMetricsForPatient(int $idPatient, ?int $userId = null): array
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
                pr.critical_min,
                pr.critical_max,
                pr.display_min,
                pr.display_max,
                -- Priorité : Préférence user > Default global > Default reference
                COALESCE(UPCP.chart_type, PCA.chart_type, pr.default_chart) AS chart_type
            FROM {$this->table} pd
            INNER JOIN (
                SELECT parameter_id, MAX(`timestamp`) AS ts
                FROM {$this->table}
                WHERE id_patient = :id_pat_inner AND archived = 0
                GROUP BY parameter_id
            ) last 
                ON last.parameter_id = pd.parameter_id
                AND last.ts = pd.`timestamp`
            LEFT JOIN parameter_reference pr 
                ON pr.parameter_id = pd.parameter_id
            -- Jointure pour préférence utilisateur (si $userId fourni)
            LEFT JOIN user_parameter_chart_pref UPCP
                ON UPCP.parameter_id = pd.parameter_id
                AND UPCP.id_user = :id_user
            -- Jointure pour le default chart défini dans parameter_chart_allowed
            LEFT JOIN parameter_chart_allowed PCA
                ON PCA.parameter_id = pd.parameter_id
                AND PCA.is_default = 1
            WHERE pd.id_patient = :id_pat_outer
              AND pd.archived = 0
            ORDER BY pr.category, pr.display_name
        ";

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':id_pat_inner' => $idPatient,
            ':id_pat_outer' => $idPatient,
            ':id_user' => $userId // Si null, la jointure UPCP ne matchera rien, ce qui est correct
        ]);
        return $st->fetchAll();
    }

    /**
     * Historique brut (toutes valeurs) trié décroissant pour le patient.
     */
    public function getRawHistoryForPatient(int $idPatient): array
    {
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
        $st->execute([':id' => $idPatient]);
        return $st->fetchAll();
    }
}