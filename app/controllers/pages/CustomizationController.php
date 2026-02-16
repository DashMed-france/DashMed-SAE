<?php

declare(strict_types=1);

namespace modules\controllers\pages;

use assets\includes\Database;
use modules\models\Monitoring\MonitorPreferenceModel;
use modules\services\UserLayoutService;
use modules\views\pages\CustomizationView;
use PDO;

/**
 * Class CustomizationController
 *
 * Manages dashboard customization (widget position, size, visibility).
 *
 * @package DashMed\Modules\Controllers\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class CustomizationController
{
    /** @var PDO Database connection */
    private PDO $pdo;

    /** @var UserLayoutService Layout service */
    private UserLayoutService $layoutService;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Database connection (optional)
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
        $prefModel = new MonitorPreferenceModel($this->pdo);
        $this->layoutService = new UserLayoutService($prefModel);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Handles GET request: Display customization page.
     *
     * @return void
     */
    public function get(): void
    {
        $userId = $this->requireAuthenticatedUser();

        $data = $this->layoutService->buildWidgetsForCustomization($userId);

        /** @var array<int, array{id: string, name: string, category: string, x: int, y: int, w: int, h: int}> $widgets */
        $widgets = $data['widgets'];
        /** @var array<int, array{id: string, name: string}> $hidden */
        $hidden = $data['hidden'];

        (new CustomizationView())->show($widgets, $hidden);
    }

    /**
     * Handles POST request: Save or reset layout.
     *
     * @return void
     */
    public function post(): void
    {
        $userId = $this->requireAuthenticatedUser();

        if ($this->isResetRequest()) {
            $this->layoutService->resetLayout($userId);
            $this->redirectWithSuccess();
        }

        $rawLayoutData = $_POST['layout_data'] ?? '';
        $layoutJson = is_string($rawLayoutData) ? $rawLayoutData : '';

        try {
            $validatedItems = $this->layoutService->validateAndParseLayoutData($layoutJson);

            if (!empty($validatedItems)) {
                $this->layoutService->saveLayout($userId, $validatedItems);
            }
        } catch (\InvalidArgumentException $e) {
            error_log('[CustomizationController] Invalid layout data: ' . $e->getMessage());
        }

        $this->redirectWithSuccess();
    }

    /**
     * Verifies authentication and returns user ID.
     *
     * @return int User ID
     * @throws \RuntimeException If not authenticated (redirects before)
     */
    private function requireAuthenticatedUser(): int
    {
        if (!isset($_SESSION['email'])) {
            header('Location: /?page=signup');
            exit;
        }

        if (isset($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }

        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $_SESSION['email']]);
        $userId = (int) ($stmt->fetchColumn() ?: 0);

        if ($userId <= 0) {
            header('Location: /?page=signup');
            exit;
        }

        $_SESSION['user_id'] = $userId;

        return $userId;
    }

    /**
     * Checks if it is a reset request.
     *
     * @return bool
     */
    private function isResetRequest(): bool
    {
        return !empty($_POST['reset_layout']);
    }

    /**
     * Redirects to page with success message.
     *
     * @return void
     */
    private function redirectWithSuccess(): void
    {
        header('Location: /?page=customization&success=1');
        exit;
    }
}
