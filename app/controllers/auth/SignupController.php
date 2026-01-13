<?php

declare(strict_types=1);

namespace modules\controllers\auth;

use Database;
use modules\models\UserModel;
use modules\views\auth\SignupView;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Class SignupController | Contrôleur d'Inscription
 *
 * Handles user registration/signup.
 * Gère le processus d'inscription des utilisateurs.
 *
 * - Display signup form
 * - Validate input
 * - Create user account
 *
 * @package DashMed\Modules\Controllers\Auth
 * @author DashMed Team
 * @license Proprietary
 */
class SignupController
{
    /** @var UserModel User model | Modèle utilisateur */
    private UserModel $model;

    /** @var PDO Database connection | Connexion BDD */
    private \PDO $pdo;

    /**
     * Constructor | Constructeur
     *
     * @param UserModel|null $model Optional model injection | Injection optionnelle du modèle
     */
    public function __construct(?UserModel $model = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if ($model) {
            $this->model = $model;
            $this->pdo = \Database::getInstance();
        } else {
            $pdo = \Database::getInstance();
            $this->pdo = $pdo;
            $this->model = new userModel($pdo);
        }
    }

    /**
     * Handles GET request: Display signup form.
     * Gère la requête GET : Affiche le formulaire d'inscription.
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

        $professions = $this->getAllProfessions();
        (new signupView())->show($professions);
    }

    /**
     * Handles POST request: Process signup submission.
     * Gère la requête POST : Traite la soumission de l'inscription.
     *
     * Validates input, creates user, initializes session.
     * Valide les données, crée l'utilisateur, initialise la session.
     *
     * @return void
     */
    public function post(): void
    {
        error_log('[SignupController] POST /signup hit');

        $sessionCsrf = isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf']) ? $_SESSION['_csrf'] : '';
        $postCsrf = isset($_POST['_csrf']) && is_string($_POST['_csrf']) ? $_POST['_csrf'] : '';
        if ($sessionCsrf !== '' && $postCsrf !== '' && !hash_equals($sessionCsrf, $postCsrf)) {
            error_log('[SignupController] CSRF mismatch');
            $_SESSION['error'] = "Invalid Request. Try again. | Requête invalide. Réessaye.";
            $this->redirect('/?page=signup');
            $this->terminate();
        }

        $rawLast = $_POST['last_name'] ?? '';
        $last = trim(is_string($rawLast) ? $rawLast : '');
        $rawFirst = $_POST['first_name'] ?? '';
        $first = trim(is_string($rawFirst) ? $rawFirst : '');
        $rawEmailPost = $_POST['email'] ?? '';
        $email = trim(is_string($rawEmailPost) ? $rawEmailPost : '');
        $pass = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $pass2 = isset($_POST['password_confirm']) && is_string($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

        // Read directly from $_POST for better testability
        $professionId = isset($_POST['id_profession']) && $_POST['id_profession'] !== ''
            ? filter_var($_POST['id_profession'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : null;

        if ($professionId === false) {
            $professionId = null;
        }

        $keepOld = function () use ($last, $first, $email, $professionId) {
            $_SESSION['old_signup'] = [
                'last_name' => $last,
                'first_name' => $first,
                'email' => $email,
                'profession' => $professionId
            ];
        };

        if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
            $_SESSION['error'] = "All fields required. | Tous les champs sont requis.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid Email. | Email invalide.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Passwords do not match. | Les mots de passe ne correspondent pas.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Password must be at least 8 chars. | Le mot de passe doit contenir au moins 8 caractères.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }
        if ($professionId === null) {
            $_SESSION['error'] = "Please select a specialty. | Merci de sélectionner une spécialité.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }

        try {
            $existing = $this->model->getByEmail($email);
            if ($existing) {
                $_SESSION['error'] = "Account already exists with this email. | Un compte existe déjà avec cet email.";
                $keepOld();
                $this->redirect('/?page=signup');
                $this->terminate();
            }
        } catch (\Throwable $e) {
            error_log('[SignupController] getByEmail error: ' . $e->getMessage());
            $_SESSION['error'] = "Internal Error (GE). | Erreur interne (GE).";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }

        try {
            $payload = [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'password' => $pass,
                'id_profession' => $professionId,
                'admin_status' => 0,
                'birth_date' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $userIdResult = $this->model->create($payload);

            if ($userIdResult <= 0) {
                error_log('[SignupController] create() returned non-positive id: ' . $userIdResult);
                throw new \RuntimeException('Insert failed or returned 0');
            }
            $userId = $userIdResult;
            $userId = $userIdResult;
            // Removed always false check as create throws exception if <= 0
        } catch (\Throwable $e) {
            error_log('[SignupController] SQL/Model error on create: ' . $e->getMessage());
            $_SESSION['error'] = "Account creation failed. | Erreur lors de la création du compte.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }

        // 6) Session + redirection
        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $first;
        $_SESSION['last_name'] = $last;
        $_SESSION['id_profession'] = $professionId;
        $_SESSION['admin_status'] = 0;
        $_SESSION['username'] = $email;

        error_log('[SignupController] Signup OK for ' . $email . ' id=' . $userId);

        $this->redirect('/?page=homepage');
        $this->terminate();
    }

    /**
     * Redirects to a location.
     * Redirige vers une destination.
     *
     * @param string $location
     */
    protected function redirect(string $location): void
    {
        header('Location: ' . $location);
    }

    /**
     * Terminates script execution.
     * Termine l'exécution du script.
     *
     * @return never
     */
    protected function terminate(): never
    {
        exit;
    }

    /**
     * Checks if user is logged in.
     * Vérifie si l'utilisateur est connecté.
     *
     * @return bool
     */
    protected function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Retrieves all professions.
     * Récupère toutes les professions.
     *
     * @return array<int, array{id_profession: int, label_profession: string}>
     */
    private function getAllProfessions(): array
    {
        $st = $this->pdo->query(
            "SELECT id_profession, label_profession
             FROM professions
             ORDER BY label_profession"
        );
        if ($st === false) {
            return [];
        }
        /** @var array<int, array{id_profession: int, label_profession: string}> */
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }
}
