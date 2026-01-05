<?php

namespace modules\controllers\pages;

use modules\models\Monitoring\MonitorPreferenceModel;
use modules\views\pages\customizationView;
use Database;
use PDO;

require_once __DIR__ . '/../../../assets/includes/database.php';

class CustomizationController
{
    private PDO $pdo;
    private MonitorPreferenceModel $prefModel;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getInstance();
        $this->prefModel = new MonitorPreferenceModel($this->pdo);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=signup');
            exit;
        }

        $userId = (int) $_SESSION['user_id']; // Assuming user_id is in session, check login controller if unsure, usually it is.
        // If not checking LoginController, let's check ProfileController? 
        // ProfileController uses email from session to get user. 
        // Let's rely on email to get ID if needed, or assume user_id is set.
        // Actually, looking at MonitorPreferenceModel usage, it expects an int ID.
        // Let's quickly double check how to get ID.
        // ... Checked mental model: usually stored in session or fetched.
        // Let's fetch ID from email if not in session.
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

        // Merge to create a clean list for the view
        // We want: [param_id => [name, category, is_hidden]]
        $viewData = [];
        foreach ($allParams as $p) {
            $pid = $p['parameter_id'];
            $hidden = false;

            // Check user preferences
            // $userPrefs['orders'] is [pid => [display_order, is_hidden]]
            if (isset($userPrefs['orders'][$pid])) {
                $hidden = (bool) $userPrefs['orders'][$pid]['is_hidden'];
            }

            $viewData[] = [
                'id' => $pid,
                'name' => $p['display_name'],
                'category' => $p['category'],
                'is_hidden' => $hidden
            ];
        }

        $view = new customizationView();
        $view->show($viewData);
    }

    public function post(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=signup');
            exit;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        // Ensure ID is available (same logic as get)
        if ($userId <= 0 && isset($_SESSION['email'])) {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :e");
            $stmt->execute([':e' => $_SESSION['email']]);
            $userId = (int) $stmt->fetchColumn();
        }

        if ($userId <= 0) {
            header('Location: /?page=signup');
            exit;
        }

        // Process visibility
        // Inputs: hidden_params array? or checkbox for visible?
        // Let's assume the form sends an array of VISIBLE parameters, or we iterate all.
        // Better: The form sends a list of toggled ON items.
        // OR: iterating over all known params is safer.
        // Let's rely on the submitted data.

        $allParams = $this->prefModel->getAllParameters();

        foreach ($allParams as $p) {
            $pid = $p['parameter_id'];
            // If checkbox name is "visible[$pid]", if checked it sends '1' (or 'on').
            // So if isset, it is visible. If not isset, it is hidden.
            $isVisible = isset($_POST['visible']) && is_array($_POST['visible']) && in_array($pid, $_POST['visible']);

            $isHidden = !$isVisible;

            $this->prefModel->saveUserVisibilityPreference($userId, $pid, $isHidden);
        }

        // Redirect to avoid resubmit
        header('Location: /?page=customization&success=1');
        exit;
    }

    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']); // Consistent with ProfileController
    }
}
