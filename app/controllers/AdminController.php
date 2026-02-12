<?php

declare(strict_types=1);

namespace modules\controllers;

use modules\models\repositories\UserRepository;
use modules\views\admin\SysadminView;
use assets\includes\Database;
use PDO;

/**
 * Class AdminController | Contrôleur Administrateur
 *
 * System administrator dashboard controller.
 * Contrôleur du tableau de bord administrateur.
 *
 * Replaces: SysadminController.
 *
 * @package DashMed\Modules\Controllers
 * @author DashMed Team
 * @license Proprietary
 */
class AdminController
{
    /** @var UserRepository User repository | Repository Utilisateur */
    private UserRepository $userRepo;

    /** @var PDO Database connection | Connexion BDD */
    private PDO $pdo;

    /**
     * Constructor | Constructeur
     *
     * @param UserRepository|null $model Optional repository injection | Injection optionnelle
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
     * Affiche le panneau d'administration.
     *
     * @return void
     */
    private function panelGet(): void
    {
        if (!$this->isLoggedIn() || !$this->isAdmin()) {
            header('Location: /?page=login');
            exit;
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        $specialties = $this->getAllSpecialties();
        (new SysadminView())->show($specialties);
    }

    /**
     * Processes admin panel form submission (user creation).
     * Traite la soumission du formulaire admin (création utilisateur).
     *
     * @return void
     */
    private function panelPost(): void
    {
        $sessionCsrf = isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf']) ? $_SESSION['_csrf'] : '';
        $postCsrf = isset($_POST['_csrf']) && is_string($_POST['_csrf']) ? $_POST['_csrf'] : '';
        if ($sessionCsrf !== '' && $postCsrf !== '' && !hash_equals($sessionCsrf, $postCsrf)) {
            $_SESSION['error'] = "Invalid request. Try again. | Requête invalide. Réessaye.";
            header('Location: /?page=sysadmin');
            exit;
        }

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
        $profId = $_POST['id_profession'] ?? null;
        $rawAdmin = $_POST['admin_status'] ?? 0;
        $admin = is_numeric($rawAdmin) ? (int) $rawAdmin : 0;

        if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
            $_SESSION['error'] = "All fields required. | Tous les champs sont requis.";
            header('Location: /?page=sysadmin');
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email. | Email invalide.";
            header('Location: /?page=sysadmin');
            exit;
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Passwords do not match. | Les mots de passe ne correspondent pas.";
            header('Location: /?page=sysadmin');
            exit;
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Password must be at least 8 chars. | " .
                "Le mot de passe doit contenir au moins 8 caractères.";
            header('Location: /?page=sysadmin');
            exit;
        }

        if ($this->userRepo->getByEmail($email)) {
            $_SESSION['error'] = "Account already exists with this email. | Un compte existe déjà avec cet email.";
            header('Location: /?page=sysadmin');
            exit;
        }

        try {
            $this->userRepo->create([
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'password' => $pass,
                'profession' => $profId,
                'admin_status' => $admin,
            ]);
        } catch (\Throwable $e) {
            error_log('[AdminController] SQL error: ' . $e->getMessage());
            $_SESSION['error'] = "Account creation failed (email used?). | " .
                "Impossible de créer le compte (email déjà utilisé ?)";
            header('Location: /?page=sysadmin');
            exit;
        }

        $_SESSION['success'] = "Account created successfully for {$email} | Compte créé avec succès pour {$email}";
        header('Location: /?page=sysadmin');
        exit;
    }

    // ──────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────

    /**
     * Checks if user is logged in.
     * Vérifie la connexion.
     *
     * @return bool
     */
    private function isLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Checks if user is admin.
     * Vérifie le statut admin.
     *
     * @return bool
     */
    private function isAdmin(): bool
    {
        $rawAdminStatus = $_SESSION['admin_status'] ?? 0;
        return is_numeric($rawAdminStatus) && (int) $rawAdminStatus === 1;
    }

    /**
     * Retrieves all medical specialties.
     * Récupère toutes les spécialités médicales.
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
