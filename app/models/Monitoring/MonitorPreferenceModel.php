<?php

namespace modules\models\Monitoring;

use Database;
use PDO;

/**
 * Model for managing user monitoring preferences.
 *
 * This model handles user preferences for chart types, display order,
 * and visibility of monitoring parameters.
 */
class MonitorPreferenceModel
{
    /**
     * PDO database connection instance.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Constructor for MonitorPreferenceModel.
     *
     * Initializes the database connection and sets PDO attributes
     * for error handling and fetch mode.
     *
     * @param PDO|null $pdo Optional PDO instance for dependency injection
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Saves chart type preference for a user and parameter.
     *
     * If a preference already exists, it updates it; otherwise, it creates
     * a new preference record.
     *
     * @param int $userId The user's unique identifier
     * @param string $parameterId The parameter identifier
     * @param string $chartType The selected chart type (line, bar, etc.)
     * @return void
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
        }
    }

    /**
     * Retrieves all preferences (charts and display order) for a user.
     *
     * Returns both chart type preferences and display order/visibility
     * preferences in a structured array.
     *
     * @param int $userId The user's unique identifier
     * @return array Associative array with 'charts' and 'orders' keys containing preference data,
     *               or arrays with empty values on error
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
     * Retrieves all available parameters (indicators) from the database.
     *
     * Returns a complete list of monitoring parameters ordered by category
     * and display name.
     *
     * @return array Array of parameter data, or empty array on error
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
     * Updates visibility preference for a parameter.
     *
     * If a preference record exists, updates the is_hidden flag; otherwise,
     * creates a new record with the specified visibility and next available
     * display order.
     *
     * @param int $userId The user's unique identifier
     * @param string $parameterId The parameter identifier
     * @param bool $isHidden Whether the parameter should be hidden
     * @return void
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
        }
    }

    /**
     * Updates multiple display orders in a single transaction.
     *
     * Deletes all existing display order preferences for the user and
     * recreates them with updated order values while preserving visibility
     * settings. Uses a transaction to ensure data integrity.
     *
     * @param int $userId The user's unique identifier
     * @param array $orders Associative array mapping parameter_id to new display order
     * @return void
     * @throws \PDOException If the transaction fails
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
     * Updates display order for a single parameter.
     *
     * If a preference record exists, updates the display order; otherwise,
     * creates a new record with the specified order and default visibility.
     *
     * @param int $userId The user's unique identifier
     * @param string $parameterId The parameter identifier
     * @param int $order The new display order value
     * @return void
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
        }
    }
}