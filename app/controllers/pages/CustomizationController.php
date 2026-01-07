<?php

declare(strict_types=1);

namespace modules\controllers\pages;

use Database;
use modules\models\Monitoring\MonitorPreferenceModel;
use modules\services\UserLayoutService;
use modules\views\pages\CustomizationView;
use PDO;

/**
 * Gère la personnalisation du dashboard (position, taille, visibilité des widgets).
 */
final class CustomizationController
{
    private PDO $pdo;
    private UserLayoutService $layoutService;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
        $prefModel = new MonitorPreferenceModel($this->pdo);
        $this->layoutService = new UserLayoutService($prefModel);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
    public function get(): void
    {
        $userId = $this->requireAuthenticatedUser();

        $data = $this->layoutService->buildWidgetsForCustomization($userId);

        (new CustomizationView())->show($data['widgets'], $data['hidden']);
    }
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
     * Vérifie l'authentification et retourne l'ID utilisateur.
     *
     * @throws \RuntimeException Si non authentifié (redirige avant)
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
     * Vérifie si c'est une demande de réinitialisation.
     */
    private function isResetRequest(): bool
    {
        return !empty($_POST['reset_layout']);
    }

    /**
     * Redirige vers la page avec message de succès.
     */
    private function redirectWithSuccess(): void
    {
        header('Location: /?page=customization&success=1');
        exit;
    }
}
