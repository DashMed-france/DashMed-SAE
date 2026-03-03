<?php

declare(strict_types=1);

namespace modules\controllers;

use modules\models\repositories\UserRepository;
use modules\models\repositories\PatientRepository;
use modules\models\repositories\RoomRepository;
use modules\views\admin\SysadminView;
use assets\includes\Database;
use PDO;

/**
 * Class AdminController
 *
 * System administrator dashboard controller.
 *
 * Replaces: SysadminController.
 *
 * @package DashMed\Modules\Controllers
 * @author DashMed Team
 * @license Proprietary
 */
class AdminController
{
    /** @var UserRepository User repository */
    private UserRepository $userRepo;

    /** @var PatientRepository Patient repository */
    private PatientRepository $patientRepo;

    /** @var RoomRepository Room repository */
    private RoomRepository $roomRepo;

    /** @var PDO Database connection */
    private PDO $pdo;

    /**
     * Constructor
     *
     * @param UserRepository|null $model Optional user repository injection
     * @param PatientRepository|null $patientModel Optional patient repository injection
     * @param RoomRepository|null $roomModel Optional room repository injection
     */
    public function __construct(
        ?UserRepository $model = null,
        ?PatientRepository $patientModel = null,
        ?RoomRepository $roomModel = null
    ) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->pdo = Database::getInstance();
        $this->userRepo = $model ?? new UserRepository($this->pdo);
        $this->patientRepo = $patientModel ?? new PatientRepository($this->pdo);
        $this->roomRepo = $roomModel ?? new RoomRepository($this->pdo);
    }

    /**
     * Admin panel entry point (GET & POST).
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
        $rooms = $this->roomRepo->getAvailableRooms();
        (new SysadminView())->show($specialties, $rooms);
    }

    /**
     * Processes admin panel form submission (user or patient creation).
     *
     * @return void
     */
    private function panelPost(): void
    {
        $sessionCsrf = isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf']) ? $_SESSION['_csrf'] : '';
        $postCsrf = isset($_POST['_csrf']) && is_string($_POST['_csrf']) ? $_POST['_csrf'] : '';
        if ($sessionCsrf !== '' && $postCsrf !== '' && !hash_equals($sessionCsrf, $postCsrf)) {
            $_SESSION['error'] = "Requête invalide. Veuillez réessayer.";
            header('Location: /?page=sysadmin');
            exit;
        }

        if (isset($_POST['room'])) {
            $this->processPatientCreation();
        } else {
            $this->processUserCreation();
        }
    }

    /**
     * Processes admin panel form submission for patient creation.
     * 
     * @return void
     */
    private function processPatientCreation(): void
    {
        $_SESSION['old_sysadmin'] = $_POST;

        $room = $_POST['room'] ?? '';
        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $birthDate = $_POST['birth_date'] ?? '';
        $admissionReason = trim($_POST['admission_reason'] ?? '');
        $height = trim($_POST['height'] ?? '');
        $weight = trim($_POST['weight'] ?? '');

        if ($room === '' || $lastName === '' || $firstName === '' || $email === '' || 
            $gender === '' || $birthDate === '' || $admissionReason === '' || $height === '' || $weight === '') {
            $_SESSION['error'] = "Tous les champs patient sont obligatoires.";
            header('Location: /?page=sysadmin');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email patient invalide.";
            header('Location: /?page=sysadmin');
            exit;
        }

        if (!is_numeric($height) || !is_numeric($weight)) {
            $_SESSION['error'] = "La taille et le poids doivent être des nombres valides.";
            header('Location: /?page=sysadmin');
            exit;
        }

        $genderValue = $gender === 'Homme' ? 'M' : ($gender === 'Femme' ? 'F' : '');
        if ($genderValue === '') {
            $_SESSION['error'] = "Genre invalide.";
            header('Location: /?page=sysadmin');
            exit;
        }

        try {
            $this->patientRepo->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'birth_date' => $birthDate,
                'weight' => (float) str_replace(',', '.', $weight),
                'height' => (float) str_replace(',', '.', $height),
                'gender' => $genderValue,
                'status' => 'En réanimation',
                'description' => $admissionReason,
                'room_id' => (int) $room
            ]);

            unset($_SESSION['old_sysadmin']);
            $_SESSION['success'] = "Patient créé avec succès dans la chambre {$room}.";

        } catch (Exception $e) {
            error_log('[AdminController] Patient creation SQL error: ' . $e->getMessage());
            $_SESSION['error'] = "Échec de la création du patient (email déjà utilisé ou chambre occupée ?).";
        }

        header('Location: /?page=sysadmin');
        exit;
    }

    /**
     * Processes admin panel form submission for user creation.
     *
     * @return void
     */
    private function processUserCreation(): void
    {
        $_SESSION['old_sysadmin'] = $_POST;

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
            $_SESSION['error'] = "Tous les champs compte sont obligatoires.";
            header('Location: /?page=sysadmin');
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            header('Location: /?page=sysadmin');
            exit;
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            header('Location: /?page=sysadmin');
            exit;
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            header('Location: /?page=sysadmin');
            exit;
        }

        if ($this->userRepo->getByEmail($email)) {
            $_SESSION['error'] = "Un compte existe déjà avec cet email.";
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
            
            unset($_SESSION['old_sysadmin']);
            $_SESSION['success'] = "Compte créé avec succès pour {$email}";
        } catch (\Throwable $e) {
            error_log('[AdminController] User creation SQL error: ' . $e->getMessage());
            $_SESSION['error'] = "Échec de la création du compte (email déjà utilisé ?).";
        }

        header('Location: /?page=sysadmin');
        exit;
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
     * Checks if user is admin.
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
