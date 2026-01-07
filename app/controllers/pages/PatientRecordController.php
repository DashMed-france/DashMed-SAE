<?php

declare(strict_types=1);

namespace modules\controllers\pages;

use modules\views\pages\PatientRecordView;
use modules\models\PatientModel;
use modules\models\consultation;
use modules\services\PatientContextService;
use Database;
use PDO;

require_once __DIR__ . '/../../../assets/includes/database.php';

/**
 * Controller for managing the patient record page.
 *
 * This controller handles the display of patient information, medical team,
 * and processes patient record updates.
 */
class PatientRecordController
{
    /**
     * PDO database connection instance.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Model for managing patient data.
     *
     * @var PatientModel
     */
    private PatientModel $patientModel;

    /**
     * Service for managing patient context (selected patient).
     *
     * @var PatientContextService
     */
    private PatientContextService $contextService;

    /**
     * Constructor for PatientRecordController.
     *
     * Allows dependency injection for easier testing. Initializes the database
     * connection, patient model, and context service.
     *
     * @param PDO|null $pdo Optional PDO instance for dependency injection
     * @param PatientModel|null $patientModel Optional PatientModel instance for dependency injection
     * @param PatientContextService|null $contextService Optional PatientContextService instance for dependency injection
     */
    public function __construct(?PDO $pdo = null, ?PatientModel $patientModel = null, ?PatientContextService $contextService = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();

        $this->patientModel = $patientModel ?? new PatientModel($this->pdo);
        $this->contextService = $contextService ?? new PatientContextService($this->patientModel);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Retrieves the current patient ID via the context service.
     *
     * @return int The unique identifier of the patient
     */
    private function getCurrentPatientId(): int
    {
        $this->contextService->handleRequest();
        return $this->contextService->getCurrentPatientId();
    }

    /**
     * Handles GET requests for the patient record page.
     *
     * Prepares all necessary data and displays the view. Retrieves patient information,
     * medical team, and consultations (past and future). Redirects to login if user
     * is not authenticated.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit();
        }

        $idPatient = $this->getCurrentPatientId();

        try {
            $patientData = $this->patientModel->findById($idPatient);

            if (!$patientData) {
                $patientData = [
                    'id_patient' => $idPatient,
                    'first_name' => 'Patient',
                    'last_name' => 'Inconnu',
                    'birth_date' => null,
                    'gender' => 'U',
                    'admission_cause' => 'Dossier non trouvé ou inexistant.',
                    'medical_history' => '',
                    'age' => 0
                ];
            } else {
                $patientData['age'] = $this->calculateAge($patientData['birth_date'] ?? null);
            }

            $doctors = $this->patientModel->getDoctors($idPatient);


            $toutesConsultations = $this->getConsultations();
            $consultationsPassees = [];
            $consultationsFutures = [];
            $dateAujourdhui = new \DateTime();

            foreach ($toutesConsultations as $consultation) {
                $dStr = $consultation->getDate();
                $dObj = \DateTime::createFromFormat('d/m/Y', $dStr);

                if (!$dObj) {
                    $dObj = \DateTime::createFromFormat('Y-m-d', $dStr);
                }

                if ($dObj && $dObj < $dateAujourdhui) {
                    $consultationsPassees[] = $consultation;
                } else {
                    $consultationsFutures[] = $consultation;
                }
            }

            $msg = $_SESSION['patient_msg'] ?? null;
            if (isset($_SESSION['patient_msg'])) {
                unset($_SESSION['patient_msg']);
            }

            $view = new PatientRecordView(
                $consultationsPassees,
                $consultationsFutures,
                $patientData,
                $doctors,
                $msg
            );
            $view->show();

        } catch (\Throwable $e) {
            error_log("[PatientRecordController] Erreur critique dans get(): " . $e->getMessage());
            $view = new PatientRecordView([], [], [], [], ['type' => 'error', 'text' => 'Une erreur interne est survenue lors du chargement du dossier.']);
            $view->show();
        }
    }

    /**
     * Handles POST requests for patient record updates.
     *
     * Processes form submissions to update patient information. Validates CSRF token,
     * required fields, and date format. Updates the patient record and redirects
     * with a success or error message.
     *
     * @return void
     */
    public function post(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit();
        }

        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_patient'] ?? '', $_POST['csrf'])) {
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Session expirée. Veuillez rafraîchir la page.'];
            header('Location: /?page=dossierpatient');
            exit;
        }

