<?php

declare(strict_types=1);

namespace modules\controllers\auth;

use modules\models\UserModel;
use modules\views\auth\LoginView;
use assets\includes\Database;

/**
 * Class LoginController | Contrôleur de Connexion
 *
 * Handles user authentication/login process.
 * Gère le processus d'authentification/connexion des utilisateurs.
 *
 * @package DashMed\Modules\Controllers\Auth
 * @author DashMed Team
 * @license Proprietary
 */
class LoginController
{
    /** @var UserModel User model instance | Instance du modèle utilisateur */
    private UserModel $model;

    /**
     * Constructor | Constructeur
     *
     * Initializes session and user model.
     * Initialise la session et le modèle utilisateur.
     */
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $pdo = Database::getInstance();
        $this->model = new UserModel($pdo);
    }

    /**
     * Handles GET request: Display login form.
     * Gère la requête GET : Affiche le formulaire de connexion.
     *
     * Redirects to dashboard if already logged in.
     * Redirige vers le tableau de bord si déjà connecté.
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
     * Handles POST request: Process login submission.
     * Gère la requête POST : Traite la soumission du formulaire de connexion.
     *
     * HTTP params: email, password, _csrf
     *
     * @return void
     */
    public function post(): void
    {
        $sessionCsrf = isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf']) ? $_SESSION['_csrf'] : '';
        $postCsrf = isset($_POST['_csrf']) && is_string($_POST['_csrf']) ? $_POST['_csrf'] : '';
        if ($sessionCsrf !== '' && $postCsrf !== '' && !hash_equals($sessionCsrf, $postCsrf)) {
            $_SESSION['error'] = "Invalid Request. Try again. | Requête invalide. Réessaye.";
            header('Location: /?page=login');
            exit;
        }

        $rawEmail = $_POST['email'] ?? '';
        $email = trim(is_string($rawEmail) ? $rawEmail : '');
        $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

        if ($email === '' || $password === '') {
            $_SESSION['error'] = "Email and password required. | Email et mot de passe sont requis.";
            header('Location: /?page=login');
            exit;
        }

        $user = $this->model->verifyCredentials($email, $password);
        if (!$user) {
            $_SESSION['error'] = "Invalid Credentials. | Identifiants incorrects.";
            header('Location: /?page=login');
            exit;
        }

        $userId = $user['id_user'] ?? 0;
        $_SESSION['user_id'] = is_numeric($userId) ? (int) $userId : 0;
        $_SESSION['email'] = is_string($user['email']) ? $user['email'] : '';
        $_SESSION['first_name'] = is_string($user['first_name'] ?? null) ? $user['first_name'] : '';
        $_SESSION['last_name'] = is_string($user['last_name'] ?? null) ? $user['last_name'] : '';
        $idProf = $user['id_profession'] ?? 0;
        $_SESSION['id_profession'] = is_numeric($idProf) ? (int) $idProf : 0;
        $_SESSION['profession_label'] = is_string($user['profession_label'] ?? null) ? $user['profession_label'] : '';
        $admStatus = $user['admin_status'] ?? 0;
        $_SESSION['admin_status'] = is_numeric($admStatus) ? (int) $admStatus : 0;
        $_SESSION['username'] = $_SESSION['email'];

        header('Location: /?page=homepage');
        exit;
    }

    /**
     * Logs out the user.
     * Déconnecte l'utilisateur.
     *
     * Destroys session and cookies.
     * Détruit la session et les cookies.
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
            $sessionName = session_name();
            if ($sessionName !== false) {
                setcookie($sessionName, '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
            }
        }
        session_destroy();

        header('Location: /?page=login');
        exit;
    }

    /**
     * Checks if user is logged in.
     * Vérifie si l'utilisateur est connecté.
     *
     * @return bool
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
