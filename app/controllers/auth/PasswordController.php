<?php

namespace modules\controllers\auth;

use Database;
use DateTime;
use Mailer;
use modules\views\auth\MailerView;
use modules\views\auth\PasswordView;
use PDO;
use Throwable;

/**
 * Class PasswordController | Contrôleur de Mot de Passe
 *
 * Manages password reset process.
 * Gère le processus de réinitialisation de mot de passe.
 *
 * - Request reset code
 * - Verify code
 * - Reset password
 *
 * @package DashMed\Modules\Controllers\Auth
 * @author DashMed Team
 * @license Proprietary
 */
class PasswordController
{
    /** @var PDO Database connection | Connexion BDD */
    private PDO $pdo;

    /** @var Mailer Mailer service | Service d'envoi de mails */
    private \Mailer $mailer;

    /**
     * Constructor | Constructeur
     *
     * Initializes controller, DB connection, and mailer.
     * Initialise le contrôleur, la connexion à la base et le mailer.
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
     * Handles GET request: Display password reset page.
     * Gère la requête GET : Affiche la page de réinitialisation de mot de passe.
     *
     * @return void
     */
    public function get(): void
    {
        if ($this->isUserLoggedIn()) {
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
     * Handles POST request: Send code or reset password.
     * Gère la requête POST : Envoi du code ou réinitialisation.
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
            $_SESSION['pw_msg'] = ['type' => 'error', 'text' => 'Unknown action. | Action inconnue.'];
            header('Location: /?page=password');
        }
    }

    /**
     * Sends the reset code via email.
     * Gère l'envoi du code de réinitialisation par e-mail.
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
                    $this->mailer->send($userEmail, 'Votre code de réinitialisation', $html);
                }
            } catch (\Throwable $e) {
                error_log('[Password] Mail send failed: ' . $e->getMessage());
            }
        }

        header('Location: ' . $redirectLink);
        return;
    }

    /**
     * Resets the password using the code.
     * Gère la réinitialisation du mot de passe après saisie du code.
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
     * Vérifie si l'utilisateur est connecté.
     *
     * @return bool
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
