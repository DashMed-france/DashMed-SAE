<?php

namespace modules\controllers\pages;

use modules\models\userModel;
use modules\views\pages\sysadminView;
use PDO;


/**
 * Contrôleur du tableau de bord administrateur.
 */
class SysadminController
{
    /**
     * Logique métier / modèle pour les opérations de connexion et d’inscription.
     *
     * @var userModel
     */
    private userModel $model;

    /**
     * Instance PDO pour l'accès à la base de données.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Constructeur du contrôleur.
     *
     * Démarre la session si nécessaire, récupère une instance partagée de PDO via
     * l’aide de base de données (Database helper) et instancie le modèle de connexion.
     */
    public function __construct(?userModel $model = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if ($model) {
            $this->model = $model;
        } else {
            $pdo = \Database::getInstance();
            $this->model = new userModel($pdo);
        }

        $this->pdo = \Database::getInstance();
        $this->model = $model ?? new userModel($this->pdo);
    }

    /**
     * Affiche la vue du tableau de bord administrateur si l'utilisateur est connecté.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn() || !$this->isAdmin())
        {
            header('Location: /?page=login');
            $this->terminate();
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        $specialties = $this->getAllSpecialties();
        (new sysadminView())->show($specialties);
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

    private function isAdmin(): bool
    {
        return isset($_SESSION['admin_status']) && (int)$_SESSION['admin_status'] === 1;
    }

    /**
     * Gestionnaire des requêtes HTTP POST.
     *
     * Valide les champs du formulaire soumis (nom, e-mail, mot de passe et confirmation),
     * applique une politique de sécurité minimale sur le mot de passe, vérifie l’unicité
     * de l’adresse e-mail et délègue la création du compte au modèle. En cas de succès,
     * initialise la session et redirige l’utilisateur ; en cas d’échec, enregistre un
     * message d’erreur et conserve les données saisies.
     *
     * Utilise des redirections basées sur les en-têtes HTTP et des données de session
     * temporaires (flash) pour communiquer les résultats de la validation.
     *
     * @return void
     */

    public function post(): void
    {
        error_log('[SysadminController] POST /sysadmin hit');

        if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string)$_POST['_csrf'])) {
            $_SESSION['error'] = "Requête invalide. Réessaye.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }

        $last   = trim($_POST['last_name'] ?? '');
        $first  = trim($_POST['first_name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $pass   = (string)($_POST['password'] ?? '');
        $pass2  = (string)($_POST['password_confirm'] ?? '');
        $profId = $_POST['profession_id'] ?? null;
        $admin  = $_POST['admin_status'] ?? 0;

        if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
            $_SESSION['error'] = "Tous les champs sont requis.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }

        if ($this->model->getByEmail($email)) {
            $_SESSION['error'] = "Un compte existe déjà avec cet email.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }

        try {
            $userId = $this->model->create([
                'first_name'   => $first,
                'last_name'    => $last,
                'email'        => $email,
                'password'     => $pass,
                'profession'   => $profId,
                'admin_status' => $admin,
            ]);
        } catch (\Throwable $e) {
            error_log('[SysadminController] SQL error: '.$e->getMessage());
            $_SESSION['error'] = "Impossible de créer le compte (email déjà utilisé ?)";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }

        $_SESSION['success'] = "Compte créé avec succès pour {$email}";
        $this->redirect('/?page=sysadmin');
        $this->terminate();
    }


    protected function redirect(string $location): void
    {
        header('Location: ' . $location);
    }

    protected function terminate(): void
    {
        exit;
    }

    /**
     * Récupère la liste de toutes les spécialités médicales.
     *
     * @return array
     */
    private function getAllSpecialties(): array
    {
        $st = $this->pdo->query("SELECT id, name FROM medical_specialties ORDER BY name");
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}