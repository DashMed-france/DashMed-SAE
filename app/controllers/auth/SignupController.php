<?php
declare(strict_types=1);

namespace modules\controllers\auth;

use modules\models\userModel;
use modules\views\auth\signupView;

require_once __DIR__ . '/../../../assets/includes/database.php';

class SignupController
{
    private userModel $model;
    private \PDO $pdo;

    public function __construct(?userModel $model = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if ($model) {
            $this->model = $model;
            $this->pdo   = \Database::getInstance(); // pour getAllProfessions()
        } else {
            $pdo         = \Database::getInstance();
            $this->pdo   = $pdo;
            $this->model = new userModel($pdo);
        }
    }

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

    public function post(): void
    {
        error_log('[SignupController] POST /signup hit');

        if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string)$_POST['_csrf'])) {
            error_log('[SignupController] CSRF mismatch');
            $_SESSION['error'] = "Requête invalide. Réessaye.";
            $this->redirect('/?page=signup'); $this->terminate();
        }

        $last   = trim($_POST['last_name'] ?? '');
        $first  = trim($_POST['first_name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $pass   = (string)($_POST['password'] ?? '');
        $pass2  = (string)($_POST['password_confirm'] ?? '');

        $professionId = filter_input(
            INPUT_POST,
            'profession_id',
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        ) ?: null;

        $keepOld = function () use ($last, $first, $email, $professionId) {
            $_SESSION['old_signup'] = [
                'last_name'  => $last,
                'first_name' => $first,
                'email'      => $email,
                'profession' => $professionId
            ];
        };

        if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
            $_SESSION['error'] = "Tous les champs sont requis.";
            $keepOld(); $this->redirect('/?page=signup'); $this->terminate();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            $keepOld(); $this->redirect('/?page=signup'); $this->terminate();
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            $keepOld(); $this->redirect('/?page=signup'); $this->terminate();
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            $keepOld(); $this->redirect('/?page=signup'); $this->terminate();
        }
        if ($professionId === null) {
            $_SESSION['error'] = "Merci de sélectionner une spécialité.";
            $keepOld(); $this->redirect('/?page=signup'); $this->terminate();
        }

        try {
            $existing = $this->model->getByEmail($email);
            if ($existing) {
                $_SESSION['error'] = "Un compte existe déjà avec cet email.";
                $keepOld(); $this->redirect('/?page=signup'); $this->terminate();
            }
        } catch (\Throwable $e) {
            error_log('[SignupController] getByEmail error: ' . $e->getMessage());
            $_SESSION['error'] = "Erreur interne (GE)."; // court message pour l’UI
            $keepOld(); $this->redirect('/?page=signup'); $this->terminate();
        }

        try {
            $payload = [
                'first_name'    => $first,
                'last_name'     => $last,
                'email'         => $email,
                'password'      => $pass,            // hashé côté modèle
                'profession_id' => $professionId,
                'admin_status'  => 0,
                'birth_date'    => null,
                'created_at'    => date('Y-m-d H:i:s'),
            ];

            $userId = $this->model->create($payload);

            if (!is_int($userId) && !ctype_digit((string)$userId)) {
                error_log('[SignupController] create() did not return a numeric id. Got: ' . var_export($userId, true));
                throw new \RuntimeException('Invalid returned user id');
            }
            $userId = (int)$userId;
            if ($userId <= 0) {
                error_log('[SignupController] create() returned non-positive id: ' . $userId);
                throw new \RuntimeException('Insert failed or returned 0');
            }
        } catch (\Throwable $e) {
            error_log('[SignupController] SQL/Model error on create: ' . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de la création du compte.";
            $keepOld(); $this->redirect('/?page=signup'); $this->terminate();
        }

        // 6) Session + redirection
        $_SESSION['user_id']        = $userId;
        $_SESSION['email']          = $email;
        $_SESSION['first_name']     = $first;
        $_SESSION['last_name']      = $last;
        $_SESSION['profession_id']  = $professionId;
        $_SESSION['admin_status']   = 0;
        $_SESSION['username']       = $email;

        error_log('[SignupController] Signup OK for ' . $email . ' id=' . $userId);

        $this->redirect('/?page=homepage');
        $this->terminate();
    }

    protected function redirect(string $location): void
    {
        header('Location: ' . $location);
    }

    protected function terminate(): void { exit; }

    protected function isUserLoggedIn(): bool { return isset($_SESSION['email']); }

    private function getAllProfessions(): array
    {
        $st = $this->pdo->query(
            "SELECT id_profession, label_profession
             FROM professions
             ORDER BY label_profession"
        );
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }
}