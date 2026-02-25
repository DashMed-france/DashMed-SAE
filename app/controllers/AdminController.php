<?php

declare(strict_types=1);

namespace modules\controllers;

use modules\models\repositories\UserRepository;
use modules\views\admin\SysadminView;
use assets\includes\Database;
use PDO;

/**
 * Class AdminController | Contrôleur Admin Système
 *
 * System Administrator Dashboard Controller.
 * Contrôleur du tableau de bord administrateur.
 *
 * Replaces: SysadminController.
 * Remplace : SysadminController.
 *
 * @package DashMed\Modules\Controllers
 * @author DashMed Team
 * @license Proprietary
 */
class AdminController
{
    /** @var UserRepository User repository | Repository utilisateur */
    private UserRepository $userRepo;

    /** @var PDO Database connection | Connexion BDD */
    private PDO $pdo;

    /**
     * Constructor | Constructeur
     *
     * @param UserRepository|null $model Optional repository injection
     */
    public function __construct(?UserRepository $model = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->pdo = Database::getInstance();
        $this->userRepo = $model ?? new UserRepository($this->pdo);
    }

    /**
     * Admin panel entry point (GET & POST).
     * Point d'entrée du panneau admin (GET & POST).
     *
     * @return void
     */
    public function panel(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->panelPost();
        } else {
            $this->panelGet();
        }
    }

    /**
     * Displays the admin panel.
     * Affiche le panneau administrateur.
     *
     * @return void
     */
    private function panelGet(): void
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            $this->redirect('/?page=login');
            $this->terminate();
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        $specialties = $this->getAllSpecialties();
        $users = $this->userRepo->getAllUsersWithProfession();
        (new SysadminView())->show($specialties, $users);
    }

    /**
     * Handles POST requests: dispatches to create, edit, or delete.
     * Gestionnaire POST : dispatche vers création, édition ou suppression.
     *
     * @return void
     */
    private function panelPost(): void
    {
        $sessionCsrf = isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf']) ? $_SESSION['_csrf'] : '';
        $postCsrf = isset($_POST['_csrf']) && is_string($_POST['_csrf']) ? $_POST['_csrf'] : '';
        if ($sessionCsrf !== '' && $postCsrf !== '' && !hash_equals($sessionCsrf, $postCsrf)) {
            $_SESSION['error'] = "Requête invalide. Veuillez réessayer.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'delete_user') {
            $this->handleDelete();
            return;
        }

        if ($action === 'edit_user') {
            $this->handleEdit();
            return;
        }

        $this->handleCreate();
    }

    /**
     * Handles user creation.
     * Gère la création d'un utilisateur.
     *
     * @return void
     */
    private function handleCreate(): void
    {
        $rawLast = $_POST['last_name'] ?? '';
        $last = trim(is_string($rawLast) ? $rawLast : '');
        $rawFirst = $_POST['first_name'] ?? '';
        $first = trim(is_string($rawFirst) ? $rawFirst : '');
        $rawEmail = $_POST['email'] ?? '';
        $email = trim(is_string($rawEmail) ? $rawEmail : '');
        $pass = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $pass2 = isset($_POST['password_confirm']) && is_string($_POST['password_confirm'])
            ? $_POST['password_confirm']
            : '';
        $profId = $_POST['profession_id'] ?? null;
        $rawAdmin = $_POST['admin_status'] ?? 0;
        $admin = is_numeric($rawAdmin) ? (int) $rawAdmin : 0;

        if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
            $_SESSION['error'] = "Tous les champs sont obligatoires.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        if ($this->userRepo->getByEmail($email)) {
            $_SESSION['error'] = "Un compte existe déjà avec cet email.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        try {
            $this->userRepo->create([
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'password' => $pass,
                'id_profession' => $profId,
                'admin_status' => $admin,
            ]);
        } catch (\Throwable $e) {
            error_log('[AdminController] SQL error: ' . $e->getMessage());
            $_SESSION['error'] = "Échec de la création du compte (email déjà utilisé ?).";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $_SESSION['success'] = "Compte créé avec succès pour {$email}";
        $this->redirect('/?page=sysadmin');
        $this->terminate();
    }

    /**
     * Handles user deletion.
     * Gère la suppression d'un utilisateur.
     *
     * @return void
     */
    private function handleDelete(): void
    {
        $deleteId = isset($_POST['delete_user_id']) ? (int) $_POST['delete_user_id'] : 0;
        if ($deleteId <= 0) {
            $_SESSION['error'] = "ID utilisateur invalide.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        if ($deleteId === $currentUserId) {
            $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $targetUser = $this->userRepo->getById($deleteId);
        if ($targetUser === null) {
            $_SESSION['error'] = "Compte introuvable.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        if ($targetUser->isAdmin()) {
            $_SESSION['error'] = "Impossible de supprimer un compte administrateur.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        try {
            $deleted = $this->userRepo->deleteById($deleteId);
            if ($deleted) {
                $_SESSION['success'] = "Compte supprimé avec succès.";
            } else {
                $_SESSION['error'] = "Compte introuvable.";
            }
        } catch (\Throwable $e) {
            error_log('[AdminController] Delete error: ' . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de la suppression.";
        }

        $this->redirect('/?page=sysadmin');
        $this->terminate();
    }

    /**
     * Handles user editing.
     * Gère la modification d'un utilisateur.
     *
     * @return void
     */
    private function handleEdit(): void
    {
        $editId = isset($_POST['edit_user_id']) ? (int) $_POST['edit_user_id'] : 0;
        if ($editId <= 0) {
            $_SESSION['error'] = "ID utilisateur invalide.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $editLast = trim((string) ($_POST['edit_last_name'] ?? ''));
        $editFirst = trim((string) ($_POST['edit_first_name'] ?? ''));
        $editEmail = trim((string) ($_POST['edit_email'] ?? ''));
        $editProfId = $_POST['edit_profession_id'] ?? null;
        $editAdmin = isset($_POST['edit_admin_status']) ? (int) $_POST['edit_admin_status'] : 0;

        if ($editLast === '' || $editFirst === '' || $editEmail === '') {
            $_SESSION['error'] = "Nom, prénom et email sont requis.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        if (!filter_var($editEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $existingUser = $this->userRepo->getByEmail($editEmail);
        if ($existingUser !== null && $existingUser->getId() !== $editId) {
            $_SESSION['error'] = "Cet email est déjà utilisé par un autre compte.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $targetUser = $this->userRepo->getById($editId);
        if (!$targetUser) {
            $_SESSION['error'] = "Utilisateur introuvable.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $targetIsAdmin = $targetUser->isAdmin();
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);

        $updateData = [
            'first_name' => $editFirst,
            'last_name' => $editLast,
            'email' => $editEmail,
            'admin_status' => $editAdmin,
            'id_profession' => $editProfId !== '' ? $editProfId : null,
        ];

        if ($targetIsAdmin) {
            if ($editAdmin === 0) {
                if ($editId === $currentUserId) {
                    $_SESSION['error'] = "Vous ne pouvez pas retirer vos propres droits administrateur.";
                } else {
                    $_SESSION['error'] = "Impossible de retirer les droits d'un autre administrateur.";
                }
                $this->redirect('/?page=sysadmin');
                $this->terminate();
            }
            $updateData['admin_status'] = 1;
        } else {
            $updateData['admin_status'] = $editAdmin;
        }

        try {
            $this->userRepo->updateById($editId, $updateData);
            $_SESSION['success'] = "Profil mis à jour avec succès.";
        } catch (\Throwable $e) {
            error_log('[AdminController] Update error: ' . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de la mise à jour.";
        }

        $this->redirect('/?page=sysadmin');
        $this->terminate();
    }

    /**
     * Checks if user is logged in.
     * Vérifie si l'utilisateur est connecté.
     *
     * @return bool
     */
    private function isLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Checks if user is admin.
     * Vérifie si l'utilisateur est un administrateur.
     *
     * @return bool
     */
    private function isAdmin(): bool
    {
        $rawAdminStatus = $_SESSION['admin_status'] ?? 0;
        return is_numeric($rawAdminStatus) && (int) $rawAdminStatus === 1;
    }

    /**
     * Redirects to location.
     * Redirige vers une destination.
     *
     * @param string $location
     * @return void
     */
    protected function redirect(string $location): void
    {
        header('Location: ' . $location);
    }

    /**
     * Terminates execution.
     * Termine l'exécution.
     *
     * @return void
     */
    protected function terminate(): void
    {
        exit;
    }

    /**
     * Retrieves all medical specialties.
     * Récupère la liste de toutes les spécialités médicales.
     *
     * @return array<int, array{id_profession: int, label_profession: string}>
     */
    private function getAllSpecialties(): array
    {
        $st = $this->pdo->query("SELECT id_profession, label_profession FROM professions ORDER BY label_profession");
        if ($st === false) {
            return [];
        }
        /** @var array<int, array{id_profession: int, label_profession: string}> */
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