        $idPatient = $this->getCurrentPatientId();

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $admissionCause = trim($_POST['admission_cause'] ?? '');
        $medicalHistory = trim($_POST['medical_history'] ?? '');
        $birthDate = trim($_POST['birth_date'] ?? '');

        if ($firstName === '' || $lastName === '' || $admissionCause === '') {
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Merci de remplir tous les champs obligatoires.'];
            header('Location: /?page=dossierpatient');
            exit;
        }

        if ($birthDate !== '') {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $birthDate);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $birthDate) {
                $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Le format de la date de naissance est invalide.'];
                header('Location: /?page=dossierpatient');
                exit;
            }
            if ($dateObj > new \DateTime()) {
                $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'La date de naissance ne peut pas être future.'];
                header('Location: /?page=dossierpatient');
                exit;
            }
        }

        try {
            $updateData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'birth_date' => $birthDate,
                'admission_cause' => $admissionCause,
                'medical_history' => $medicalHistory
            ];

            $success = $this->patientModel->update($idPatient, $updateData);

            if ($success) {
                $_SESSION['patient_msg'] = ['type' => 'success', 'text' => 'Dossier mis à jour avec succès.'];
            } else {
                $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Aucune modification détectée ou erreur de sauvegarde.'];
            }
        } catch (\Exception $e) {
            error_log("[PatientRecordController] Erreur UPDATE: " . $e->getMessage());
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Erreur technique lors de la sauvegarde.'];
        }

        header('Location: /?page=dossierpatient');
        exit;
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

    /**
     * Calculates age from a birth date string.
     *
     * Accepts dates in Y-m-d or d/m/Y format and returns the age in years.
     *
     * @param string|null $birthDateString Birth date in Y-m-d or d/m/Y format
     * @return int Age in years, or 0 if date is invalid or null
     */
    private function calculateAge(?string $birthDateString): int
    {
        if (empty($birthDateString)) {
            return 0;
        }
        try {
            $birthDate = new \DateTime($birthDateString);
            $today = new \DateTime();
            return $today->diff($birthDate)->y;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Retrieves a mocked list of consultations for display.
     *
     * TODO: Replace with ConsultationModel::getByPatientId()
     *
     * @return consultation[] Array of consultation objects
     */
    private function getConsultations(): array
    {
        $consultations = [];

        $consultations[] = new consultation(1, 'Dr. Dupont', '08/10/2025', 'Radio du genou', 'Imagerie', 'Résultats normaux', 'doc123.pdf');
        $consultations[] = new consultation(2, 'Dr. Martin', '15/10/2025', 'Consultation de suivi', 'Consultation', 'Patient en bonne voie', 'doc124.pdf');
        $consultations[] = new consultation(3, 'Dr. Leblanc', '22/10/2025', 'Examen sanguin', 'Analyse', 'Valeurs normales', 'doc125.pdf');
        $consultations[] = new consultation(4, 'Dr. Durant', '10/11/2025', 'Contrôle post-op', 'Consultation', 'Cicatrisation ok', 'doc126.pdf');
        $consultations[] = new consultation(5, 'Dr. Bernard', '20/11/2025', 'Radio thoracique', 'Imagerie', 'Contrôle routine', 'doc127.pdf');
        $consultations[] = new consultation(6, 'Dr. Petit', '05/12/2025', 'Bilan sanguin', 'Analyse', 'Annuel', 'doc128.pdf');

        return $consultations;
    }
}