<?php
/**
 * DashMed — Contrôleur de Connexion / Inscription
 *
 * Ce fichier définit le contrôleur responsable de l’affichage de la vue de connexion / inscription
 * et de la gestion des soumissions de formulaire pour la création d’un nouveau compte utilisateur.
 *
 * @package   DashMed\Modules\Controllers
 * @author    Équipe DashMed
 * @license   Propriétaire
 * @link      /?page=signin
 */

declare(strict_types=1);

namespace modules\controllers;

use modules\models\signinModel;
use modules\views\signinView;

require_once __DIR__ . '/../../assets/includes/database.php';


/**
 * Gère le processus de connexion (inscription).
 *
 * Responsabilités :
 *  - Démarrer une session (si elle n’est pas déjà active)
 *  - Fournir le point d’entrée GET pour afficher le formulaire de connexion
 *  - Fournir le point d’entrée POST pour valider les données et créer un utilisateur
 *  - Rediriger les utilisateurs authentifiés vers le tableau de bord
 *
 * @see \modules\models\signinModel
 * @see \modules\views\signinView
 */
class SigninController
{
    /**
     * Logique métier / modèle pour les opérations de connexion et d’inscription.
     *
     * @var signinModel
     */
    private signinModel $model;

    /**
     * Constructeur du contrôleur.
     *
     * Démarre la session si nécessaire, récupère une instance partagée de PDO via
     * l’aide de base de données (Database helper) et instancie le modèle de connexion.
     */
    public function __construct(?signinModel $model = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if ($model) {
            $this->model = $model;
        } else {
            $pdo = \Database::getInstance();
            $this->model = new signinModel($pdo);
        }
    }

    /**
     * Gestionnaire des requêtes HTTP GET.
     *
     * Si une session utilisateur existe déjà, redirige vers le tableau de bord.
     * Sinon, s’assure qu’un jeton CSRF est disponible et affiche la vue de connexion.
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
        (new signinView())->show();
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
        error_log('[SigninController] POST /signin hit');

        if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string)$_POST['_csrf'])) {
            $_SESSION['error'] = "Requête invalide. Réessaye.";
            $this->redirect('/?page=signin'); $this->terminate();
        }

        $last   = trim($_POST['last_name'] ?? '');
        $first  = trim($_POST['first_name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $pass   = (string)($_POST['password'] ?? '');
        $pass2  = (string)($_POST['password_confirm'] ?? '');

        $keepOld = function () use ($last, $first, $email) {
            $_SESSION['old_signin'] = [
                'last_name'  => $last,
                'first_name' => $first,
                'email'      => $email,
            ];
        };

        if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
            $_SESSION['error'] = "Tous les champs sont requis.";
            $keepOld();
            $this->redirect('/?page=signin'); $this->terminate();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            $this->redirect('/?page=signin'); $this->terminate();
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            $keepOld();
            $this->redirect('/?page=signin'); $this->terminate();
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            $keepOld();
            $this->redirect('/?page=signin'); $this->terminate();
        }

        if ($this->model->getByEmail($email)) {
            $_SESSION['error'] = "Un compte existe déjà avec cet email.";
            $keepOld();
            $this->redirect('/?page=signin'); $this->terminate();
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
            error_log('[SigninController] SQL error: '.$e->getMessage());
            $_SESSION['error'] = "Impossible de créer le compte (email déjà utilisé ?)";
            $keepOld();
            $this->redirect('/?page=signin'); $this->terminate();
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

    /**
     * Indique si un utilisateur est considéré comme connecté pour la session actuelle.
     *
     * @return bool True si une adresse e-mail d’utilisateur existe dans la session ; false sinon.
     */
    protected function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
