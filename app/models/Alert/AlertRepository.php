<?php

declare(strict_types=1);

namespace modules\models\Alert;

use Database;
use PDO;
use PDOException;

/**
 * Repository pour récupérer les alertes de dépassement de seuil.
 * Utilise PDO avec requêtes préparées pour la sécurité.
 */
final class AlertRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Récupère les dernières mesures hors seuil pour un patient donné.
     * Retourne uniquement les paramètres où la valeur dépasse les seuils définis.
     *
     * @param int $patientId Identifiant du patient
     * @return AlertItem[] Liste des alertes (peut être vide)
     */
    public function getOutOfThresholdAlerts(int $patientId): array
    {
        try {
            // Requête : dernière mesure par paramètre + seuils, filtré hors seuil
            $sql = "
                SELECT 
                    latest.parameter_id,
                    latest.value,
                    latest.timestamp,
                    pr.display_name,
                    pr.unit,
                    pr.normal_min,
                    pr.normal_max,
                    pr.critical_min,
                    pr.critical_max
                FROM (
                    -- Sous-requête : dernière mesure par paramètre pour ce patient
                    SELECT pd1.parameter_id, pd1.value, pd1.timestamp
                    FROM patient_data pd1
                    INNER JOIN (
                        SELECT parameter_id, MAX(timestamp) AS max_ts
                        FROM patient_data
                        WHERE id_patient = :patient_id_inner
                          AND archived = 0
                          AND value IS NOT NULL
                        GROUP BY parameter_id
                    ) AS latest_ts
                    ON pd1.parameter_id = latest_ts.parameter_id
                       AND pd1.timestamp = latest_ts.max_ts
                    WHERE pd1.id_patient = :patient_id_outer
                      AND pd1.archived = 0
                ) AS latest
                INNER JOIN parameter_reference pr 
                    ON pr.parameter_id = latest.parameter_id
                WHERE 
                    -- Filtre : seulement les valeurs hors seuil (min/max non NULL)
                    (
                        (pr.normal_min IS NOT NULL AND latest.value < pr.normal_min)
                        OR (pr.normal_max IS NOT NULL AND latest.value > pr.normal_max)
                    )
                ORDER BY 
                    -- Priorité : critiques d'abord
                    CASE 
                        WHEN (pr.critical_min IS NOT NULL AND latest.value < pr.critical_min)
                             OR (pr.critical_max IS NOT NULL AND latest.value > pr.critical_max)
                        THEN 0
                        ELSE 1
                    END ASC,
                    latest.timestamp DESC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':patient_id_inner' => $patientId,
                ':patient_id_outer' => $patientId,
            ]);

            $alerts = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                /** @var array<string, mixed> $row */
                $alerts[] = AlertItem::fromRow($row);
            }

            return $alerts;
        } catch (PDOException $e) {
            // Log l'erreur sans bloquer l'affichage
            error_log('[AlertRepository] SQL Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie rapidement si un patient a des alertes actives.
     * Utilise une requête optimisée avec LIMIT 1 au lieu de récupérer toutes les alertes.
     *
     * @param int $patientId Identifiant du patient
     * @return bool Vrai si au moins une alerte existe
     */
    public function hasAlertsQuick(int $patientId): bool
    {
        try {
            $sql = "
                SELECT 1
                FROM patient_data pd
                INNER JOIN parameter_reference pr ON pr.parameter_id = pd.parameter_id
                WHERE pd.id_patient = :patient_id
                  AND pd.archived = 0
                  AND pd.value IS NOT NULL
                  AND (
                      (pr.normal_min IS NOT NULL AND pd.value < pr.normal_min)
                      OR (pr.normal_max IS NOT NULL AND pd.value > pr.normal_max)
                  )
                LIMIT 1
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':patient_id' => $patientId]);

            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            error_log('[AlertRepository] hasAlertsQuick SQL Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un patient a des alertes actives.
     *
     * @deprecated Utiliser hasAlertsQuick() pour de meilleures performances.
     * @param int $patientId Identifiant du patient
     * @return bool Vrai si au moins une alerte existe
     */
    public function hasAlerts(int $patientId): bool
    {
        return $this->hasAlertsQuick($patientId);
    }
}
