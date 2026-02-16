<?php

declare(strict_types=1);

namespace modules\controllers;

use assets\includes\Database;
use assets\includes\Mailer;
use modules\models\Repositories\UserRepository;
use modules\views\auth\LoginView;
use modules\views\auth\SignupView;
use modules\views\auth\PasswordView;
use modules\views\auth\MailerView;
use PDO;

/**
 * Class AuthController
 *
 * Centralizes all authentication-related actions.
 *
 * Replaces: LoginController, SignupController, LogoutController, PasswordController.
 *
 * @package DashMed\Modules\Controllers
 * @author DashMed Team
 * @license Proprietary
 */
class AuthController
{
    /** @var UserRepository User repository */
    private UserRepository $userRepo;

    /** @var PDO Database connection */
    private PDO $pdo;

    /**
     * Constructor
     *
     * Initializes session, DB connection, and user repository.
     */
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->pdo = Database::getInstance();
        $this->userRepo = new UserRepository($this->pdo);
    }

    /**
     * Handles login (GET & POST).
     *
     * @return void
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->loginPost();
        } else {
            $this->loginGet();
        }
    }

    /**
     * Displays login form.
     *
     * @return void
     */
    private function loginGet(): void
    {
        if ($this->isLoggedIn()) {
            header('Location: /?page=dashboard');
            exit;
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        $users = $this->userRepo->listUsersForLogin();
        (new LoginView())->show($users);
    }

    /**
     * Processes login submission.
     *
     * @return void
     */
    private function loginPost(): void
    {
        $sessionCsrf = isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf']) ? $_SESSION['_csrf'] : '';
        $postCsrf = isset($_POST['_csrf']) && is_string($_POST['_csrf']) ? $_POST['_csrf'] : '';
        if ($sessionCsrf !== '' && $postCsrf !== '' && !hash_equals($sessionCsrf, $postCsrf)) {
            $_SESSION['error'] = "Requête invalide. Veuillez réessayer.";
            header('Location: /?page=login');
            exit;
        }

        $rawEmail = $_POST['email'] ?? '';
        $email = trim(is_string($rawEmail) ? $rawEmail : '');
        $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

        if ($email === '' || $password === '') {
            $_SESSION['error'] = "Email et mot de passe requis.";
            header('Location: /?page=login');
            exit;
        }

        $user = $this->userRepo->verifyCredentials($email, $password);
        if (!$user) {
            $_SESSION['error'] = "Identifiants invalides.";
            header('Location: /?page=login');
            exit;
        }

        $_SESSION['user_id'] = $user->getId();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['first_name'] = $user->getFirstName();
        $_SESSION['last_name'] = $user->getLastName();
        $_SESSION['id_profession'] = $user->getIdProfession() ?? 0;
        $_SESSION['profession_label'] = $user->getProfessionLabel() ?? '';
        $_SESSION['admin_status'] = $user->getAdminStatus();
        $_SESSION['username'] = $user->getEmail();

        header('Location: /?page=homepage');
        exit;
    }

    /**
     * Handles signup (GET & POST).
     *
     * @return void
     */
    public function signup(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->signupPost();
        } else {
            $this->signupGet();
        }
    }

    /**
     * Displays signup form.
     *
     * @return void
     */
    private function signupGet(): void
    {
        if ($this->isLoggedIn()) {
            header('Location: /?page=dashboard');
            exit;
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        $professions = $this->getAllProfessions();
        (new SignupView())->show($professions);
    }

    /**
     * Processes signup submission.
     *
     * @return void
     */
    private function signupPost(): void
    {
        $sessionCsrf = isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf']) ? $_SESSION['_csrf'] : '';
        $postCsrf = isset($_POST['_csrf']) && is_string($_POST['_csrf']) ? $_POST['_csrf'] : '';
        if ($sessionCsrf !== '' && $postCsrf !== '' && !hash_equals($sessionCsrf, $postCsrf)) {
            $_SESSION['error'] = "Requête invalide. Veuillez réessayer.";
            header('Location: /?page=signup');
            exit;
        }

        $rawLast = $_POST['last_name'] ?? '';
        $last = trim(is_string($rawLast) ? $rawLast : '');
        $rawFirst = $_POST['first_name'] ?? '';
        $first = trim(is_string($rawFirst) ? $rawFirst : '');
        $rawEmailPost = $_POST['email'] ?? '';
        $email = trim(is_string($rawEmailPost) ? $rawEmailPost : '');
        $pass = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $pass2 = isset($_POST['password_confirm']) && is_string($_POST['password_confirm'])
            ? $_POST['password_confirm']
            : '';

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
            $_SESSION['error'] = "Tous les champs sont obligatoires.";
            $keepOld();
            header('Location: /?page=signup');
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            $keepOld();
            header('Location: /?page=signup');
            exit;
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            $keepOld();
            header('Location: /?page=signup');
            exit;
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            $keepOld();
            header('Location: /?page=signup');
            exit;
        }
        if ($professionId === null) {
            $_SESSION['error'] = "Veuillez sélectionner une spécialité.";
            $keepOld();
            header('Location: /?page=signup');
            exit;
        }

        try {
            $existing = $this->userRepo->getByEmail($email);
            if ($existing) {
                $_SESSION['error'] = "Un compte existe déjà avec cet email.";
                $keepOld();
                header('Location: /?page=signup');
                exit;
            }
        } catch (\Throwable $e) {
            error_log('[AuthController] getByEmail error: ' . $e->getMessage());
            $_SESSION['error'] = "Internal Error (GE).";
            $keepOld();
            header('Location: /?page=signup');
            exit;
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

            $userIdResult = $this->userRepo->create($payload);

            if ($userIdResult <= 0) {
                throw new \RuntimeException('Insert failed or returned 0');
            }
            $userId = $userIdResult;
        } catch (\Throwable $e) {
            error_log('[AuthController] SQL/Model error on create: ' . $e->getMessage());
            $_SESSION['error'] = "Échec de la création du compte.";
            $keepOld();
            header('Location: /?page=signup');
            exit;
        }

        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $first;
        $_SESSION['last_name'] = $last;
        $_SESSION['id_profession'] = $professionId;
        $_SESSION['admin_status'] = 0;
        $_SESSION['username'] = $email;

        header('Location: /?page=homepage');
        exit;
    }

    /**
     * Logs out the user, destroys session, redirects.
     *
     * @return void
     */
    public function logout(): void
    {
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
     * Handles password reset (GET & POST).
     *
     * @return void
     */
    public function password(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->passwordPost();
        } else {
            $this->passwordGet();
        }
    }

    /**
     * Displays password reset form.
     *
     * @return void
     */
    private function passwordGet(): void
    {
        if ($this->isLoggedIn()) {
            header('Location: /?page=dashboard');
            return;
        }

        /** @var array{type: string, text: string}|null $msg */
        $msg = isset($_SESSION['pw_msg']) && is_array($_SESSION['pw_msg']) ? $_SESSION['pw_msg'] : null;
        unset($_SESSION['pw_msg']);

        $view = new PasswordView();
        $view->show($msg);
    }

    /**
     * Processes password reset POST.
     *
     * @return void
     */
    private function passwordPost(): void
    {
        if ($this->isLoggedIn()) {
            header('Location: /?page=dashboard');
            return;
        }

        $action = $_POST['action'] ?? '';
        if ($action === 'send_code') {
            $this->handleSendCode();
        } elseif ($action === 'reset_password') {
            $this->handleReset();
        } else {
            $_SESSION['pw_msg'] = ['type' => 'error', 'text' => 'Action inconnue.'];
            header('Location: /?page=password');
        }
    }

    /**
     * Sends the reset code via email.
     *
     * @return void
     */
    private function handleSendCode(): void
    {
        $rawEmail = $_POST['email'] ?? '';
        $email = trim(is_string($rawEmail) ? $rawEmail : '');
        $generic = "Si un compte correspond, un code de réinitialisation a été envoyé.";

        if ($email === '') {
            $_SESSION['pw_msg'] = ['type' => 'error', 'text' => 'Email requis.'];
            header('Location: /?page=password');
            return;
        }

        $st = $this->pdo->prepare("SELECT id_user, email, first_name FROM users WHERE email = :e LIMIT 1");
        $st->execute([':e' => $email]);
        $user = $st->fetch();

        $_SESSION['pw_msg'] = ['type' => 'info', 'text' => $generic];

        $token = bin2hex(random_bytes(16));
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);
        $expires = (new \DateTime('+20 minutes'))->format('Y-m-d H:i:s');

        $rawAppUrl = $_ENV['APP_URL'] ?? '';
        $appUrl = rtrim(is_string($rawAppUrl) ? $rawAppUrl : '', '/');
        if (empty($appUrl)) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            $protocol = $isHttps ? 'https' : 'http';
            $host = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST'])
                ? $_SERVER['HTTP_HOST']
                : 'localhost';
            $appUrl = $protocol . '://' . $host;
        }
        $emailLink = $appUrl . "/?page=password&token={$token}&code={$code}";
        $redirectLink = $appUrl . "/?page=password&token={$token}";

        if (is_array($user) && isset($user['id_user'], $user['email'])) {
            $upd = $this->pdo->prepare(
                "UPDATE users
                 SET reset_token=:t, reset_code_hash=:c, reset_expires=:e
                 WHERE id_user=:id"
            );
            $upd->execute([':t' => $token, ':c' => $codeHash, ':e' => $expires, ':id' => $user['id_user']]);

            $tpl = new MailerView();
            $html = $tpl->show($code, $emailLink);

            try {
                $userEmail = $user['email'];
                if (is_string($userEmail) && $userEmail !== '') {
                    $mailer = new Mailer();
                    $mailer->send($userEmail, 'Your reset code', $html);
                }
            } catch (\Throwable $e) {
                error_log('[AuthController] Mail send failed: ' . $e->getMessage());
            }
        }

        header('Location: ' . $redirectLink);
        return;
    }

    /**
     * Resets the password using the code.
     *
     * @return void
     */
    private function handleReset(): void
    {
        $rawToken = $_POST['token'] ?? '';
        $token = is_string($rawToken) ? $rawToken : '';
        $rawCode = $_POST['code'] ?? '';
        $code = is_string($rawCode) ? $rawCode : '';
        $rawPass = $_POST['password'] ?? '';
        $pass = is_string($rawPass) ? $rawPass : '';

        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            $_SESSION['pw_msg'] = ['type' => 'error', 'text' => 'Lien/token invalide.'];
            header('Location: /?page=password');
            return;
        }
        if (strlen($pass) < 8) {
            $_SESSION['pw_msg'] = ['type' => 'error', 'text' => 'Mot de passe trop court (min 8).'];
            header('Location: /?page=password&token=' . $token);
            return;
        }

        $st = $this->pdo->prepare(
            "SELECT id_user, reset_code_hash, reset_expires
             FROM users
             WHERE reset_token=:t LIMIT 1"
        );
        $st->execute([':t' => $token]);
        $u = $st->fetch();

        if (!is_array($u) || !isset($u['reset_expires'], $u['reset_code_hash'], $u['id_user'])) {
            $_SESSION['pw_msg'] = ['type' => 'error', 'text' => 'Code expiré ou invalide.'];
            header('Location: /?page=password');
            return;
        }

        $resetExpires = is_string($u['reset_expires']) ? $u['reset_expires'] : '';
        $resetCodeHash = is_string($u['reset_code_hash']) ? $u['reset_code_hash'] : '';

        if ($resetExpires === '' || new \DateTime($resetExpires) < new \DateTime()) {
            $_SESSION['pw_msg'] = ['type' => 'error', 'text' => 'Code expiré ou invalide.'];
            header('Location: /?page=password');
            return;
        }

        if (!password_verify($code, $resetCodeHash)) {
            $_SESSION['pw_msg'] = ['type' => 'error', 'text' => 'Code incorrect.'];
            header('Location: /?page=password&token=' . $token);
            return;
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $this->pdo->beginTransaction();
        $upd = $this->pdo->prepare(
            "UPDATE users
             SET password=:p, reset_token=NULL, reset_code_hash=NULL, reset_expires=NULL
             WHERE id_user=:id"
        );
        $upd->execute([':p' => $hash, ':id' => $u['id_user']]);
        $this->pdo->commit();

        $_SESSION['pw_msg'] = ['type' => 'success', 'text' => 'Mot de passe mis à jour. Vous pouvez vous connecter.'];
        header('Location: /?page=login');
    }

    /**
     * Checks if user is logged in.
     *
     * @return bool
     */
    private function isLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Retrieves all professions.
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
