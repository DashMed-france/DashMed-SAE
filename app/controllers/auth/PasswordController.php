<?php
namespace modules\controllers\auth;

use modules\views\auth\mailerView;
use modules\views\auth\passwordView;
use PDO;

require_once __DIR__ . '/../../../assets/includes/database.php';
require_once __DIR__ . '/../../../assets/includes/Mailer.php';

/**
 * Contrôleur de gestion de la réinitialisation de mot de passe.
 */
class PasswordController
{
    /**
     * Instance PDO pour l'accès à la base de données.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Instance du service d'envoi de mails.
     *
     * @var \Mailer
     */
    private \Mailer $mailer;

    /**
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
     * Affiche la page de réinitialisation de mot de passe.
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
     * Traite les requêtes POST pour l'envoi du code ou la réinitialisation.
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
     * Gère l'envoi du code de réinitialisation par e-mail.
     *
     * @return void
     */
    private function handleSendCode(): void
    {
        $email = trim($_POST['email'] ?? '');
        $generic = "Si un compte correspond, un code de réinitialisation a été envoyé.";

        if ($email === '') {
            $_SESSION['pw_msg'] = ['type'=>'error','text'=>'Email requis.'];
            header('Location: /?page=password');
            return;
        }

        $st = $this->pdo->prepare("SELECT id_user, email, first_name FROM users WHERE email = :e LIMIT 1");
        $st->execute([':e' => $email]);
        $user = $st->fetch();

        $_SESSION['pw_msg'] = ['type'=>'info','text'=>$generic];

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
            $this->mailer->send($user['email'], 'Votre code de réinitialisation', $html);
        }

        try {
            $this->mailer->send($user['email'], 'Votre code de réinitialisation', $html);
        } catch (\Throwable $e) {
            error_log('[Password] Mail send failed: ' . $e->getMessage());
        }

        header('Location: ' . $link);
    }

    /**
     * Gère la réinitialisation du mot de passe après saisie du code.
     *
     * @return void
     */
    private function handleReset(): void
    {
        $token = $_POST['token'] ?? '';
        $code  = $_POST['code']  ?? '';
        $pass  = $_POST['password'] ?? '';

        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            $_SESSION['pw_msg'] = ['type'=>'error','text'=>'Lien/token invalide.'];
            header('Location: /?page=password');
            return;
        }
        if (strlen($pass) < 8) {
            $_SESSION['pw_msg'] = ['type'=>'error','text'=>'Mot de passe trop court (min 8).'];
            header('Location: /?page=password&token='.$token);
            return;
        }

        $st = $this->pdo->prepare(
            "SELECT id_user, reset_code_hash, reset_expires
             FROM users
             WHERE reset_token=:t LIMIT 1"
        );
        $st->execute([':t'=>$token]);
        $u = $st->fetch();

        if (!$u || !$u['reset_expires'] || new \DateTime($u['reset_expires']) < new \DateTime()) {
            $_SESSION['pw_msg'] = ['type'=>'error','text'=>'Code expiré ou invalide.'];
            header('Location: /?page=password');
            return;
        }

        if (!password_verify($code, $u['reset_code_hash'])) {
            $_SESSION['pw_msg'] = ['type'=>'error','text'=>'Code incorrect.'];
            header('Location: /?page=password&token='.$token);
            return;
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $this->pdo->beginTransaction();
        $upd = $this->pdo->prepare(
            "UPDATE users
             SET password=:p, reset_token=NULL, reset_code_hash=NULL, reset_expires=NULL
             WHERE id_user=:id"
        );
        $upd->execute([':p'=>$hash, ':id'=>$u['id_user']]);
        $this->pdo->commit();

        $_SESSION['pw_msg'] = ['type'=>'success','text'=>'Mot de passe mis à jour. Vous pouvez vous connecter.'];
        header('Location: /?page=login');
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
}
