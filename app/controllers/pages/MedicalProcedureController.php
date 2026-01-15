<?php

namespace modules\controllers\pages;

use modules\views\pages\MedicalprocedureView;
use modules\models\ConsultationModel;
use modules\services\PatientContextService;
use modules\models\PatientModel;
use modules\models\UserModel;
use assets\includes\Database;

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
        $this->pdo = $pdo ?? Database::getInstance();
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

            if (!$patientId || !$currentUserId) {
                return;
            }

            $rawCurrentUserId = $_SESSION['user_id'] ?? 0;
            $isAdmin = $this->isAdminUser(is_numeric($rawCurrentUserId) ? (int) $rawCurrentUserId : 0);

            $rawAction = $_POST['action'];
            $action = is_string($rawAction) ? $rawAction : '';

            $rawDoctorId = $_POST['doctor_id'] ?? null;
            $currentUserInt = is_numeric($rawCurrentUserId) ? (int) $rawCurrentUserId : 0;

            if ($action === 'add_consultation') {
                $this->handleAddConsultation($patientId, $currentUserInt, $isAdmin);
            } elseif ($action === 'update_consultation') {
                $this->handleUpdateConsultation($patientId, $currentUserInt, $isAdmin);
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
        $rawDoctorId = $_POST['doctor_id'] ?? null;
        $doctorId = ($isAdmin && $rawDoctorId !== null && is_numeric($rawDoctorId) && (int) $rawDoctorId > 0)
            ? (int) $rawDoctorId
            : $currentUserId;

        $rawTitle = $_POST['consultation_title'] ?? '';
        $title = trim(is_string($rawTitle) ? $rawTitle : '');
        $rawDate = $_POST['consultation_date'] ?? '';
        $date = is_string($rawDate) ? $rawDate : '';
        $rawTime = $_POST['consultation_time'] ?? '';
        $time = is_string($rawTime) ? $rawTime : '';
        $rawType = $_POST['consultation_type'] ?? 'Autre';
        $type = is_string($rawType) ? $rawType : 'Autre';
        $rawNote = $_POST['consultation_note'] ?? '';
        $note = trim(is_string($rawNote) ? $rawNote : '');

        if ($title !== '' && $date !== '' && $time !== '') {
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
        $rawConsId = $_POST['id_consultation'] ?? 0;
        $consultationId = is_numeric($rawConsId) ? (int) $rawConsId : 0;

        if ($consultationId) {
            $consultation = $this->consultationModel->getConsultationById($consultationId);
            if (!$consultation) {
                return;
            }

            if (!$isAdmin && $consultation->getDoctorId() !== $currentUserId) {
                error_log(
                    "Access denied: User $currentUserId tried to modify consultation $consultationId | Accès refusé"
                );
                return;
            }
        }

        $rawDoctorId2 = $_POST['doctor_id'] ?? null;
        $doctorId = ($isAdmin && $rawDoctorId2 !== null && is_numeric($rawDoctorId2) && (int) $rawDoctorId2 > 0)
            ? (int) $rawDoctorId2
            : $currentUserId;

        $rawTitle2 = $_POST['consultation_title'] ?? '';
        $title = trim(is_string($rawTitle2) ? $rawTitle2 : '');
        $rawDate2 = $_POST['consultation_date'] ?? '';
        $date = is_string($rawDate2) ? $rawDate2 : '';
        $rawTime2 = $_POST['consultation_time'] ?? '';
        $time = is_string($rawTime2) ? $rawTime2 : '';
        $rawType2 = $_POST['consultation_type'] ?? 'Autre';
        $type = is_string($rawType2) ? $rawType2 : 'Autre';
        $rawNote2 = $_POST['consultation_note'] ?? '';
        $note = trim(is_string($rawNote2) ? $rawNote2 : '');

        if ($consultationId && $title !== '' && $date !== '' && $time !== '') {
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
        $rawDelConsId = $_POST['id_consultation'] ?? 0;
        $consultationId = is_numeric($rawDelConsId) ? (int) $rawDelConsId : 0;
        $rawDelUserId = $_SESSION['user_id'] ?? 0;
        $currentUserId = is_numeric($rawDelUserId) ? (int) $rawDelUserId : 0;
        $isAdmin = $this->isAdminUser($currentUserId);

        if ($consultationId) {
            $consultation = $this->consultationModel->getConsultationById($consultationId);
            if (!$consultation) {
                return;
            }

            if (!$isAdmin && $consultation->getDoctorId() !== $currentUserId) {
                error_log(
                    "Access denied: User $currentUserId tried to delete consultation $consultationId | Accès refusé"
                );
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

        $this->contextService->handleRequest();

        $patientId = $this->contextService->getCurrentPatientId();

        $consultations = [];
        if ($patientId) {
            $consultations = $this->consultationModel->getConsultationsByPatientId($patientId);
        }

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

        $doctors = $this->userModel->getAllDoctors();

        $currentUserId = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])
            ? (int) $_SESSION['user_id']
            : 0;
        $isAdmin = $this->isAdminUser($currentUserId);

        $view = new MedicalprocedureView($consultations, $doctors, $isAdmin, $currentUserId, $patientId);
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
        return $user
            && isset($user['admin_status'])
            && is_numeric($user['admin_status'])
            && (int) $user['admin_status'] === 1;
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
