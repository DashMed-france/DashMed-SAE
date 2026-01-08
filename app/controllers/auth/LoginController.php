<?php

declare(strict_types=1);

namespace modules\controllers\auth;

use modules\models\userModel;
use modules\views\auth\LoginView;

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
        $pdo = \Database::getInstance();
        $this->model = new userModel($pdo);
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
        if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string) $_POST['_csrf'])) {
            $_SESSION['error'] = "Invalid Request. Try again. | Requête invalide. Réessaye.";
            header('Location: /?page=login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

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

        // Align with DB/Model | Aligne avec la BDD et le modèle
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
            setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
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
