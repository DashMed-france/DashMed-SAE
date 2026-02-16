<?php

declare(strict_types=1);

namespace modules\controllers;

use assets\includes\Database;
use modules\models\monitoring\MonitorPreferenceModel;
use modules\models\repositories\UserRepository;
use modules\services\UserLayoutService;
use modules\views\user\CustomizationView;
use modules\views\user\ProfileView;
use PDO;

/**
 * Class UserController
 *
 * Centralizes user-centric actions (profile, customization).
 *
 * Replaces: CustomizationController, ProfileController.
 *
 * @package DashMed\Modules\Controllers
 * @author DashMed Team
 * @license Proprietary
 */
class UserController
{
    /** @var PDO Database connection */
    private PDO $pdo;

    /** @var UserLayoutService Layout service */
    private UserLayoutService $layoutService;

    /** @var bool Test mode flag */
    protected bool $testMode = false;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Database connection (optional)
     */
    public function __construct(?PDO $pdo = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->pdo = $pdo ?? Database::getInstance();
        $prefModel = new MonitorPreferenceModel($this->pdo);
        $this->layoutService = new UserLayoutService($prefModel);
    }

    /**
     * Sets test mode.
     *
     * @param bool $mode
     */
    public function setTestMode(bool $mode): void
    {
        $this->testMode = $mode;
    }

