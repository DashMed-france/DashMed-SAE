<?php
namespace modules\controllers\pages;

use modules\views\pages\profileView;
use PDO;

require_once __DIR__ . '/../../../assets/includes/database.php';

/**
 * Contrôleur de gestion du profil utilisateur.
 */
class ProfileController
{
    /**
     * Instance PDO pour l'accès à la base de données.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Initialise le contrôleur et la connexion à la base de données.
     */
    public function __construct()
    {
        $this->pdo = \Database::getInstance();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }

    /**
     * Affiche la page de profil utilisateur.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=signup'); exit;
        }

        $user = $this->getUserByEmail($_SESSION['email']);
        $specialties = $this->getAllSpecialties();

        $msg = $_SESSION['profile_msg'] ?? null;
        unset($_SESSION['profile_msg']);

        $view = new profileView();
        $view->show($user, $specialties, $msg);
    }

    /**
     * Traite la soumission du formulaire de profil (mise à jour ou suppression).
     *
     * @return void
     */
    public function post(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=signup'); exit;
        }

        // CSRF pour toutes les actions POST de la page profil
        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_profile'] ?? '', $_POST['csrf'])) {
            $_SESSION['profile_msg'] = ['type'=>'error','text'=>'Session expirée, réessayez.'];
            header('Location: /?page=profile'); exit;
        }

        $action = $_POST['action'] ?? 'update';

        if ($action === 'delete_account') {
            $this->handleDeleteAccount();
            return; // handleDeleteAccount fait le redirect
        }

        // ----- Mise à jour du profil (action par défaut)
        $first  = trim($_POST['first_name'] ?? '');
        $last   = trim($_POST['last_name'] ?? '');
        $profId = $_POST['profession_id'] ?? null;

        if ($first === '' || $last === '') {
            $_SESSION['profile_msg'] = ['type'=>'error','text'=>'Le prénom et le nom sont obligatoires.'];
            header('Location: /?page=profile'); exit;
        }

        $validId = null;
        if ($profId !== null && $profId !== '') {
            $st = $this->pdo->prepare("SELECT id FROM medical_specialties WHERE id = :id");
            $st->execute([':id' => $profId]);
            $validId = $st->fetchColumn() ?: null;
            if ($validId === null) {
                $_SESSION['profile_msg'] = ['type'=>'error','text'=>'Spécialité invalide.'];
                header('Location: /?page=profile'); exit;
            }
        }

        $upd = $this->pdo->prepare("
            UPDATE users
               SET first_name = :f, last_name = :l, profession_id = :p
             WHERE email = :e
        ");
        $upd->execute([
            ':f' => $first,
            ':l' => $last,
            ':p' => $validId,
            ':e' => $_SESSION['email']
        ]);

        $_SESSION['profile_msg'] = ['type'=>'success','text'=>'Profil mis à jour ✅'];
        header('Location: /?page=profile'); exit;
    }

    /**
     * Gère la suppression du compte utilisateur.
     *
     * @return void
     */
    private function handleDeleteAccount(): void
    {
        $email = $_SESSION['email'] ?? null;
        if (!$email) {
            header('Location: /?page=signup'); exit;
        }

        try {
            $this->pdo->beginTransaction();

            // TODO: si tu as d'autres tables liées (dossiers, logs, etc.),
            // supprime-les ici ou assure-toi que tes FK aient ON DELETE CASCADE.
            $del = $this->pdo->prepare("DELETE FROM users WHERE email = :e");
            $del->execute([':e' => $email]);

            $this->pdo->commit();

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('[Profile] Delete account failed: '.$e->getMessage());
            $_SESSION['profile_msg'] = [
                'type' => 'error',
                'text' => "Impossible de supprimer le compte (contraintes en base ?)."
            ];
            header('Location: /?page=profile'); exit;
        }

        // Déconnexion propre
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();

        // Redirection après suppression
        header('Location: /?page=signup'); // ou '/homepage'
        exit;
    }

    /**
     * Récupère les informations de l'utilisateur par email.
     *
     * @param string $email
     * @return array|null
     */
    private function getUserByEmail(string $email): ?array
    {
        $sql = "SELECT u.first_name, u.last_name, u.email, u.profession_id,
                       ms.name AS profession_name
                  FROM users u
             LEFT JOIN medical_specialties ms ON ms.id = u.profession_id
                 WHERE u.email = :e";
        $st = $this->pdo->prepare($sql);
        $st->execute([':e' => $email]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Récupère la liste de toutes les spécialités médicales.
     *
     * @return array
     */
    private function getAllSpecialties(): array
    {
        $st = $this->pdo->query("SELECT id, name FROM medical_specialties ORDER BY name");
        return $st->fetchAll(PDO::FETCH_ASSOC);
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
