<?php

namespace modules\controllers\pages;

use modules\models\UserModel;
use modules\views\pages\SysadminView;
use PDO;

/**
 * Controller for managing the system administrator dashboard.
 *
 * This controller handles user account creation by administrators,
 * including validation, permission checks, and specialty assignment.
 */
class SysadminController
{
    /**
     * Business logic model for user operations.
     *
     * @var UserModel
     */
    private UserModel $model;

    /**
     * PDO database connection instance.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Constructor for SysadminController.
     *
     * Starts the session if necessary, retrieves a shared PDO instance via
     * the Database helper, and instantiates the user model.
     *
     * @param UserModel|null $model Optional UserModel instance for dependency injection
     */
    public function __construct(?UserModel $model = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if ($model) {
            $this->model = $model;
        } else {
            $pdo = \Database::getInstance();
            $this->model = new UserModel($pdo);
        }

        $this->pdo = \Database::getInstance();
        $this->model = $model ?? new UserModel($this->pdo);
    }

    /**
     * Handles GET requests for the system administrator dashboard.
     *
     * Displays the admin dashboard view if the user is logged in and has
     * admin privileges. Generates a CSRF token for form submissions.
     * Redirects to login if user is not authenticated or not an admin.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn() || !$this->isAdmin()) {
            $this->redirect('/?page=login');
            $this->terminate();
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        $specialties = $this->getAllSpecialties();
        (new SysadminView())->show($specialties);
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

    /**
     * Checks if the current user has administrator privileges.
     *
     * @return bool True if user is an admin, false otherwise
     */
    private function isAdmin(): bool
    {
        return isset($_SESSION['admin_status']) && (int) $_SESSION['admin_status'] === 1;
    }

    /**
     * Handles POST requests for user account creation.
     *
     * Validates form fields (name, email, password and confirmation),
     * applies minimum password security policy, verifies email uniqueness,
     * and delegates account creation to the model. On success, sets a
     * success message and redirects; on failure, logs an error message
     * and preserves submitted data.
     *
     * Uses HTTP header-based redirects and temporary session data (flash)
     * to communicate validation results.
     *
     * @return void
     */
    public function post(): void
    {
        error_log('[SysadminController] POST /sysadmin hit');

        if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string) $_POST['_csrf'])) {
            $_SESSION['error'] = "Requête invalide. Réessaye.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $last = trim($_POST['last_name'] ?? '');
        $first = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password_confirm'] ?? '');
        $profId = $_POST['id_profession'] ?? null;
        $admin = $_POST['admin_status'] ?? 0;

        if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
            $_SESSION['error'] = "Tous les champs sont requis.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        if ($this->model->getByEmail($email)) {
            $_SESSION['error'] = "Un compte existe déjà avec cet email.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        try {
            $userId = $this->model->create([
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'password' => $pass,
                'profession' => $profId,
                'admin_status' => $admin,
            ]);
        } catch (\Throwable $e) {
            error_log('[SysadminController] SQL error: ' . $e->getMessage());
            $_SESSION['error'] = "Impossible de créer le compte (email déjà utilisé ?)";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $_SESSION['success'] = "Compte créé avec succès pour {$email}";
        $this->redirect('/?page=sysadmin');
        $this->terminate();
    }

    /**
     * Redirects the user to a specified URL.
     *
     * Sends a Location header to redirect the client.
     *
     * @param string $location The URL to redirect to
     * @return void
     */
    protected function redirect(string $location): void
    {
        header('Location: ' . $location);
    }

    /**
     * Terminates script execution.
     *
     * Stops further code execution, typically after a redirect.
     *
     * @return void
     */
    protected function terminate(): void
    {
        exit;
    }

    /**
     * Retrieves all medical specialties from the database.
     *
     * Returns an ordered list of professions for use in dropdown menus
     * and form selections.
     *
     * @return array Array of specialties with 'id_profession' and 'label_profession' keys
     */
    private function getAllSpecialties(): array
    {
        $st = $this->pdo->query("SELECT id_profession, label_profession FROM professions ORDER BY label_profession");
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}