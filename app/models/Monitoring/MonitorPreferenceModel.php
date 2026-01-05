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
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
            $sqlChart = "SELECT parameter_id, chart_type FROM user_parameter_chart_pref WHERE id_user = :uid";
            $stChart = $this->pdo->prepare($sqlChart);
            $stChart->execute([':uid' => $userId]);
            $chartPrefs = $stChart->fetchAll(PDO::FETCH_KEY_PAIR);

            $sqlOrder = "SELECT parameter_id, display_order, is_hidden FROM user_parameter_order WHERE id_user = :uid";
            $stOrder = $this->pdo->prepare($sqlOrder);
            $stOrder->execute([':uid' => $userId]);
            $orderPrefs = $stOrder->fetchAll(PDO::FETCH_UNIQUE);

            return [
                'charts' => $chartPrefs,
                'orders' => $orderPrefs
            ];
        } catch (\PDOException $e) {
            return ['charts' => [], 'orders' => []];
        }
    }
    /**
     * Récupère la liste de tous les paramètres (indicateurs) disponibles.
     *
     * @return array
     */
    public function getAllParameters(): array
    {
        try {
            $sql = "SELECT * FROM parameter_reference ORDER BY category, display_name";
            $st = $this->pdo->query($sql);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Met à jour la visibilité (is_hidden) pour un paramètre donné.
     *
     * @param int $userId
     * @param string $parameterId
     * @param bool $isHidden
     */
    public function saveUserVisibilityPreference(int $userId, string $parameterId, bool $isHidden): void
    {
        try {
            $check = "SELECT 1 FROM user_parameter_order WHERE id_user = :uid AND parameter_id = :pid";
            $st = $this->pdo->prepare($check);
            $st->execute([':uid' => $userId, ':pid' => $parameterId]);

            if ($st->fetchColumn()) {
                $sql = "UPDATE user_parameter_order 
                        SET is_hidden = :hid, updated_at = NOW() 
                        WHERE id_user = :uid AND parameter_id = :pid";
                $params = [
                    ':uid' => $userId,
                    ':pid' => $parameterId,
                    ':hid' => $isHidden ? 1 : 0
                ];
            } else {
                $sqlOrder = "SELECT COALESCE(MAX(display_order), 0) FROM user_parameter_order WHERE id_user = :uid";
                $stOrder = $this->pdo->prepare($sqlOrder);
                $stOrder->execute([':uid' => $userId]);
                $maxOrder = (int) $stOrder->fetchColumn();

                $sql = "INSERT INTO user_parameter_order (id_user, parameter_id, display_order, is_hidden, updated_at) 
                        VALUES (:uid, :pid, :order, :hid, NOW())";
                $params = [
                    ':uid' => $userId,
                    ':pid' => $parameterId,
                    ':order' => $maxOrder + 1,
                    ':hid' => $isHidden ? 1 : 0
                ];
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } catch (\PDOException $e) {
            // Silent fail or log
        }
    }

    /**
     * Met à jour plusieurs ordres d'affichage en une seule requête (CASE WHEN).
     *
     * @param int $userId
     * @param array $orders [parameter_id => order]
     */
    public function updateUserDisplayOrdersBulk(int $userId, array $orders): void
    {
        if (empty($orders)) {
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $sqlSelect = "SELECT parameter_id, display_order, is_hidden FROM user_parameter_order WHERE id_user = :uid";
            $stmtSelect = $this->pdo->prepare($sqlSelect);
            $stmtSelect->execute([':uid' => $userId]);
            $existingRows = $stmtSelect->fetchAll(\PDO::FETCH_ASSOC);
            $existingMap = [];

            foreach ($existingRows as $row) {
                $existingMap[$row['parameter_id']] = [
                    'display_order' => $row['display_order'],
                    'is_hidden' => $row['is_hidden']
                ];
            }

            $sqlDelete = "DELETE FROM user_parameter_order WHERE id_user = :uid";
            $stmtDelete = $this->pdo->prepare($sqlDelete);
            $stmtDelete->execute([':uid' => $userId]);

            $sqlInsert = "
                        INSERT INTO user_parameter_order (id_user, parameter_id, display_order, is_hidden, updated_at) 
                        VALUES (:uid, :pid, :ord, :hid, NOW())";
            $stmtInsert = $this->pdo->prepare($sqlInsert);

            foreach ($existingMap as $pid => $data) {
                $newOrder = isset($orders[$pid]) ? (int) $orders[$pid] : $data['display_order'];
                $stmtInsert->execute([
                    ':uid' => $userId,
                    ':pid' => $pid,
                    ':ord' => $newOrder,
                    ':hid' => $data['is_hidden']
                ]);
            }

            $this->pdo->commit();
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Met à jour l'ordre d'affichage pour un paramètre donné.
     *
     * @param int $userId
     * @param string $parameterId
     * @param int $order
     */
    public function updateUserDisplayOrder(int $userId, string $parameterId, int $order): void
    {
        try {
            $check = "SELECT 1 FROM user_parameter_order WHERE id_user = :uid AND parameter_id = :pid";
            $st = $this->pdo->prepare($check);
            $st->execute([':uid' => $userId, ':pid' => $parameterId]);

            if ($st->fetchColumn()) {
                $sql = "UPDATE user_parameter_order 
                        SET display_order = :ord, updated_at = NOW() 
                        WHERE id_user = :uid AND parameter_id = :pid";
            } else {
                $sql = "INSERT INTO user_parameter_order (id_user, parameter_id, display_order, is_hidden, updated_at) 
                        VALUES (:uid, :pid, :ord, 0, NOW())";
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':uid' => $userId,
                ':pid' => $parameterId,
                ':ord' => $order
            ]);
        } catch (\PDOException $e) {
            // Silent fail
        }
    }
}
