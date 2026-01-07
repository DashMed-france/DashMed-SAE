<?php

namespace modules\controllers\pages;

use modules\models\Monitoring\MonitorPreferenceModel;
use modules\views\pages\customizationView;
use Database;
use PDO;

/**
 * Controller for managing user customization preferences for monitoring parameters.
 *
 * This controller handles the display and modification of user preferences
 * for monitoring parameter visibility and display order.
 */
class CustomizationController
{
    /**
     * PDO database connection instance.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Model for managing monitor preferences.
     *
     * @var MonitorPreferenceModel
     */
    private MonitorPreferenceModel $prefModel;

    /**
     * Constructor for CustomizationController.
     *
     * Initializes the database connection, preference model, and ensures
     * a session is started.
     *
     * @param PDO|null $pdo Optional PDO instance for dependency injection
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getInstance();
        $this->prefModel = new MonitorPreferenceModel($this->pdo);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Handles GET requests for the customization page.
     *
     * Retrieves all monitoring parameters and user preferences, then displays
     * the customization view. Redirects to signup if user is not logged in.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=signup');
            exit;
        }

        $userId = (int) $_SESSION['user_id'];
        if (!isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :e");
            $stmt->execute([':e' => $_SESSION['email']]);
            $_SESSION['user_id'] = $stmt->fetchColumn();
        }
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if ($userId <= 0) {
            header('Location: /?page=signup');
            exit;
        }

        $allParams = $this->prefModel->getAllParameters();
        $userPrefs = $this->prefModel->getUserPreferences($userId);

        $viewData = [];
        foreach ($allParams as $p) {
            $pid = $p['parameter_id'];
            $hidden = false;

            $order = 999;
            if (isset($userPrefs['orders'][$pid])) {
                $hidden = (bool) $userPrefs['orders'][$pid]['is_hidden'];
                $order = (int) ($userPrefs['orders'][$pid]['display_order'] ?? 999);
            }

            $viewData[] = [
                'id' => $pid,
                'name' => $p['display_name'],
                'category' => $p['category'],
                'is_hidden' => $hidden,
                'display_order' => $order
            ];
        }

        $view = new customizationView();
        $view->show($viewData);
    }

    /**
     * Handles POST requests for saving customization preferences.
     *
     * Validates submitted display orders for duplicates, saves visibility
     * and order preferences, then redirects to the customization page.
     * Redirects to signup if user is not logged in.
     *
     * @return void
     */
    public function post(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=signup');
            exit;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0 && isset($_SESSION['email'])) {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :e");
            $stmt->execute([':e' => $_SESSION['email']]);
            $userId = (int) $stmt->fetchColumn();
        }

        if ($userId <= 0) {
            header('Location: /?page=signup');
            exit;
        }

        $orders = $_POST['display_order'] ?? [];
        $duplicates = [];
        $counts = array_count_values($orders);

        foreach ($orders as $pid => $val) {
            if ($counts[$val] > 1) {
                $duplicates[] = $pid;
            }
        }

        if (!empty($duplicates)) {
            $allParams = $this->prefModel->getAllParameters();
            $viewData = [];
            foreach ($allParams as $p) {
                $pid = $p['parameter_id'];

                $isVisible = isset($_POST['visible']) &&
                    is_array($_POST['visible']) && in_array($pid, $_POST['visible']);
                $orderRaw = $orders[$pid] ?? 999;

                $viewData[] = [
                    'id' => $pid,
                    'name' => $p['display_name'],
                    'category' => $p['category'],
                    'is_hidden' => !$isVisible,
                    'display_order' => (int) $orderRaw
                ];
            }

            $view = new customizationView();
            $view->show($viewData, $duplicates);
            return;
        }

        $allParams = $this->prefModel->getAllParameters();

        foreach ($allParams as $p) {
            $pid = $p['parameter_id'];
            $isVisible = isset($_POST['visible']) && is_array($_POST['visible']) && in_array($pid, $_POST['visible']);
            $this->prefModel->saveUserVisibilityPreference($userId, $pid, !$isVisible);
        }

        if (!empty($orders)) {
            $this->prefModel->updateUserDisplayOrdersBulk($userId, $orders);
        }

        header('Location: /?page=customization&success=1');
        exit;
    }

    /**
     * Checks if a user is currently logged in.
     *
     * Determines login status by checking for the presence of an email
     * in the session.
     *
     * @return bool True if user is logged in, false otherwise
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}