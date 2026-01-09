<?php

namespace modules\models\Monitoring;

use Database;
use PDO;

/**
 * Class MonitorModel | Classe MonitorModel
 *
 * Handles retrieval of patient monitoring metrics and history.
 * Gère la récupération des métriques de monitoring et de l'historique des patients.
 *
 * @package DashMed\Modules\Models\Monitoring
 * @author DashMed Team
 * @license Proprietary
 */
class MonitorModel
{
    /** @var PDO Database connection | Connexion BDD */
    private PDO $pdo;

    /** @var string Table name | Nom de la table */
    private string $table;

    /** @var string Status: Normal | Statut : Normal */
    public const STATUS_NORMAL = 'normal';

    /** @var string Status: Warning | Statut : Attention */
    public const STATUS_WARNING = 'warning';

    /** @var string Status: Critical | Statut : Critique */
    public const STATUS_CRITICAL = 'critical';

    /** @var string Status: Unknown | Statut : Inconnu */
    public const STATUS_UNKNOWN = 'unknown';

    /**
     * Constructor | Constructeur
     *
     * @param PDO|null $pdo Database connection (optional) | Connexion BDD (optionnel)
     * @param string $table Table name | Nom de la table
     */
    public function __construct(?PDO $pdo = null, string $table = 'patient_data')
    {
        $this->pdo = $pdo ?? Database::getInstance();
        $this->table = $table;
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves the latest metrics for a patient.
     * Récupère les dernières métriques pour un patient.
     *
     * Returns an empty array on SQL error to prevent blocking display.
     * Retourne un tableau vide en cas d'erreur SQL pour ne pas bloquer l'affichage.
     *
     * @param int $patientId Patient ID | Identifiant du patient
     * @return array List of metrics or empty array | La liste des métriques ou un tableau vide
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

            return $st->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Retrieves raw history for a patient.
     * Récupère l'historique brut pour un patient.
     *
     * @param int $patientId Patient ID | ID du patient
     * @param int $limit Max records | Nombre max d'enregistrements
     * @return array
     */
    public function getRawHistory(int $patientId, int $limit = 500): array
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
     * Récupère la liste complète des types de graphiques disponibles.
     *
     * @return array Associative array (type => label) | Tableau associatif (type => libellé)
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
