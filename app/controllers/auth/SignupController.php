<?php

/**
 * DashMed — Signup Controller
 *
 * This file defines the controller responsible for displaying the signup view
 * and handling form submissions for creating new user accounts.
 *
 * @package   DashMed\Modules\Controllers\auth
 * @author    DashMed Team
 * @license   Proprietary
 * @link      /?page=signup
 */

declare(strict_types=1);

namespace modules\controllers\auth;

use Database;
use modules\models\UserModel;
use modules\views\auth\SignupView;
use PDO;
use RuntimeException;
use Throwable;

require_once __DIR__ . '/../../../assets/includes/database.php';


/**
 * Manages the signup (registration) process.
 *
 * Responsibilities:
 *  - Start a session if not already active
 *  - Provide GET endpoint to display the signup form
 *  - Provide POST endpoint to validate data and create a user
 *  - Redirect authenticated users to the dashboard
 *
 * @see UserModel
 * @see SignupView
 */
class SignupController
{
    /**
     * Business logic/model for signup and user operations.
     *
     * @var UserModel
     */
    private UserModel $model;

    /**
     * PDO database instance.
     *
     * @var \PDO
     */
    private \PDO $pdo;

    /**
     * Controller constructor.
     *
     * Starts the session if necessary, retrieves a shared PDO instance via
     * the Database helper and instantiates the user model.
     *
     * @param UserModel|null $model Optional UserModel instance for dependency injection.
     */
    public function __construct(?UserModel $model = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if ($model) {
            $this->model = $model;
            $this->pdo = \Database::getInstance();
        } else {
            $pdo = \Database::getInstance();
            $this->pdo = $pdo;
            $this->model = new userModel($pdo);
        }
    }

    /**
     * Handles HTTP GET requests.
     *
     * If a user session already exists, redirects to the dashboard.
     * Otherwise, ensures a CSRF token is available and displays the signup view.
     *
     * @return void
     */
    public function get(): void
    {
        if ($this->isUserLoggedIn()) {
            $this->redirect('/?page=dashboard');
            $this->terminate();
        }

        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }

        $professions = $this->getAllProfessions();
        (new signupView())->show($professions);
    }

    /**
     * Handles HTTP POST requests.
     *
     * Validates submitted form fields (name, email, password and confirmation),
     * enforces minimum password security policy, verifies email uniqueness
     * and delegates account creation to the model. On success, initializes
     * the session and redirects the user; on failure, stores an error message
     * and preserves submitted data.
     *
     * Uses HTTP header-based redirections and temporary session data (flash)
     * to communicate validation results.
     *
     * Expected POST parameters:
     * - _csrf: CSRF token for request validation.
     * - first_name: User's first name.
     * - last_name: User's last name.
     * - email: User's email address.
     * - password: User's password (minimum 8 characters).
     * - password_confirm: Password confirmation.
     * - id_profession: Profession ID (must be a positive integer).
     *
     * @return void
     */
    public function post(): void
    {
        error_log('[SignupController] POST /signup hit');

        if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string) $_POST['_csrf'])) {
            error_log('[SignupController] CSRF mismatch');
            $_SESSION['error'] = "Requête invalide. Réessaye.";
            $this->redirect('/?page=signup');
            $this->terminate();
        }

        $last = trim($_POST['last_name'] ?? '');
        $first = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password_confirm'] ?? '');

        $professionId = isset($_POST['id_profession']) && $_POST['id_profession'] !== ''
            ? filter_var($_POST['id_profession'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : null;

        if ($professionId === false) {
            $professionId = null;
        }

        $keepOld = function () use ($last, $first, $email, $professionId) {
            $_SESSION['old_signup'] = [
                'last_name' => $last,
                'first_name' => $first,
                'email' => $email,
                'profession' => $professionId
            ];
        };

        if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
            $_SESSION['error'] = "Tous les champs sont requis.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }
        if ($professionId === null) {
            $_SESSION['error'] = "Merci de sélectionner une spécialité.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }

        try {
            $existing = $this->model->getByEmail($email);
            if ($existing) {
                $_SESSION['error'] = "Un compte existe déjà avec cet email.";
                $keepOld();
                $this->redirect('/?page=signup');
                $this->terminate();
            }
        } catch (\Throwable $e) {
            error_log('[SignupController] getByEmail error: ' . $e->getMessage());
            $_SESSION['error'] = "Erreur interne (GE).";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }

        try {
            $payload = [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'password' => $pass,
                'id_profession' => $professionId,
                'admin_status' => 0,
                'birth_date' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $userId = $this->model->create($payload);

            if (!is_int($userId) && !ctype_digit((string) $userId)) {
                error_log('[SignupController] create() did not return a numeric id. Got: ' . var_export($userId, true));
                throw new \RuntimeException('Invalid returned user id');
            }
            $userId = (int) $userId;
            if ($userId <= 0) {
                error_log('[SignupController] create() returned non-positive id: ' . $userId);
                throw new \RuntimeException('Insert failed or returned 0');
            }
        } catch (\Throwable $e) {
            error_log('[SignupController] SQL/Model error on create: ' . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de la création du compte.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }

        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $first;
        $_SESSION['last_name'] = $last;
        $_SESSION['id_profession'] = $professionId;
        $_SESSION['admin_status'] = 0;
        $_SESSION['username'] = $email;

        error_log('[SignupController] Signup OK for ' . $email . ' id=' . $userId);

        $this->redirect('/?page=homepage');
        $this->terminate();
    }

    /**
     * Redirects to the specified location.
     *
     * @param string $location Target URL for redirection.
     * @return void
     */
    protected function redirect(string $location): void
    {
        header('Location: ' . $location);
    }

    /**
     * Terminates script execution.
     *
     * @return never
     */
    protected function terminate(): never
    {
        exit;
    }

    /**
     * Checks if a user is currently logged in.
     *
     * @return bool True if user is authenticated, false otherwise.
     */
    protected function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Retrieves all available professions from the database.
     *
     * @return array Array of professions with id_profession and label_profession.
     */
    private function getAllProfessions(): array
    {
        $st = $this->pdo->query(
            "SELECT id_profession, label_profession
             FROM professions
             ORDER BY label_profession"
        );
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }
}