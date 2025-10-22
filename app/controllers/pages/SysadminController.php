<?php

namespace modules\controllers\pages;

use modules\models\userModel;
use modules\views\pages\sysadminView;


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
    }

    /**
     * Affiche la vue du tableau de bord administrateur si l'utilisateur est connecté.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn())
        {
            header('Location: /?page=login');
            $this->terminate();
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        } (new sysadminView())->show();
    }

    /**
     * Vérifie si l'utilisateur est connecté.
     *
     * @return bool
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);

        /* TODO

        Une fois que la structure de la base de donnée sera accessible, il sera nécéssaire de refaire cette fonction.
        Afin de non plus juste seulement vérifier si l'utilisateur est connecté mais s'il a les permissions d'administrateur.

        */
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
        error_log('[SignupController] POST /signup hit');

        if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string)$_POST['_csrf'])) {
            $_SESSION['error'] = "Requête invalide. Réessaye.";
            $this->redirect('/?page=signup'); $this->terminate();
        }

        $last   = trim($_POST['last_name'] ?? '');
        $first  = trim($_POST['first_name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $pass   = (string)($_POST['password'] ?? '');
        $pass2  = (string)($_POST['password_confirm'] ?? '');

        $keepOld = function () use ($last, $first, $email) {
            $_SESSION['old_signup'] = [
                'last_name'  => $last,
                'first_name' => $first,
                'email'      => $email,
            ];
        };

        if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
            $_SESSION['error'] = "Tous les champs sont requis.";
            $keepOld();
            $this->redirect('/?page=signup'); $this->terminate();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            $this->redirect('/?page=signup'); $this->terminate();
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            $keepOld();
            $this->redirect('/?page=signup'); $this->terminate();
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            $keepOld();
            $this->redirect('/?page=signup'); $this->terminate();
        }

        if ($this->model->getByEmail($email)) {
            $_SESSION['error'] = "Un compte existe déjà avec cet email.";
            $keepOld();
            $this->redirect('/?page=signup'); $this->terminate();
        }

        try {
            $userId = $this->model->create([
                'first_name'   => $first,
                'last_name'    => $last,
                'email'        => $email,
                'password'     => $pass,
                'profession'   => null,
                'admin_status' => 0,
            ]);
        } catch (\Throwable $e) {
            error_log('[SignupController] SQL error: '.$e->getMessage());
            $_SESSION['error'] = "Impossible de créer le compte (email déjà utilisé ?)";
            $keepOld();
            $this->redirect('/?page=signup'); $this->terminate();
        }

        $_SESSION['user_id']      = (int)$userId;
        $_SESSION['email']        = $email;
        $_SESSION['first_name']   = $first;
        $_SESSION['last_name']    = $last;
        $_SESSION['profession']   = null;
        $_SESSION['admin_status'] = 0;
        $_SESSION['username']     = $email;

        $this->redirect('/?page=homepage');
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
}