<?php

namespace modules\controllers\pages;

require_once __DIR__ . '/../../views/pages/medicalprocedureView.php';
require_once __DIR__ . '/../../models/ConsultationModel.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../../assets/includes/database.php';

use modules\views\pages\medicalprocedureView;
use modules\models\ConsultationModel;
use modules\services\PatientContextService;
use modules\models\PatientModel;
use modules\models\UserModel;

/**
 * Controller for managing the patient's medical procedures page.
 *
 * This controller handles the display and management of patient consultations,
 * including creation, updates, and deletions with appropriate access control.
 */
class MedicalProcedureController
{
    /**
     * PDO database connection instance.
     *
     * @var PDO
     */
    private \PDO $pdo;

    /**
     * Model for managing consultations.
     *
     * @var ConsultationModel
     */
    private \modules\models\ConsultationModel $consultationModel;

    /**
     * Service for managing patient context (selected patient).
     *
     * @var PatientContextService
     */
    private \modules\services\PatientContextService $contextService;

    /**
     * Model for managing patient data.
     *
     * @var PatientModel
     */
    private \modules\models\PatientModel $patientModel;

    /**
     * Model for managing user data.
     *
     * @var UserModel
     */
    private \modules\models\UserModel $userModel;

    /**
     * Constructor for MedicalProcedureController.
     *
     * Initializes all required models and services for medical procedure
     * management functionality.
     *
     * @param PDO|null $pdo Optional PDO instance for dependency injection
     */
    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getInstance();
        $this->consultationModel = new \modules\models\ConsultationModel($this->pdo);
        $this->patientModel = new \modules\models\PatientModel($this->pdo);
        $this->userModel = new \modules\models\UserModel($this->pdo);
        $this->contextService = new \modules\services\PatientContextService($this->patientModel);
    }

    /**
     * Handles POST requests (consultation addition, update, deletion).
     *
     * Processes the POST request and then redirects to the GET method
     * to display the updated consultation list.
     *
     * @return void
     */
    public function post(): void
    {
        $this->handlePostRequest();
        $this->get();
    }

    /**
     * Processes data submitted via POST form.
     *
     * Handles different actions based on the 'action' POST parameter:
     * - add_consultation: Creates a new consultation
     * - update_consultation: Updates an existing consultation
     * - delete_consultation: Deletes a consultation
     *
     * Enforces access control based on user admin status and consultation ownership.
     *
     * @return void
     */
    private function handlePostRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if (!$this->isUserLoggedIn()) {
                header('Location: /?page=login');
                exit;
            }

            $this->contextService->handleRequest();
            $patientId = $this->contextService->getCurrentPatientId();
            $currentUserId = $_SESSION['user_id'] ?? null;

            if (!$patientId || !$currentUserId) {
                return;
            }

            $isAdmin = $this->isAdminUser((int) $currentUserId);

            $action = $_POST['action'];

            if ($action === 'add_consultation') {
                $this->handleAddConsultation($patientId, (int) $currentUserId, $isAdmin);
            } elseif ($action === 'update_consultation') {
                $this->handleUpdateConsultation($patientId, (int) $currentUserId, $isAdmin);
            } elseif ($action === 'delete_consultation') {
                $this->handleDeleteConsultation($patientId);
            }
        }
    }

    /**
     * Handles the addition of a new consultation.
     *
     * If the user is an admin, they can specify the doctor ID. Otherwise,
     * the current user is automatically set as the doctor. Creates the consultation
     * and redirects to the medical procedure page on success.
     *
     * @param int $patientId The ID of the patient for the consultation
     * @param int $currentUserId The ID of the current logged-in user
     * @param bool $isAdmin Whether the current user has admin privileges
     * @return void
     */
    private function handleAddConsultation(int $patientId, int $currentUserId, bool $isAdmin): void
    {
        $doctorId = ($isAdmin && isset($_POST['doctor_id']) && $_POST['doctor_id'])
            ? (int) $_POST['doctor_id']
            : $currentUserId;

        $title = trim($_POST['consultation_title'] ?? '');
        $date = $_POST['consultation_date'] ?? '';
        $time = $_POST['consultation_time'] ?? '';
        $type = $_POST['consultation_type'] ?? 'Autre';
        $note = trim($_POST['consultation_note'] ?? '');

        if ($title && $date && $time) {
            $fullDate = $date . ' ' . $time . ':00';

            $success = $this->consultationModel->createConsultation(
                $patientId,
                $doctorId,
                $fullDate,
                $type,
                $note,
                $title
            );

            if ($success) {
                header("Location: ?page=medicalprocedure&id_patient=" . $patientId);
                exit;
            }
        }
    }

    /**
     * Handles the update of an existing consultation.
     *
     * Verifies access rights: only the consultation owner or an admin can update.
     * If the user is an admin, they can change the doctor ID. Updates the consultation
     * and redirects to the medical procedure page on success.
     *
     * @param int $patientId The ID of the patient for the consultation
     * @param int $currentUserId The ID of the current logged-in user
     * @param bool $isAdmin Whether the current user has admin privileges
     * @return void
     */
    private function handleUpdateConsultation(int $patientId, int $currentUserId, bool $isAdmin): void
    {
        $consultationId = isset($_POST['id_consultation']) ? (int) $_POST['id_consultation'] : 0;

        if ($consultationId) {
            $consultation = $this->consultationModel->getConsultationById($consultationId);
            if (!$consultation)
                return;

            if (!$isAdmin && $consultation->getDoctorId() !== $currentUserId) {
                error_log("Accès refusé: User $currentUserId a tenté de modifier la consultation $consultationId");
                return;
            }
        }

        $doctorId = ($isAdmin && isset($_POST['doctor_id']) && $_POST['doctor_id'])
            ? (int) $_POST['doctor_id']
            : $currentUserId;

        $title = trim($_POST['consultation_title'] ?? '');
        $date = $_POST['consultation_date'] ?? '';
        $time = $_POST['consultation_time'] ?? '';
        $type = $_POST['consultation_type'] ?? 'Autre';
        $note = trim($_POST['consultation_note'] ?? '');

        if ($consultationId && $title && $date && $time) {
            $fullDate = $date . ' ' . $time . ':00';

            $success = $this->consultationModel->updateConsultation(
                $consultationId,
                $doctorId,
                $fullDate,
                $type,
                $note,
                $title
            );

            if ($success) {
                header("Location: ?page=medicalprocedure&id_patient=" . $patientId);
                exit;
            }
        }
    }

    /**
     * Handles the deletion of a consultation.
     *
     * Verifies access rights: only the consultation owner or an admin can delete.
     * Deletes the consultation and redirects to the medical procedure page on success.
     *
     * @param int $patientId The ID of the patient for the consultation
     * @return void
     */
    private function handleDeleteConsultation(int $patientId): void
    {
        $consultationId = isset($_POST['id_consultation']) ? (int) $_POST['id_consultation'] : 0;
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        $isAdmin = $this->isAdminUser($currentUserId);

        if ($consultationId) {
            $consultation = $this->consultationModel->getConsultationById($consultationId);
            if (!$consultation)
                return;

            if (!$isAdmin && $consultation->getDoctorId() !== $currentUserId) {
                error_log("Accès refusé: User $currentUserId a tenté de supprimer la consultation $consultationId");
                return;
            }

            $success = $this->consultationModel->deleteConsultation($consultationId);

            if ($success) {
                header("Location: ?page=medicalprocedure&id_patient=" . $patientId);
                exit;
            }
        }
    }

    /**
     * Displays the patient's medical procedures view.
     *
     * Retrieves and displays all consultations for the selected patient,
     * sorted by date in descending order. Also retrieves the list of doctors
     * for the form and determines if the current user is an admin.
     *
     * Redirects to login page if user is not authenticated.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit;
        }

        $this->contextService->handleRequest();

        $patientId = $this->contextService->getCurrentPatientId();

        $consultations = [];
        if ($patientId) {
            $consultations = $this->consultationModel->getConsultationsByPatientId($patientId);
        }

        usort($consultations, function ($a, $b) {
            $dateA = \DateTime::createFromFormat('Y-m-d', $a->getDate());
            $dateB = \DateTime::createFromFormat('Y-m-d', $b->getDate());

            if (!$dateA)
                return 1;
            if (!$dateB)
                return -1;
            return $dateB <=> $dateA;
        });

        $doctors = $this->userModel->getAllDoctors();

        $currentUserId = $_SESSION['user_id'] ?? 0;
        $isAdmin = $this->isAdminUser((int) $currentUserId);

        $view = new medicalprocedureView($consultations, $doctors, $isAdmin, (int) $currentUserId, $patientId);
        $view->show();
    }

    /**
     * Checks if a user has admin privileges.
     *
     * Retrieves the user's data and verifies their admin status.
     *
     * @param int $userId The ID of the user to check
     * @return bool True if the user is an admin, false otherwise
     */
    private function isAdminUser(int $userId): bool
    {
        if ($userId <= 0)
            return false;
        $user = $this->userModel->getById($userId);
        return $user && isset($user['admin_status']) && (int) $user['admin_status'] === 1;
    }

    /**
     * Checks if a user is currently logged in.
     *
     * Determines login status by checking for the presence of an email
     * in the session.
     *
     * @return bool True if user is logged in, false otherwise
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}