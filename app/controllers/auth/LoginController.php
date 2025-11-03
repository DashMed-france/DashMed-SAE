<?php
namespace modules\controllers\auth;

use modules\models\userModel;
use modules\views\auth\loginView;

require_once __DIR__ . '/../../../assets/includes/database.php';

/**
 * Contrôleur de gestion de l'authentification utilisateur.
 */
class LoginController
{
    /**
     * Modèle de gestion de connexion.
     *
     * @var userModel
     */
    private userModel $model;

    /**
     * Initialise le contrôleur et démarre la session si nécessaire.
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
     * Affiche la page de connexion ou redirige vers le tableau de bord si l'utilisateur est déjà connecté.
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
        (new loginView())->show($users);
    }

    /**
     * Traite la soumission du formulaire de connexion.
     *
     * @return void
     */
    public function post(): void
    {
        if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string)$_POST['_csrf'])) {
            $_SESSION['error'] = "Requête invalide. Réessaye.";
            header('Location: /?page=login');
            exit;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

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

        $_SESSION['user_id']      = (int)$user['id_user'];
        $_SESSION['email']        = $user['email'];
        $_SESSION['first_name']   = $user['first_name'];
        $_SESSION['last_name']    = $user['last_name'];
        $_SESSION['profession']   = $user['profession'];
        $_SESSION['admin_status'] = (int)$user['admin_status'];
        $_SESSION['username']     = $user['email'];

        header('Location: /?page=homepage');
        exit;
    }

    /**
     * Déconnecte l'utilisateur et détruit la session.
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
     * Vérifie si l'utilisateur est connecté.
     *
     * @return bool
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
