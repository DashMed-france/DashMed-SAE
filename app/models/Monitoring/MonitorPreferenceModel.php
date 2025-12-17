<?php

namespace modules\models\Monitoring;

use Database;
use PDO;

class MonitorPreferenceModel
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Enregistre la préférence de graphique pour un utilisateur et un paramètre donné.
     *
     * @param int $userId ID de l'utilisateur
     * @param string $parameterId ID du paramètre
     * @param string $chartType Type de graphique choisi (line, bar, etc.)
     */
    public function saveUserChartPreference(int $userId, string $parameterId, string $chartType): void
    {
        try {
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
        } catch (\PDOException $e) {
            // Echec silencieux ou log
        }
    }

    /**
     * Récupère toutes les préférences (graphiques, ordre) pour un utilisateur.
     *
     * @param int $userId ID de l'utilisateur
     * @return array Tableau associatif ['charts' => ..., 'orders' => ...]
     */
    public function getUserPreferences(int $userId): array
    {
        try {
            // Récupérer les préférences de graphiques
            $sqlChart = "SELECT parameter_id, chart_type FROM user_parameter_chart_pref WHERE id_user = :uid";
            $stChart = $this->pdo->prepare($sqlChart);
            $stChart->execute([':uid' => $userId]);
            $chartPrefs = $stChart->fetchAll(PDO::FETCH_KEY_PAIR); // [param_id => chart_type]

            // Récupérer les préférences d'ordre et de masquage
            $sqlOrder = "SELECT parameter_id, display_order, is_hidden FROM user_parameter_order WHERE id_user = :uid";
            $stOrder = $this->pdo->prepare($sqlOrder);
            $stOrder->execute([':uid' => $userId]);
            $orderPrefs = $stOrder->fetchAll(PDO::FETCH_UNIQUE); // [param_id => [display_order, is_hidden]]

            return [
                'charts' => $chartPrefs,
                'orders' => $orderPrefs
            ];
        } catch (\PDOException $e) {
            return ['charts' => [], 'orders' => []];
        }
    }
}
