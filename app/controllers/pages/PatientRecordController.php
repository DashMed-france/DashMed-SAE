<?php

namespace modules\controllers\pages;

use modules\views\pages\PatientRecordView;
use modules\controllers\pages\MonitoringController;
use modules\models\PatientModel;
use modules\models\consultation;
use Database;
use PDO;

require_once __DIR__ . '/../../../assets/includes/database.php';

/**
 * Controller for the Patient Folder (Dossier Patient) page.
 */
class PatientRecordController
{
    private PDO $pdo;
    private PatientModel $patientModel;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->patientModel = new PatientModel($this->pdo);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Finds the current patient ID.
     * Prioritizes GET/POST request, then falls back to MonitoringController default.
     */
    private function getCurrentPatientId(): int
    {
        return (int) ($_REQUEST['id_patient'] ?? MonitoringController::$idPatient);
    }

    /**
     * Displays the patient folder view.
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit();
        }

        $idPatient = $this->getCurrentPatientId();

        try {
            // Fetch Patient Data
            $patientData = $this->patientModel->findById($idPatient);

            if (!$patientData) {
                // Fallback if not found (or handle 404)
                $patientData = [
                    'id_patient' => $idPatient,
                    'first_name' => 'Patient',
                    'last_name' => 'Inconnu',
                    'birth_date' => null,
                    'gender' => 'F',
                    'admission_cause' => 'Dossier non trouvé',
                    'medical_history' => '',
                    'age' => 0
                ];
            } else {
                // Calculate Age
                $patientData['age'] = 0;
                if (!empty($patientData['birth_date'])) {
                    $birthDate = new \DateTime($patientData['birth_date']);
                    $today = new \DateTime();
                    $patientData['age'] = $today->diff($birthDate)->y;
                }
            }

            // Fetch Doctors (Placeholder for now)
            $doctors = $this->patientModel->getDoctors($idPatient);

            // Consultations fetching removed as requested
            $consultationsPassees = [];
            $consultationsFutures = [];

            $msg = $_SESSION['patient_msg'] ?? null;
            unset($_SESSION['patient_msg']);

            $view = new PatientRecordView($consultationsPassees, $consultationsFutures, $patientData, $doctors, $msg);
            $view->show();

        } catch (\Throwable $e) {
            error_log("[PatientRecordController] Error in get(): " . $e->getMessage());
            // Optionally show an error page or fallback
            // We verify if headers sent to avoid double output issues if possible, though strict MVC usually renders once.
            // But since we are likely crashing during render, we might want to just stop or try simple error.

            // If we haven't started outputting, we can show the view with error.
            // If we HAVE started, we are kind of stuck, but let's try to show the view content if possible.
            $view = new PatientRecordView([], [], [], [], ['type' => 'error', 'text' => 'Une erreur est survenue lors du chargement du dossier.']);
            $view->show();
        }
    }

    /**
     * Handles patient data updates.
     */
    public function post(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit();
        }

        // CSRF Check
        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_patient'] ?? '', $_POST['csrf'])) {
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Session expirée, veuillez réessayer.'];
            header('Location: /?page=dossierpatient');
            exit;
        }

        $idPatient = $this->getCurrentPatientId();

        // Sanitize and Validate Inputs
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $admissionCause = trim($_POST['admission_cause'] ?? '');
        $medicalHistory = trim($_POST['medical_history'] ?? ''); // New field handling
        $birthDate = trim($_POST['birth_date'] ?? '');

        if ($firstName === '' || $lastName === '' || $admissionCause === '') {
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Tous les champs obligatoires doivent être remplis.'];
            header('Location: /?page=dossierpatient');
            exit;
        }

        // Date Validation
        if ($birthDate !== '') {
            $date = \DateTime::createFromFormat('Y-m-d', $birthDate);
            if (!$date || $date->format('Y-m-d') !== $birthDate) {
                $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Format de date de naissance invalide.'];
                header('Location: /?page=dossierpatient');
                exit;
            }
            if ($date > new \DateTime()) {
                $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'La date de naissance ne peut pas être dans le futur.'];
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
                $_SESSION['patient_msg'] = ['type' => 'success', 'text' => 'Dossier patient mis à jour avec succès.'];
            } else {
                $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Aucune modification détectée ou erreur lors de la mise à jour.'];
            }

        } catch (\Exception $e) {
            error_log("[PatientRecordController] Update failed: " . $e->getMessage());
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Erreur technique lors de la sauvegarde.'];
        }

        header('Location: /?page=dossierpatient');
        exit;
    }

    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Returns mock consultations as per existing logic.
     */
    private function getConsultations(): array
    {
        $consultations = [];

        $consultations[] = new consultation(
            'Dr. Dupont',
            '08/10/2025',
            'Radio du genou',
            'Résultats normaux',
            'doc123.pdf'
        );

        $consultations[] = new consultation(
            'Dr. Martin',
            '15/10/2025',
            'Consultation de suivi',
            'Patient en bonne voie de guérison',
            'doc124.pdf'
        );

        $consultations[] = new consultation(
            'Dr. Leblanc',
            '22/10/2025',
            'Examen sanguin',
            'Valeurs normales',
            'doc125.pdf'
        );

        // Consultations futures
        $consultations[] = new consultation(
            'Dr. Durant',
            '10/11/2025',
            'Contrôle post-opératoire',
            'Cicatrisation à vérifier',
            'doc126.pdf'
        );

        $consultations[] = new consultation(
            'Dr. Bernard',
            '20/11/2025',
            'Radiographie thoracique',
            'Contrôle de routine',
            'doc127.pdf'
        );

        $consultations[] = new consultation(
            'Dr. Petit',
            '05/12/2025',
            'Bilan sanguin complet',
            'Analyse annuelle',
            'doc128.pdf'
        );

        return $consultations;
    }
}