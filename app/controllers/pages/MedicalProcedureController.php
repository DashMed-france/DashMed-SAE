<?php

namespace modules\controllers\pages;

use modules\views\pages\medicalprocedureView;
use modules\models\ConsultationModel;
use modules\services\PatientContextService;
use modules\models\PatientModel;
use modules\models\UserModel;

/**
 * Class MedicalProcedureController | Contrôleur des Actes Médicaux
 *
 * Manages the patient's medical procedures/consultations page.
 * Contrôleur de la page actes médicaux du patient.
 *
 * @package DashMed\Modules\Controllers\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class MedicalProcedureController
{
    private \PDO $pdo;
    private \modules\models\ConsultationModel $consultationModel;
    private \modules\services\PatientContextService $contextService;
    private \modules\models\PatientModel $patientModel;
    private \modules\models\UserModel $userModel;

    /**
     * Constructor | Constructeur
     *
     * @param \PDO|null $pdo Database connection (optional) | Connexion BDD (optionnel)
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
     * Handles POST requests (add consultation).
     * Gère la requête POST (ajout consultation).
     *
     * @return void
     */
    public function post(): void
    {
        $this->handlePostRequest();
        $this->get();
    }

    /**
     * Processes POST data.
     * Traite les données soumises via le formulaire POST.
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

            // Check essential IDs
            if (!$patientId || !$currentUserId) {
                return;
            }

            // Verify admin status
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
     * Adds a consultation.
     * Ajoute une consultation.
     */
    private function handleAddConsultation(int $patientId, int $currentUserId, bool $isAdmin): void
    {
        // If admin, use form ID, else use current user ID
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
     * Updates a consultation.
     * Met à jour une consultation.
     */
    private function handleUpdateConsultation(int $patientId, int $currentUserId, bool $isAdmin): void
    {
        $consultationId = isset($_POST['id_consultation']) ? (int) $_POST['id_consultation'] : 0;

        // Check permissions
        if ($consultationId) {
            $consultation = $this->consultationModel->getConsultationById($consultationId);
            if (!$consultation) {
                return;
            }

            // If not own patient and not admin => deny
            if (!$isAdmin && $consultation->getDoctorId() !== $currentUserId) {
                // Unauthorized modification attempt
                error_log("Access denied: User $currentUserId tried to modify consultation $consultationId | Accès refusé");
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
     * Deletes a consultation.
     * Supprime une consultation.
     */
    private function handleDeleteConsultation(int $patientId): void
    {
        $consultationId = isset($_POST['id_consultation']) ? (int) $_POST['id_consultation'] : 0;
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        $isAdmin = $this->isAdminUser($currentUserId);

        if ($consultationId) {
            // Check permissions
            $consultation = $this->consultationModel->getConsultationById($consultationId);
            if (!$consultation) {
                return;
            }

            if (!$isAdmin && $consultation->getDoctorId() !== $currentUserId) {
                error_log("Access denied: User $currentUserId tried to delete consultation $consultationId | Accès refusé");
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
     * Handles GET request: Display medical procedures view.
     * Affiche la vue des actes médicaux du patient si l'utilisateur est connecté.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit;
        }

        // Context Management (Cookies / URL)
        $this->contextService->handleRequest();

        // Get patient ID from context
        $patientId = $this->contextService->getCurrentPatientId();

        // If no patient selected, handle accordingly
        $consultations = [];
        if ($patientId) {
            $consultations = $this->consultationModel->getConsultationsByPatientId($patientId);
        }

        // Sort by date descending
        usort($consultations, function ($a, $b) {
            $dateA = \DateTime::createFromFormat('Y-m-d', $a->getDate());
            $dateB = \DateTime::createFromFormat('Y-m-d', $b->getDate());

            if (!$dateA) {
                return 1;
            }
            if (!$dateB) {
                return -1;
            }
            return $dateB <=> $dateA;
        });

        // Get doctor list for form
        $doctors = $this->userModel->getAllDoctors();

        // Check is current user admin
        $currentUserId = $_SESSION['user_id'] ?? 0;
        $isAdmin = $this->isAdminUser((int) $currentUserId);

        // Pass sorted list, doctors, admin status and patient ID to view
        $view = new medicalprocedureView($consultations, $doctors, $isAdmin, (int) $currentUserId, $patientId);
        $view->show();
    }

    /**
     * Checks if user is admin.
     * Vérifie si l'utilisateur est admin.
     *
     * @param int $userId
     * @return bool
     */
    private function isAdminUser(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $user = $this->userModel->getById($userId);
        return $user && isset($user['admin_status']) && (int) $user['admin_status'] === 1;
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
