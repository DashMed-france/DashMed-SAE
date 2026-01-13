<?php

declare(strict_types=1);

namespace modules\models\Monitoring;

use Database;
use PDO;
use PDOException;

/**
 * Class MonitorPreferenceModel | Classe MonitorPreferenceModel
 *
 * Manages user preferences for monitoring.
 * Modèle de gestion des préférences utilisateur pour le monitoring.
 *
 * Handles two types of preferences:
 * - Chart preferences (chart type per parameter)
 * - Dashboard layout (position, size, visibility of widgets)
 *
 * Gère deux types de préférences :
 * - Préférences de graphiques (type de chart par paramètre)
 * - Layout du dashboard (position, taille, visibilité des widgets)
 *
 * @package DashMed\Modules\Models\Monitoring
 * @author DashMed Team
 * @license Proprietary
 */
class MonitorPreferenceModel
{
    /** @var PDO Database connection | Connexion BDD */
    private PDO $pdo;

    /** @var bool Flag to check if layout columns exist | Indicateur de vérification des colonnes layout */
    private bool $layoutColumnsChecked = false;

    /**
     * Constructor | Constructeur
     *
     * @param PDO|null $pdo
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Saves user chart preference for a parameter.
     * Enregistre la préférence de graphique pour un utilisateur et un paramètre donné.
     *
     * @param int $userId User ID | ID de l'utilisateur
     * @param string $parameterId Parameter ID | ID du paramètre
     * @param string $chartType Chart type (line, bar, etc.) | Type de graphique
     */
    public function saveUserChartPreference(int $userId, string $parameterId, string $chartType): void
    {
        try {
            $exists = $this->pdo->prepare(
                'SELECT 1 FROM user_parameter_chart_pref WHERE id_user = :uid AND parameter_id = :pid'
            );
            $exists->execute([':uid' => $userId, ':pid' => $parameterId]);

            $sql = $exists->fetchColumn()
                ? 'UPDATE user_parameter_chart_pref SET chart_type = :ctype, updated_at = NOW() WHERE id_user = :uid AND parameter_id = :pid'
                : 'INSERT INTO user_parameter_chart_pref (id_user, parameter_id, chart_type, updated_at) VALUES (:uid, :pid, :ctype, NOW())';

            $this->pdo->prepare($sql)->execute([
                ':uid' => $userId,
                ':pid' => $parameterId,
                ':ctype' => $chartType,
            ]);
        } catch (PDOException $e) {
            error_log('[MonitorPreferenceModel] saveUserChartPreference error: ' . $e->getMessage());
        }
    }

    /**
     * Retrieves all preferences (charts, order) for a user.
     * Récupère toutes les préférences (graphiques, ordre) pour un utilisateur.
     *
     * @param int $userId User ID | ID de l'utilisateur
     * @return array{charts: array<string, string>, orders: array<string, array<string, mixed>>} Associative array ['charts' => ..., 'orders' => ...] | Tableau associatif
     */
    public function getUserPreferences(int $userId): array
    {
        try {
            $sqlChart = 'SELECT parameter_id, chart_type FROM user_parameter_chart_pref WHERE id_user = :uid';
            $stChart = $this->pdo->prepare($sqlChart);
            $stChart->execute([':uid' => $userId]);
            $chartPrefs = $stChart->fetchAll(PDO::FETCH_KEY_PAIR);

            $this->ensureLayoutColumns();
            $sqlOrder = 'SELECT parameter_id, display_order, is_hidden FROM user_parameter_order WHERE id_user = :uid ORDER BY display_order';
            $stOrder = $this->pdo->prepare($sqlOrder);
            $stOrder->execute([':uid' => $userId]);
            $orderPrefs = $stOrder->fetchAll(PDO::FETCH_UNIQUE);

            return ['charts' => $chartPrefs, 'orders' => $orderPrefs];
        } catch (PDOException $e) {
            error_log('[MonitorPreferenceModel] getUserPreferences error: ' . $e->getMessage());
            return ['charts' => [], 'orders' => []];
        }
    }

