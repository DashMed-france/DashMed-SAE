<?php

namespace modules\controllers\pages;

use modules\models\UserModel;
use modules\views\pages\SysadminView;
use PDO;

/**
 * Class SysadminController | Contrôleur Admin Système
 *
 * System Administrator Dashboard Controller.
 * Contrôleur du tableau de bord administrateur.
 *
 * @package DashMed\Modules\Controllers\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class SysadminController
{
    /**
     * Business logic / model for login and registration.
     * Logique métier / modèle pour les opérations de connexion et d’inscription.
     *
     * @var UserModel
     */
    private UserModel $model;

    /**
     * PDO Instance for database access.
     * Instance PDO pour l'accès à la base de données.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Controller Constructor.
     * Constructeur du contrôleur.
     *
     * Starts session if needed, retrieves shared PDO instance via Database helper,
     * and instantiates model.
     * Démarre la session si nécessaire, récupère une instance partagée de PDO via
     * l’aide de base de données (Database helper) et instancie le modèle de connexion.
     *
     * @param UserModel|null $model
     */
    public function __construct(?UserModel $model = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->pdo = \Database::getInstance();

        if ($model) {
            $this->model = $model;
        } else {
            $this->model = new UserModel($this->pdo);
        }
    }

    /**
     * Handles GET request: Display sysadmin dashboard.
     * Affiche la vue du tableau de bord administrateur si l'utilisateur est connecté.
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
     * Checks if user is logged in.
     * Vérifie si l'utilisateur est connecté.
     *
     * @return bool
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Checks if user is admin.
     * Vérifie si l'utilisateur est un administrateur.
     *
     * @return bool
     */
    private function isAdmin(): bool
    {
        $rawAdminStatus = $_SESSION['admin_status'] ?? 0;
        return is_numeric($rawAdminStatus) && (int) $rawAdminStatus === 1;
    }

    /**
     * Handles HTTP POST requests.
     * Gestionnaire des requêtes HTTP POST.
     *
     * Validates form fields (name, email, password, confirm), enforces minimum security,
     * checks email uniqueness and delegates account creation to model.
     * Valide les champs du formulaire soumis (nom, e-mail, mot de passe et confirmation),
     * applique une politique de sécurité minimale sur le mot de passe, vérifie l’unicité
     * de l’adresse e-mail et délègue la création du compte au modèle.
     *
     * Uses redirects and session flash data for results.
     * Utilise des redirections basées sur les en-têtes HTTP et des données de session
     * temporaires (flash) pour communiquer les résultats de la validation.
     *
     * @return void
     */
    public function post(): void
    {
        error_log('[SysadminController] POST /sysadmin hit');

        $sessionCsrf = isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf']) ? $_SESSION['_csrf'] : '';
        $postCsrf = isset($_POST['_csrf']) && is_string($_POST['_csrf']) ? $_POST['_csrf'] : '';
        if ($sessionCsrf !== '' && $postCsrf !== '' && !hash_equals($sessionCsrf, $postCsrf)) {
            $_SESSION['error'] = "Invalid request. Try again. | Requête invalide. Réessaye.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $rawLast = $_POST['last_name'] ?? '';
        $last = trim(is_string($rawLast) ? $rawLast : '');
        $rawFirst = $_POST['first_name'] ?? '';
        $first = trim(is_string($rawFirst) ? $rawFirst : '');
        $rawEmail = $_POST['email'] ?? '';
        $email = trim(is_string($rawEmail) ? $rawEmail : '');
        $pass = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $pass2 = isset($_POST['password_confirm']) && is_string($_POST['password_confirm'])
            ? $_POST['password_confirm']
            : '';
        $profId = $_POST['id_profession'] ?? null;
        $rawAdmin = $_POST['admin_status'] ?? 0;
        $admin = is_numeric($rawAdmin) ? (int) $rawAdmin : 0;

        if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
            $_SESSION['error'] = "All fields required. | Tous les champs sont requis.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email. | Email invalide.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Passwords do not match. | Les mots de passe ne correspondent pas.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Password must be at least 8 chars. | " .
                "Le mot de passe doit contenir au moins 8 caractères.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        if ($this->model->getByEmail($email)) {
            $_SESSION['error'] = "Account already exists with this email. | Un compte existe déjà avec cet email.";
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
            $_SESSION['error'] = "Account creation failed (email used?). | " .
                "Impossible de créer le compte (email déjà utilisé ?)";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $_SESSION['success'] = "Account created successfully for {$email} | Compte créé avec succès pour {$email}";
        $this->redirect('/?page=sysadmin');
        $this->terminate();
    }


    /**
     * Redirects to location.
     * Redirige vers une destination.
     *
     * @param string $location
     * @return void
     */
    protected function redirect(string $location): void
    {
        header('Location: ' . $location);
    }

    /**
     * Terminates execution.
     * Termine l'exécution.
     *
     * @return void
     */
    protected function terminate(): void
    {
        exit;
    }

    /**
     * Retrieves all medical specialties.
     * Récupère la liste de toutes les spécialités médicales.
     *
     * @return array<int, array{id_profession: int, label_profession: string}>
     */
    private function getAllSpecialties(): array
    {
        $st = $this->pdo->query("SELECT id_profession, label_profession FROM professions ORDER BY label_profession");
        if ($st === false) {
            return [];
        }
        /** @var array<int, array{id_profession: int, label_profession: string}> */
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
