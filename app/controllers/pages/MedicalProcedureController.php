<?php

namespace modules\controllers\pages;

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

            // Vérifier si l'utilisateur est admin
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

    private function handleAddConsultation(int $patientId, int $currentUserId, bool $isAdmin): void
    {
        // Si admin, on prend l'ID du formulaire, sinon on impose l'utilisateur courant
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

    private function handleUpdateConsultation(int $patientId, int $currentUserId, bool $isAdmin): void
    {
        $consultationId = isset($_POST['id_consultation']) ? (int) $_POST['id_consultation'] : 0;

        // Vérification des droits
        if ($consultationId) {
            $consultation = $this->consultationModel->getConsultationById($consultationId);
            if (!$consultation) {
                return;
            }

            // Si ce n'est pas mon patient et que je ne suis pas admin => refus
            if (!$isAdmin && $consultation->getDoctorId() !== $currentUserId) {
                // Tentative de modification non autorisée
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

    private function handleDeleteConsultation(int $patientId): void
    {
        $consultationId = isset($_POST['id_consultation']) ? (int) $_POST['id_consultation'] : 0;
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        $isAdmin = $this->isAdminUser($currentUserId);

        if ($consultationId) {
            // Vérification des droits
            $consultation = $this->consultationModel->getConsultationById($consultationId);
            if (!$consultation) {
                return;
            }

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
        $consultations = [];
        if ($patientId) {
            $consultations = $this->consultationModel->getConsultationsByPatientId($patientId);
        }

        // Trier par date décroissante
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

        // Vérifier si l'utilisateur courant est admin
        $currentUserId = $_SESSION['user_id'] ?? 0;
        $isAdmin = $this->isAdminUser((int) $currentUserId);

        // Passer la liste triée, les médecins, le statut admin et l'ID patient à la vue
        $view = new medicalprocedureView($consultations, $doctors, $isAdmin, (int) $currentUserId, $patientId);
        $view->show();
    }

    /**
     * Vérifie si l'utilisateur est admin
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
     * Vérifie si l'utilisateur est connecté.
     *
     * @return bool
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