    /**
     * Profile entry point (GET & POST).
     *
     * @return void
     */
    public function profile(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->profilePost();
        } else {
            $this->profileGet();
        }
    }

    /**
     * Displays the user profile.
     *
     * @return void
     */
    private function profileGet(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /?page=signup');
            exit;
        }

        $userEmail = isset($_SESSION['email']) && is_string($_SESSION['email']) ? $_SESSION['email'] : '';
        $user = $this->getUserByEmail($userEmail);
        $professions = $this->getAllProfessions();

        /** @var array{type: string, text: string}|null $msg */
        $msg = isset($_SESSION['profile_msg']) && is_array($_SESSION['profile_msg']) ? $_SESSION['profile_msg'] : null;
        unset($_SESSION['profile_msg']);

        $view = new ProfileView();
        $view->show($user, $professions, $msg);
    }

    /**
     * Processes profile update or account deletion (POST).
     *
     * @return void
     */
    private function profilePost(): void
    {
        if (!$this->isLoggedIn()) {
            if (!$this->testMode) {
                header('Location: /?page=signup');
                exit;
            }
            return;
        }

        $sessionCsrf = isset($_SESSION['csrf_profile']) && is_string($_SESSION['csrf_profile'])
            ? $_SESSION['csrf_profile']
            : '';
        $postCsrf = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
        if ($sessionCsrf === '' || $postCsrf === '' || !hash_equals($sessionCsrf, $postCsrf)) {
            $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Session expirée, réessayez.'];
            if (!$this->testMode) {
                header('Location: /?page=profile');
                exit;
            }
            return;
        }

        $rawAction = $_POST['action'] ?? 'update';
        $action = is_string($rawAction) ? $rawAction : 'update';

        if ($action === 'delete_account') {
            $this->handleDeleteAccount();
            return;
        }

        $rawFirst = $_POST['first_name'] ?? '';
        $first = trim(is_string($rawFirst) ? $rawFirst : '');
        $rawLast = $_POST['last_name'] ?? '';
        $last = trim(is_string($rawLast) ? $rawLast : '');
        $profId = $_POST['id_profession'] ?? null;

        if ($first === '' || $last === '') {
            $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Le prénom et le nom sont obligatoires.'];
            if (!$this->testMode) {
                header('Location: /?page=profile');
                exit;
            }
            return;
        }

        $validId = null;
        if ($profId !== null && $profId !== '') {
            $st = $this->pdo->prepare("SELECT id_profession FROM professions WHERE id_profession = :id");
            $st->execute([':id' => $profId]);
            $validId = $st->fetchColumn() ?: null;
            if ($validId === null) {
                $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Spécialité invalide.'];
                if (!$this->testMode) {
                    header('Location: /?page=profile');
                    exit;
                }
                return;
            }
        }

        $upd = $this->pdo->prepare("
            UPDATE users
               SET first_name = :f,
                   last_name = :l,
                   id_profession = :p
             WHERE email = :e
        ");
        $upd->execute([
            ':f' => $first,
            ':l' => $last,
            ':p' => $validId,
            ':e' => $_SESSION['email']
        ]);

        $_SESSION['profile_msg'] = ['type' => 'success', 'text' => 'Profil mis à jour'];

        if (!$this->testMode) {
            header('Location: /?page=profile');
            exit;
        }
    }

    /**
     * Handles account deletion.
     *
     * @return void
     */
    private function handleDeleteAccount(): void
    {
        $email = $_SESSION['email'] ?? null;
        if (!$email) {
            if (!$this->testMode) {
                header('Location: /?page=signup');
                exit;
            }
            return;
        }

        try {
            $this->pdo->beginTransaction();
            $del = $this->pdo->prepare("DELETE FROM users WHERE email = :e");
            $del->execute([':e' => $email]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('[UserController] Delete account failed: ' . $e->getMessage());
            $_SESSION['profile_msg'] = [
                'type' => 'error',
                'text' => "Impossible de supprimer le compte (contraintes en base ?)."
            ];
            if (!$this->testMode) {
                header('Location: /?page=profile');
                exit;
            }
            return;
        }

        if (!$this->testMode) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                $sessionName = session_name();
                if ($sessionName !== false) {
                    setcookie(
                        $sessionName,
                        '',
                        time() - 42000,
                        $params["path"],
                        $params["domain"],
                        $params["secure"],
                        $params["httponly"]
                    );
                }
            }
            session_destroy();

            header('Location: /?page=signup');
            exit;
        }
    }

    /**
     * Customization entry point (GET & POST).
     *
     * @return void
     */
    public function customization(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->customizationPost();
        } else {
            $this->customizationGet();
        }
    }

    /**
     * Displays the customization page.
     *
     * @return void
     */
    private function customizationGet(): void
    {
        $userId = $this->requireAuth();

        $data = $this->layoutService->buildWidgetsForCustomization($userId);

        /** @var array<int, array{id: string, name: string, category: string, x: int, y: int, w: int, h: int}> $widgets */
        $widgets = $data['widgets'];
        /** @var array<int, array{id: string, name: string}> $hidden */
        $hidden = $data['hidden'];

        (new CustomizationView())->show($widgets, $hidden);
    }

    /**
     * Processes customization save/reset (POST).
     *
     * @return void
     */
    private function customizationPost(): void
    {
        $userId = $this->requireAuth();

        if (!empty($_POST['reset_layout'])) {
            $this->layoutService->resetLayout($userId);
            header('Location: /?page=customization&success=1');
            exit;
        }

        $rawLayoutData = $_POST['layout_data'] ?? '';
        $layoutJson = is_string($rawLayoutData) ? $rawLayoutData : '';

        try {
            $validatedItems = $this->layoutService->validateAndParseLayoutData($layoutJson);

            if (!empty($validatedItems)) {
                $this->layoutService->saveLayout($userId, $validatedItems);
            }
        } catch (\InvalidArgumentException $e) {
            error_log('[UserController::customization] Invalid layout data: ' . $e->getMessage());
        }

        header('Location: /?page=customization&success=1');
        exit;
    }

    /**
     * Checks if user is logged in.
     *
     * @return bool
     */
    private function isLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Requires authentication and returns user ID.
     *
     * @return int User ID
     */
    private function requireAuth(): int
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
     * Retrieves user by email.
     *
     * @param string $email
     * @return array{first_name: string, last_name: string, email: string, id_profession: int|null, profession_name: string|null}|null
     */
    private function getUserByEmail(string $email): ?array
    {
        $sql = "SELECT
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.id_profession AS id_profession,
                    p.label_profession AS profession_name
                FROM users u
                LEFT JOIN professions p
                       ON p.id_profession = u.id_profession
                WHERE u.email = :e";
        $st = $this->pdo->prepare($sql);
        $st->execute([':e' => $email]);
        $result = $st->fetch(PDO::FETCH_ASSOC);
        if (!is_array($result)) {
            return null;
        }
        /** @var array{first_name: string, last_name: string, email: string, id_profession: int|null, profession_name: string|null} */
        return $result;
    }

    /**
     * Retrieves list of professions.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function getAllProfessions(): array
    {
        $st = $this->pdo->query("
            SELECT
                id_profession AS id,
                label_profession AS name
            FROM professions
            ORDER BY label_profession
        ");
        if ($st === false) {
            return [];
        }
        /** @var array<int, array{id: int, name: string}> */
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}

