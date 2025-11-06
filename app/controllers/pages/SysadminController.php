<?php

namespace modules\controllers\pages;

use modules\models\userModel;
use modules\models\patientModel;
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
    private patientModel $patientModel;

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
    public function __construct(?userModel $model = null, ?patientModel $patientModel = null)
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

        if ($patientModel) {
            $this->patientModel = $patientModel;
        } else {
            $pdo = \Database::getInstance();
            $this->patientModel = new patientModel($pdo);
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
            $this->redirect('/?page=login');
            $this->terminate();
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        $professions = $this->getAllprofessions();
        (new sysadminView())->show($professions);
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

        if (
            isset($_SESSION['_csrf'], $_POST['_csrf']) &&
            !hash_equals($_SESSION['_csrf'], (string)$_POST['_csrf'])
        ) {
            $_SESSION['error'] = "Requête invalide. Réessaye.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }

        $form = $_POST['_form'] ?? null;
        if (!$form) {
            $_SESSION['error'] = "Type de formulaire manquant.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }

        try {
            match ($form) {
                'create_user'    => $this->handleCreateUser(),
                'create_patient' => $this->handleCreatePatient(),
                default          => throw new \RuntimeException('Formulaire inconnu: '.$form),
            };
        } catch (\Throwable $e) {
            error_log('[SysadminController] POST error: '.$e->getMessage());
            $_SESSION['error'] = "Erreur interne.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }
    }

    private function handleCreateUser(): void
    {
        $last   = trim($_POST['last_name'] ?? '');
        $first  = trim($_POST['first_name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $pass   = (string)($_POST['password'] ?? '');
        $pass2  = (string)($_POST['password_confirm'] ?? '');
        $profId = $_POST['id_profession'] ?? null;
        $admin  = (int)($_POST['admin_status'] ?? 0);

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
            error_log('[SysadminController] SQL error (create_user): '.$e->getMessage());
            $_SESSION['error'] = "Impossible de créer le compte (email déjà utilisé ?)";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }

        $_SESSION['success'] = "Compte créé avec succès pour {$email}";
        $this->redirect('/?page=sysadmin'); $this->terminate();
    }

    private function handleCreatePatient(): void
    {
        $last   = trim($_POST['last_name'] ?? '');
        $first  = trim($_POST['first_name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $birth  = trim($_POST['birth_date'] ?? '');
        $weight = trim($_POST['weight'] ?? '');
        $height = trim($_POST['height'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $room   = trim($_POST['room'] ?? '');

        if ($room === '' || $last === '' || $first === '' || $email === '' || $gender === '' || $birth === '' || $description === '' || $height === '' || $weight === '') {
            $_SESSION['error'] = "Tous les champs du patient sont requis.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email patient invalide.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }

        if (!is_numeric($height) || !is_numeric($weight)) {
            $_SESSION['error'] = "Taille/poids doivent être numériques.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }

        try {
            $patientId = $this->patientModel->create([
                'first_name'       => $first,
                'last_name'        => $last,
                'email'            => $email,
                'birth_date'       => $birth,
                'weight'           => $weight,
                'height'           => $height,
                'gender'           => $gender,
                'status'           => $status,
                'description'      => $description,
                'room_id'          => $room,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => null,
            ]);
        } catch (\Throwable $e) {
            error_log('[SysadminController] SQL error (create_patient): '.$e->getMessage());
            $_SESSION['error'] = "Impossible de créer le patient.";
            $this->redirect('/?page=sysadmin'); $this->terminate();
        }

        $_SESSION['success'] = "Patient créé avec succès pour {$first} {$last}.";
        $this->redirect('/?page=sysadmin'); $this->terminate();
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
     * Liste des spécialités.
     * On ALIAS en 'id' / 'name' pour coller à la vue.
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
}