<?php

declare(strict_types=1);

namespace modules\controllers\auth;

use modules\models\userModel;
use modules\views\auth\LoginView;

/**
 * Controller for user authentication and session management.
 *
 * Handles login, logout operations, and session initialization.
 * Implements CSRF protection and credential verification.
 *
 * @package modules\controllers\auth
 */
class LoginController
{
    private UserModel $model;

    /**
     * Initializes the controller with its dependencies.
     * Starts the session if not already active and creates the user model instance.
     */
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $pdo = \Database::getInstance();
        $this->model = new userModel($pdo);
    }

    /**
     * Handles GET requests to display the login page.
     *
     * Redirects authenticated users to the dashboard.
     * Generates a CSRF token if none exists and displays the login form with available users.
     *
     * @return void
     */
    public function get(): void
    {
        if ($this->isUserLoggedIn()) {
            header('Location: /?page=dashboard');
            exit;
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }

        $users = $this->model->listUsersForLogin();
        (new LoginView())->show($users);
    }

    /**
     * Handles POST requests to process login attempts.
     *
     * Validates CSRF token, verifies credentials, and establishes user session.
     * Redirects to homepage on success or back to login with error message on failure.
     *
     * Expected POST parameters:
     * - _csrf: CSRF token for request validation.
     * - email: User email address.
     * - password: User password.
     *
     * @return void
     */
    public function post(): void
    {
        if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string) $_POST['_csrf'])) {
            $_SESSION['error'] = "Requête invalide. Réessaye.";
            header('Location: /?page=login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $_SESSION['error'] = "Email et mot de passe sont requis.";
            header('Location: /?page=login');
            exit;
        }

        $user = $this->model->verifyCredentials($email, $password);
        if (!$user) {
            $_SESSION['error'] = "Identifiants incorrects.";
            header('Location: /?page=login');
            exit;
        }

        $_SESSION['user_id'] = (int) $user['id_user'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['id_profession'] = $user['id_profession'];
        $_SESSION['profession_label'] = $user['profession_label'] ?? '';
        $_SESSION['admin_status'] = (int) $user['admin_status'];
        $_SESSION['username'] = $user['email'];

        header('Location: /?page=homepage');
        exit;
    }

    /**
     * Handles user logout.
     *
     * Destroys the session, clears all session data, and removes session cookies.
     * Redirects to the login page after logout completion.
     *
     * @return void
     */
    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
        }
        session_destroy();

        header('Location: /?page=login');
        exit;
    }

    /**
     * Checks if a user is currently logged in.
     *
     * @return bool True if user is authenticated, false otherwise.
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}