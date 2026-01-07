<?php

namespace modules\controllers\pages;

use Database;
use modules\views\pages\profileView;
use PDO;
use Throwable;

require_once __DIR__ . '/../../../assets/includes/database.php';

/**
 * Controller for managing user profile page.
 *
 * This controller handles the display and modification of user profile information,
 * including personal data, profession selection, and account deletion.
 */
class ProfileController
{
    /**
     * PDO database connection instance.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Test mode flag for unit testing.
     *
     * When enabled, prevents HTTP redirects and session destruction
     * to facilitate testing.
     *
     * @var bool
     */
    protected bool $testMode = false;

    /**
     * Sets the test mode flag.
     *
     * When test mode is enabled, HTTP redirects and session destruction
     * are disabled to allow proper unit testing.
     *
     * @param bool $mode True to enable test mode, false otherwise
     * @return void
     */
    public function setTestMode(bool $mode): void
    {
        $this->testMode = $mode;
    }

    /**
     * Constructor for ProfileController.
     *
     * Initializes the database connection and ensures a session is started.
     *
     * @param PDO|null $pdo Optional PDO instance for dependency injection
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getInstance();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Handles GET requests for the profile page.
     *
     * Retrieves user information and available professions, then displays
     * the profile view. Redirects to signup if user is not logged in.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=signup');
            exit;
        }

        $user = $this->getUserByEmail($_SESSION['email']);
        $professions = $this->getAllProfessions();

        $msg = $_SESSION['profile_msg'] ?? null;
        unset($_SESSION['profile_msg']);

        $view = new profileView();
        $view->show($user, $professions, $msg);
    }

    /**
     * Handles POST requests for profile updates and account deletion.
     *
     * Processes form submissions to update profile information or delete
     * the user account. Validates CSRF token and required fields. Redirects
     * with appropriate success or error messages.
     *
     * @return void
     */
    public function post(): void
    {
        if (!$this->isUserLoggedIn()) {
            if (!$this->testMode) {
                header('Location: /?page=signup');
                exit;
            }
            return;
        }

        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_profile'] ?? '', $_POST['csrf'])) {
            $_SESSION['profile_msg'] = ['type' => 'error','text' => 'Session expirée, réessayez.'];
            if (!$this->testMode) {
                header('Location: /?page=profile');
                exit;
            }
            return;
        }

        $action = $_POST['action'] ?? 'update';

        if ($action === 'delete_account') {
            $this->handleDeleteAccount();
            return;
        }

        $first  = trim($_POST['first_name'] ?? '');
        $last   = trim($_POST['last_name'] ?? '');
        $profId = $_POST['id_profession'] ?? null;

        if ($first === '' || $last === '') {
            $_SESSION['profile_msg'] = ['type' => 'error','text' => 'Le prénom et le nom sont obligatoires.'];
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
                $_SESSION['profile_msg'] = ['type' => 'error','text' => 'Spécialité invalide.'];
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

        $_SESSION['profile_msg'] = ['type' => 'success','text' => 'Profil mis à jour'];

        if (!$this->testMode) {
            header('Location: /?page=profile');
            exit;
        }
    }

    /**
     * Handles account deletion.
     *
     * Deletes the user account from the database within a transaction.
     * On success, destroys the session and redirects to signup. On failure,
     * rolls back the transaction and sets an error message.
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
            error_log('[Profile] Delete account failed: ' . $e->getMessage());
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
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();

            header('Location: /?page=signup');
            exit;
        }
    }

    /**
     * Retrieves user data by email address.
     *
     * Uses aliases to maintain compatibility with the view:
     * - u.id_profession AS id_profession
     * - p.label_profession AS profession_name
     *
     * @param string $email The user's email address
     * @return array|null User data array or null if not found
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
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Retrieves all available professions.
     *
     * Uses aliases 'id' and 'name' to match the view's expectations.
     * Results are ordered alphabetically by profession name.
     *
     * @return array Array of professions with 'id' and 'name' keys
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
        return $st->fetchAll(PDO::FETCH_ASSOC);
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