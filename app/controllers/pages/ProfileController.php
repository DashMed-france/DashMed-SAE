<?php

namespace modules\controllers\pages;

use Database;
use modules\views\pages\profileView;
use PDO;
use Throwable;

class ProfileController
{
    private PDO $pdo;
    protected bool $testMode = false;

    public function setTestMode(bool $mode): void
    {
        $this->testMode = $mode;
    }

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getInstance();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=signup');
            exit;
        }

        $user = $this->getUserByEmail($_SESSION['email']);
        $professions = $this->getAllProfessions();

        $msg = $_SESSION['profile_msg'] ?? null;
        unset($_SESSION['profile_msg']);

        $view = new profileView();
        $view->show($user, $professions, $msg);
    }

    public function post(): void
    {
        if (!$this->isUserLoggedIn()) {
            if (!$this->testMode) {
                header('Location: /?page=signup');
                exit;
            }
            return;
        }

        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_profile'] ?? '', $_POST['csrf'])) {
            $_SESSION['profile_msg'] = ['type' => 'error','text' => 'Session expirée, réessayez.'];
            if (!$this->testMode) {
                header('Location: /?page=profile');
                exit;
            }
            return;
        }

        $action = $_POST['action'] ?? 'update';

        if ($action === 'delete_account') {
            $this->handleDeleteAccount();
            return;
        }

        // ----- Mise à jour du profil
        $first  = trim($_POST['first_name'] ?? '');
        $last   = trim($_POST['last_name'] ?? '');
        $profId = $_POST['id_profession'] ?? null; // <select name="id_profession">

        if ($first === '' || $last === '') {
            $_SESSION['profile_msg'] = ['type' => 'error','text' => 'Le prénom et le nom sont obligatoires.'];
            if (!$this->testMode) {
                header('Location: /?page=profile');
                exit;
            }
            return;
        }

        // Valide l'ID de profession contre professions.id_profession
        $validId = null;
        if ($profId !== null && $profId !== '') {
            $st = $this->pdo->prepare("SELECT id_profession FROM professions WHERE id_profession = :id");
            $st->execute([':id' => $profId]);
            $validId = $st->fetchColumn() ?: null;
            if ($validId === null) {
                $_SESSION['profile_msg'] = ['type' => 'error','text' => 'Spécialité invalide.'];
                if (!$this->testMode) {
                    header('Location: /?page=profile');
                    exit;
                }
                return;
            }
        }

        // Met à jour la bonne colonne en BDD : users.id_profession
        $upd = $this->pdo->prepare("
            UPDATE users
               SET first_name = :f,
                   last_name = :l,
                   id_profession = :p
             WHERE email = :e
        ");
        $upd->execute([
            ':f' => $first,
            ':l' => $last,
            ':p' => $validId,            // null autorisé si tu le souhaites côté BDD, sinon rends NOT NULL
            ':e' => $_SESSION['email']
        ]);

        $_SESSION['profile_msg'] = ['type' => 'success','text' => 'Profil mis à jour'];

        if (!$this->testMode) {
            header('Location: /?page=profile');
            exit;
        }
    }

    private function handleDeleteAccount(): void
    {
        $email = $_SESSION['email'] ?? null;
        if (!$email) {
            if (!$this->testMode) {
                header('Location: /?page=signup');
                exit;
            }
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $del = $this->pdo->prepare("DELETE FROM users WHERE email = :e");
            $del->execute([':e' => $email]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('[Profile] Delete account failed: ' . $e->getMessage());
            $_SESSION['profile_msg'] = [
                'type' => 'error',
                'text' => "Impossible de supprimer le compte (contraintes en base ?)."
            ];
            if (!$this->testMode) {
                header('Location: /?page=profile');
                exit;
            }
            return;
        }

        if (!$this->testMode) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();

            header('Location: /?page=signup');
            exit;
        }
    }

    /**
     * Récupère l'utilisateur par email.
     * On ALIAS pour ne pas toucher la vue :
     *  - u.id_profession AS id_profession
     *  - p.label_profession AS profession_name
     */
    private function getUserByEmail(string $email): ?array
    {
        $sql = "SELECT
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.id_profession AS id_profession,
                    p.label_profession AS profession_name
                FROM users u
                LEFT JOIN professions p
                       ON p.id_profession = u.id_profession
                WHERE u.email = :e";
        $st = $this->pdo->prepare($sql);
        $st->execute([':e' => $email]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
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

    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
