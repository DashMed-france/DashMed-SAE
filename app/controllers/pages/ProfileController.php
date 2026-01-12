<?php

namespace modules\controllers\pages;

use Database;
use modules\views\pages\ProfileView;
use PDO;
use Throwable;

/**
 * Class ProfileController | Contrôleur de Profil
 *
 * Manages user profile (view, update, delete).
 * Gère le profil utilisateur (affichage, modification, suppression).
 *
 * @package DashMed\Modules\Controllers\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class ProfileController
{
    /** @var PDO Database connection | Connexion BDD */
    private PDO $pdo;

    /** @var bool Test mode flag | Mode test */
    protected bool $testMode = false;

    /**
     * Sets test mode.
     * Active le mode test.
     *
     * @param bool $mode
     */
    public function setTestMode(bool $mode): void
    {
        $this->testMode = $mode;
    }

    /**
     * Constructor | Constructeur
     *
     * @param PDO|null $pdo Database connection (optional) | Connexion BDD (optionnel)
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getInstance();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Handles GET request: Display profile page.
     * Gère la requête GET : Affiche la page de profil.
     *
     * @return void
     */
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

        $view = new ProfileView();
        $view->show($user, $professions, $msg);
    }

    /**
     * Handles POST request: Update profile or delete account.
     * Gère la requête POST : Mise à jour du profil ou suppression du compte.
     *
     * @return void
     */
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
            $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Session expirée, réessayez.'];
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

        // Update Profile
        // Mise à jour du profil
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $profId = $_POST['id_profession'] ?? null; // <select name="id_profession">

        if ($first === '' || $last === '') {
            $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Le prénom et le nom sont obligatoires.'];
            if (!$this->testMode) {
                header('Location: /?page=profile');
                exit;
            }
            return;
        }

        // Validate profession ID
        // Valide l'ID de profession contre professions.id_profession
        $validId = null;
        if ($profId !== null && $profId !== '') {
            $st = $this->pdo->prepare("SELECT id_profession FROM professions WHERE id_profession = :id");
            $st->execute([':id' => $profId]);
            $validId = $st->fetchColumn() ?: null;
            if ($validId === null) {
                $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Spécialité invalide.'];
                if (!$this->testMode) {
                    header('Location: /?page=profile');
                    exit;
                }
                return;
            }
        }

        // Update DB: users.id_profession
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
            ':p' => $validId,            // null authorized if desired in DB | null autorisé si tu le souhaites côté BDD
            ':e' => $_SESSION['email']
        ]);

        $_SESSION['profile_msg'] = ['type' => 'success', 'text' => 'Profil mis à jour'];

        if (!$this->testMode) {
            header('Location: /?page=profile');
            exit;
        }
    }

    /**
     * Handles account deletion.
     * Gère la suppression du compte.
     *
     * @return void
     */
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
     * Retrieves user by email.
     * Récupère l'utilisateur par email.
     *
     * Aliases columns to match view expectations:
     * On ALIAS pour ne pas toucher la vue :
     *  - u.id_profession AS id_profession
     *  - p.label_profession AS profession_name
     *
     * @param string $email
     * @return array|null
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
     * Retrieves list of professions.
     * Liste des spécialités.
     *
     * Aliases for view compatibility ('id', 'name').
     * On ALIAS en 'id' / 'name' pour coller à la vue.
     *
     * @return array
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
