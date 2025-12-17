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
     * Dernière valeur par paramètre (avec référentiel) + ordre utilisateur + priorité alerte/pré-alerte.
     */
    public function getLatestMetricsForPatient(int $idPatient, ?int $userId = null): array
    {
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

            COALESCE(UPCP.chart_type, PCA.chart_type, pr.default_chart) AS chart_type,
            (
                SELECT GROUP_CONCAT(chart_type ORDER BY chart_type)
                FROM parameter_chart_allowed
                WHERE parameter_id = pr.parameter_id
            ) AS allowed_charts_str,

            -- ordre user (drag & drop)
            COALESCE(UPO.display_order, 9999) AS display_order,
            COALESCE(UPO.is_hidden, 0) AS is_hidden,

            -- statut + priorité (pour tri dynamique)
            CASE
                WHEN pd.value IS NULL THEN 'unknown'
                WHEN (
                    pd.alert_flag = 1
                    OR (pr.critical_min IS NOT NULL AND pd.value < pr.critical_min)
                    OR (pr.critical_max IS NOT NULL AND pd.value > pr.critical_max)
                ) THEN 'critical'
                WHEN (
                    (pr.normal_min IS NOT NULL AND pd.value < pr.normal_min)
                    OR (pr.normal_max IS NOT NULL AND pd.value > pr.normal_max)
                ) THEN 'warning'
                ELSE 'normal'
            END AS status,

            CASE
                WHEN pd.value IS NULL THEN -1
                WHEN (
                    pd.alert_flag = 1
                    OR (pr.critical_min IS NOT NULL AND pd.value < pr.critical_min)
                    OR (pr.critical_max IS NOT NULL AND pd.value > pr.critical_max)
                ) THEN 2
                WHEN (
                    (pr.normal_min IS NOT NULL AND pd.value < pr.normal_min)
                    OR (pr.normal_max IS NOT NULL AND pd.value > pr.normal_max)
                ) THEN 1
                ELSE 0
            END AS priority

        FROM parameter_reference pr

        -- dernière mesure du patient pour chaque paramètre
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

        -- préférences chart user
        LEFT JOIN user_parameter_chart_pref UPCP
          ON UPCP.parameter_id = pr.parameter_id
         AND UPCP.id_user = :id_user_upcp

        -- default chart depuis parameter_chart_allowed
        LEFT JOIN parameter_chart_allowed PCA
          ON PCA.parameter_id = pr.parameter_id
         AND PCA.is_default = 1

        -- ordre user (drag & drop)
        LEFT JOIN user_parameter_order UPO
          ON UPO.parameter_id = pr.parameter_id
         AND UPO.id_user = :id_user_upo

        WHERE COALESCE(UPO.is_hidden, 0) = 0

        ORDER BY
            priority DESC,
            display_order ASC,
            pr.category ASC,
            pr.display_name ASC
        ";

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':id_pat_inner'   => $idPatient,
            ':id_pat_outer'   => $idPatient,
            ':id_user_upcp'   => $userId,
            ':id_user_upo'    => $userId,
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

    public function saveUserChartPreference(int $userId, string $parameterId, string $chartType): void
    {
        $check = "SELECT 1 FROM user_parameter_chart_pref WHERE id_user = :uid AND parameter_id = :pid";
        $st = $this->pdo->prepare($check);
        $st->execute([':uid' => $userId, ':pid' => $parameterId]);

        if ($st->fetchColumn()) {
            $sql = "UPDATE user_parameter_chart_pref 
                    SET chart_type = :ctype, updated_at = NOW() 
                    WHERE id_user = :uid AND parameter_id = :pid";
        } else {
            $sql = "INSERT INTO user_parameter_chart_pref (id_user, parameter_id, chart_type, updated_at) 
                    VALUES (:uid, :pid, :ctype, NOW())";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $userId,
            ':pid' => $parameterId,
            ':ctype' => $chartType
        ]);
    }
}