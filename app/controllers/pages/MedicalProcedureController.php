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
 * Contrôleur de la page actes médicaux du patient.
 */
class MedicalProcedureController
{
    private \PDO $pdo;
    private \modules\models\ConsultationModel $consultationModel;
    private \modules\services\PatientContextService $contextService;
    private \modules\models\PatientModel $patientModel;
    private \modules\models\UserModel $userModel;

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getInstance();
        $this->consultationModel = new \modules\models\ConsultationModel($this->pdo);
        $this->patientModel = new \modules\models\PatientModel($this->pdo);
        $this->userModel = new \modules\models\UserModel($this->pdo);
        $this->contextService = new \modules\services\PatientContextService($this->patientModel);
    }

    /**
     * Gère la requête POST (ajout consultation).
     */
    public function post(): void
    {
        $this->handlePostRequest();
        $this->get();
    }

    /**
     * Traite les données soumises via le formulaire POST.
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

            $action = $_POST['action'];

            if ($action === 'add_consultation') {
                $this->handleAddConsultation($patientId, $currentUserId);
            } elseif ($action === 'update_consultation') {
                $this->handleUpdateConsultation($patientId, $currentUserId);
            } elseif ($action === 'delete_consultation') {
                $this->handleDeleteConsultation($patientId);
            }
        }
    }

    private function handleAddConsultation(int $patientId, int $currentUserId): void
    {
        $doctorId = isset($_POST['doctor_id']) ? (int) $_POST['doctor_id'] : $currentUserId;
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

    private function handleUpdateConsultation(int $patientId, int $currentUserId): void
    {
        $consultationId = isset($_POST['id_consultation']) ? (int) $_POST['id_consultation'] : 0;
        $doctorId = isset($_POST['doctor_id']) ? (int) $_POST['doctor_id'] : $currentUserId;
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

    private function handleDeleteConsultation(int $patientId): void
    {
        $consultationId = isset($_POST['id_consultation']) ? (int) $_POST['id_consultation'] : 0;

        if ($consultationId) {
            $success = $this->consultationModel->deleteConsultation($consultationId);

            if ($success) {
                header("Location: ?page=medicalprocedure&id_patient=" . $patientId);
                exit;
            }
        }
    }

    /**
     * Affiche la vue des actes médicaux du patient si l'utilisateur est connecté.
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit;
        }

        // Gestion du contexte (Cookies / URL)
        $this->contextService->handleRequest();

        // Récupération de l'ID patient via le contexte
        $patientId = $this->contextService->getCurrentPatientId();

        // Si aucun patient n'est sélectionné, on peut soit rediriger, soit afficher vide
        // Ici, on tente de récupérer les consultations si un ID existe
        $consultations = [];
        if ($patientId) {
            $consultations = $this->consultationModel->getConsultationsByPatientId($patientId);
        }

        // Trier par date décroissante (plus récente -> plus ancienne)
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

        // Récupérer la liste des médecins pour le formulaire
        $doctors = $this->userModel->getAllDoctors();

        // Passer la liste triée à la vue
        $view = new medicalprocedureView($consultations, $doctors);
        $view->show();
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
