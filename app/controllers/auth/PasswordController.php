<?php

namespace modules\controllers\auth;

use Database;
use DateTime;
use Mailer;
use modules\views\auth\mailerView;
use modules\views\auth\passwordView;
use PDO;
use Throwable;

/**
 * Controller for password reset management.
 *
 * Handles password reset requests, verification code generation and validation,
 * and secure password updates. Implements email-based verification workflow.
 *
 * @package modules\controllers\auth
 */
class PasswordController
{
    /**
     * PDO instance for database access.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Mailer service instance for sending emails.
     *
     * @var \Mailer
     */
    private \Mailer $mailer;

    /**
     * Initializes the controller with database connection and mailer service.
     * Starts the session if not already active.
     */
    public function __construct()
    {
        $this->pdo = \Database::getInstance();
        $this->mailer = new \Mailer();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Handles GET requests to display the password reset page.
     *
     * Redirects authenticated users to the dashboard.
     * Displays any pending messages from the session.
     *
     * @return void
     */
    public function get(): void
    {
        if ($this->isUserLoggedIn()) {
            header('Location: /?page=dashboard');
            return;
        }

        $msg = $_SESSION['pw_msg'] ?? null;
        unset($_SESSION['pw_msg']);

        $view = new passwordView();
        $view->show($msg);
    }

    /**
     * Handles POST requests for code sending or password reset.
     *
     * Routes the request to the appropriate handler based on the action parameter.
     *
     * Expected POST parameters:
     * - action: Either 'send_code' or 'reset_password'.
     *
     * @return void
     */
    public function post(): void
    {
        if ($this->isUserLoggedIn()) {
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
     * Handles sending the password reset verification code via email.
     *
     * Generates a unique token and 6-digit verification code, stores them securely,
     * and sends an email with reset instructions. Uses timing-safe operations to
     * prevent user enumeration attacks.
     *
     * Expected POST parameters:
     * - email: User's email address.
     *
     * @return void
     */
    private function handleSendCode(): void
    {
        $email = trim($_POST['email'] ?? '');
        $generic = "Si un compte correspond, un code de réinitialisation a été envoyé.";

        if ($email === '') {
            $_SESSION['pw_msg'] = ['type' => 'error','text' => 'Email requis.'];
            header('Location: /?page=password');
            return;
        }

        $st = $this->pdo->prepare("SELECT id_user, email, first_name FROM users WHERE email = :e LIMIT 1");
        $st->execute([':e' => $email]);
        $user = $st->fetch();

        $_SESSION['pw_msg'] = ['type' => 'info','text' => $generic];

        $token = bin2hex(random_bytes(16));
        $code  = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);
        $expires  = (new \DateTime('+20 minutes'))->format('Y-m-d H:i:s');

        $appUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
        $link   = $appUrl ? $appUrl . "/?page=password&token={$token}" : "/?page=password&token={$token}";

        if ($user) {
            $upd = $this->pdo->prepare(
                "UPDATE users
                 SET reset_token=:t, reset_code_hash=:c, reset_expires=:e
                 WHERE id_user=:id"
            );
            $upd->execute([':t' => $token, ':c' => $codeHash, ':e' => $expires, ':id' => $user['id_user']]);

            $tpl = new mailerView();
            $html = $tpl->show($code, $link);

            try {
                $this->mailer->send($user['email'], 'Votre code de réinitialisation', $html);
            } catch (\Throwable $e) {
                error_log('[Password] Mail send failed: ' . $e->getMessage());
            }
        }

        header('Location: ' . $link);
        return;
    }

    /**
     * Handles password reset after verification code validation.
     *
     * Validates the reset token, verification code, and new password.
     * Updates the password securely and clears all reset-related data.
     *
     * Expected POST parameters:
     * - token: 32-character hexadecimal reset token.
     * - code: 6-digit verification code.
     * - password: New password (minimum 8 characters).
     *
     * @return void
     */
    private function handleReset(): void
    {
        $token = $_POST['token'] ?? '';
        $code  = $_POST['code']  ?? '';
        $pass  = $_POST['password'] ?? '';

        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            $_SESSION['pw_msg'] = ['type' => 'error','text' => 'Lien/token invalide.'];
            header('Location: /?page=password');
            return;
        }
        if (strlen($pass) < 8) {
            $_SESSION['pw_msg'] = ['type' => 'error','text' => 'Mot de passe trop court (min 8).'];
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

        if (!$u || !$u['reset_expires'] || new \DateTime($u['reset_expires']) < new \DateTime()) {
            $_SESSION['pw_msg'] = ['type' => 'error','text' => 'Code expiré ou invalide.'];
            header('Location: /?page=password');
            return;
        }

        if (!password_verify($code, $u['reset_code_hash'])) {
            $_SESSION['pw_msg'] = ['type' => 'error','text' => 'Code incorrect.'];
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

        $_SESSION['pw_msg'] = ['type' => 'success','text' => 'Mot de passe mis à jour. Vous pouvez vous connecter.'];
        header('Location: /?page=login');
    }

    /**
     * Checks if a user is currently logged in.
     *
     * @return bool True if user is authenticated, false otherwise.
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}