    /**
     * Retrieves all available monitoring parameters.
     * Récupère tous les paramètres de monitoring disponibles.
     *
     * @return array<int, array{parameter_id: string, display_name: string, category: string, ...}>
     */
    public function getAllParameters(): array
    {
        try {
            $sql = 'SELECT * FROM parameter_reference ORDER BY category, display_name';
            $stmt = $this->pdo->query($sql);
            if ($stmt === false) {
                return [];
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[MonitorPreferenceModel] getAllParameters error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Saves user's complete layout.
     * Sauvegarde le layout complet d'un utilisateur.
     *
     * @param int $userId User ID | ID utilisateur
     * @param array<int, array{id: string, x: int, y: int, w: int, h: int, visible: bool}> $layoutItems
     */
    public function saveUserLayoutSimple(int $userId, array $layoutItems): void
    {
        if (empty($layoutItems)) {
            return;
        }

        $this->ensureLayoutColumns();

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare('DELETE FROM user_parameter_order WHERE id_user = :uid')
                ->execute([':uid' => $userId]);

            $insert = $this->pdo->prepare(
                'INSERT INTO user_parameter_order 
                (id_user, parameter_id, display_order, is_hidden, grid_x, grid_y, grid_w, grid_h, updated_at) 
                VALUES (:uid, :pid, :ord, :hid, :x, :y, :w, :h, NOW())'
            );

            foreach ($layoutItems as $ord => $item) {
                $pid = $item['id'];
                if ($pid === '') {
                    continue;
                }

                $insert->execute([
                    ':uid' => $userId,
                    ':pid' => $pid,
                    ':x' => (int) $item['x'],
                    ':y' => (int) $item['y'],
                    ':w' => max(4, (int) $item['w']),
                    ':h' => max(3, (int) $item['h']),
                    ':hid' => empty($item['visible']) ? 1 : 0,
                    ':ord' => $ord + 1,
                ]);
            }

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('[MonitorPreferenceModel] saveUserLayoutSimple error: ' . $e->getMessage());
        }
    }

    /**
     * Retrieves user's saved layout.
     * Récupère le layout sauvegardé d'un utilisateur.
     *
     * @param int $userId User ID | ID utilisateur
     * @return array<int, array{parameter_id: string, display_order: int, is_hidden: int, grid_x: int, grid_y: int, grid_w: int, grid_h: int}>
     */
    public function getUserLayoutSimple(int $userId): array
    {
        try {
            $this->ensureLayoutColumns();

            $st = $this->pdo->prepare(
                'SELECT parameter_id, display_order, is_hidden, grid_x, grid_y, grid_w, grid_h 
                FROM user_parameter_order 
                WHERE id_user = :uid 
                ORDER BY display_order'
            );
            $st->execute([':uid' => $userId]);

            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[MonitorPreferenceModel] getUserLayoutSimple error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Resets user's layout.
     * Réinitialise le layout d'un utilisateur.
     *
     * @param int $userId User ID | ID utilisateur
     */
    public function resetUserLayoutSimple(int $userId): void
    {
        try {
            $this->pdo->prepare('DELETE FROM user_parameter_order WHERE id_user = :uid')
                ->execute([':uid' => $userId]);
        } catch (PDOException $e) {
            error_log('[MonitorPreferenceModel] resetUserLayoutSimple error: ' . $e->getMessage());
        }
    }

    /**
     * Ensures layout columns exist in the table.
     * S'assure que les colonnes de layout existent dans la table.
     *
     * Executed once per instance.
     * Exécuté une seule fois par instance.
     */
    private function ensureLayoutColumns(): void
    {
        if ($this->layoutColumnsChecked) {
            return;
        }

        try {
            $checkStmt = $this->pdo->query("SHOW COLUMNS FROM user_parameter_order LIKE 'grid_x'");

            if ($checkStmt === false || !$checkStmt->fetch()) {
                $this->pdo->exec(
                    'ALTER TABLE user_parameter_order 
                    ADD COLUMN grid_x INT DEFAULT 0, 
                    ADD COLUMN grid_y INT DEFAULT 0, 
                    ADD COLUMN grid_w INT DEFAULT 4, 
                    ADD COLUMN grid_h INT DEFAULT 3'
                );
            }

            $this->layoutColumnsChecked = true;
        } catch (PDOException $e) {
            error_log('[MonitorPreferenceModel] ensureLayoutColumns error: ' . $e->getMessage());
        }
    }
}
