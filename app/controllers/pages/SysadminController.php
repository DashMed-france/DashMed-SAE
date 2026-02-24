<?php

namespace modules\controllers\pages;

use modules\models\UserModel;
use modules\views\pages\SysadminView;
use assets\includes\Database;
use PDO;

/**
 * Class SysadminController | Contrôleur Admin Système
 *
 * System Administrator Dashboard Controller.
 * Contrôleur du tableau de bord administrateur.
 *
 * @package DashMed\Modules\Controllers\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class SysadminController
{
    /**
     * Business logic / model for login and registration.
     * Logique métier / modèle pour les opérations de connexion et d’inscription.
     *
     * @var UserModel
     */
    private UserModel $model;

    /**
     * PDO Instance for database access.
     * Instance PDO pour l'accès à la base de données.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Controller Constructor.
     * Constructeur du contrôleur.
     *
     * Starts session if needed, retrieves shared PDO instance via Database helper,
     * and instantiates model.
     * Démarre la session si nécessaire, récupère une instance partagée de PDO via
     * l’aide de base de données (Database helper) et instancie le modèle de connexion.
     *
     * @param UserModel|null $model
     */
    public function __construct(?UserModel $model = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->pdo = Database::getInstance();

        if ($model) {
            $this->model = $model;
        } else {
            $this->model = new UserModel($this->pdo);
        }
    }

    /**
     * Handles GET request: Display sysadmin dashboard.
     * Affiche la vue du tableau de bord administrateur si l'utilisateur est connecté.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn() || !$this->isAdmin()) {
            $this->redirect('/?page=login');
            $this->terminate();
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        $specialties = $this->getAllSpecialties();
        $users = $this->model->getAllUsersWithProfession();
        (new SysadminView())->show($specialties, $users);
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
     * Handles HTTP POST requests.
     * Gestionnaire des requêtes HTTP POST.
     *
     * Validates form fields (name, email, password, confirm), enforces minimum security,
     * checks email uniqueness and delegates account creation to model.
     * Valide les champs du formulaire soumis (nom, e-mail, mot de passe et confirmation),
     * applique une politique de sécurité minimale sur le mot de passe, vérifie l’unicité
     * de l’adresse e-mail et délègue la création du compte au modèle.
     *
     * Uses redirects and session flash data for results.
     * Utilise des redirections basées sur les en-têtes HTTP et des données de session
     * temporaires (flash) pour communiquer les résultats de la validation.
     *
     * @return void
     */
    public function post(): void
    {
        error_log('[SysadminController] POST /sysadmin hit');

        $sessionCsrf = isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf']) ? $_SESSION['_csrf'] : '';
        $postCsrf = isset($_POST['_csrf']) && is_string($_POST['_csrf']) ? $_POST['_csrf'] : '';
        if ($sessionCsrf !== '' && $postCsrf !== '' && !hash_equals($sessionCsrf, $postCsrf)) {
            $_SESSION['error'] = "Invalid request. Try again. | Requête invalide. Réessaye.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        // --- Handle delete user action ---
        $action = $_POST['action'] ?? '';
        if ($action === 'delete_user') {
            $deleteId = isset($_POST['delete_user_id']) ? (int) $_POST['delete_user_id'] : 0;
            if ($deleteId <= 0) {
                $_SESSION['error'] = "ID utilisateur invalide. | Invalid user ID.";
                $this->redirect('/?page=sysadmin');
                $this->terminate();
            }

            // Prevent self-deletion
            $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
            if ($deleteId === $currentUserId) {
                $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte. | You cannot delete your own account.";
                $this->redirect('/?page=sysadmin');
                $this->terminate();
            }

            // Prevent deleting another admin
            $targetUser = $this->model->getById($deleteId);
            if ($targetUser !== null && (int) ($targetUser['admin_status'] ?? 0) === 1) {
                $_SESSION['error'] = "Impossible de supprimer un compte administrateur. | Cannot delete an administrator account.";
                $this->redirect('/?page=sysadmin');
                $this->terminate();
            }

            try {
                $deleted = $this->model->deleteById($deleteId);
                if ($deleted) {
                    $_SESSION['success'] = "Compte supprimé avec succès. | Account deleted successfully.";
                } else {
                    $_SESSION['error'] = "Compte introuvable. | Account not found.";
                }
            } catch (\Throwable $e) {
                error_log('[SysadminController] Delete error: ' . $e->getMessage());
                $_SESSION['error'] = "Erreur lors de la suppression. | Deletion failed.";
            }

            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        // --- Handle edit user action ---
        if ($action === 'edit_user') {
            $editId = isset($_POST['edit_user_id']) ? (int) $_POST['edit_user_id'] : 0;
            if ($editId <= 0) {
                $_SESSION['error'] = "ID utilisateur invalide. | Invalid user ID.";
                $this->redirect('/?page=sysadmin');
                $this->terminate();
            }

            $editLast = trim((string) ($_POST['edit_last_name'] ?? ''));
            $editFirst = trim((string) ($_POST['edit_first_name'] ?? ''));
            $editEmail = trim((string) ($_POST['edit_email'] ?? ''));
            $editProfId = $_POST['edit_profession_id'] ?? null;
            $editAdmin = isset($_POST['edit_admin_status']) ? (int) $_POST['edit_admin_status'] : 0;
            $editPassword = (string) ($_POST['edit_password'] ?? '');

            if ($editLast === '' || $editFirst === '' || $editEmail === '') {
                $_SESSION['error'] = "Nom, prénom et email sont requis. | Last name, first name and email are required.";
                $this->redirect('/?page=sysadmin');
                $this->terminate();
            }

            if (!filter_var($editEmail, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Email invalide. | Invalid email.";
                $this->redirect('/?page=sysadmin');
                $this->terminate();
            }

            // Check email uniqueness (exclude current user)
            $existingUser = $this->model->getByEmail($editEmail);
            if ($existingUser !== null && (int) $existingUser['id_user'] !== $editId) {
                $_SESSION['error'] = "Cet email est déjà utilisé par un autre compte. | This email is already used by another account.";
                $this->redirect('/?page=sysadmin');
                $this->terminate();
            }

            $updateData = [
                'first_name' => $editFirst,
                'last_name' => $editLast,
                'email' => $editEmail,
                'admin_status' => $editAdmin,
                'id_profession' => $editProfId !== '' ? $editProfId : null,
            ];

            if ($editPassword !== '') {
                if (strlen($editPassword) < 8) {
                    $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères. | Password must be at least 8 characters.";
                    $this->redirect('/?page=sysadmin');
                    $this->terminate();
                }
                $updateData['password'] = $editPassword;
            }

            try {
                $this->model->updateById($editId, $updateData);
                $_SESSION['success'] = "Profil mis à jour avec succès. | Profile updated successfully.";
            } catch (\Throwable $e) {
                error_log('[SysadminController] Update error: ' . $e->getMessage());
                $_SESSION['error'] = "Erreur lors de la mise à jour. | Update failed.";
            }

            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        // --- Handle create user action ---
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
            $_SESSION['error'] = "All fields required. | Tous les champs sont requis.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email. | Email invalide.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Passwords do not match. | Les mots de passe ne correspondent pas.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Password must be at least 8 chars. | " .
                "Le mot de passe doit contenir au moins 8 caractères.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        if ($this->model->getByEmail($email)) {
            $_SESSION['error'] = "Account already exists with this email. | Un compte existe déjà avec cet email.";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        try {
            $userId = $this->model->create([
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'password' => $pass,
                'id_profession' => $profId,
                'admin_status' => $admin,
            ]);
        } catch (\Throwable $e) {
            error_log('[SysadminController] SQL error: ' . $e->getMessage());
            $_SESSION['error'] = "Account creation failed (email used?). | " .
                "Impossible de créer le compte (email déjà utilisé ?)";
            $this->redirect('/?page=sysadmin');
            $this->terminate();
        }

        $_SESSION['success'] = "Account created successfully for {$email} | Compte créé avec succès pour {$email}";
        $this->redirect('/?page=sysadmin');
        $this->terminate();
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
