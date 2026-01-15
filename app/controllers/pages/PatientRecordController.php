<?php

declare(strict_types=1);

namespace modules\controllers\pages;

use modules\views\pages\PatientRecordView;
use modules\models\PatientModel;
use modules\models\Consultation;
use modules\services\PatientContextService;
use assets\includes\Database;
use PDO;

/**
 * Class PatientRecordController | Contrôleur Dossier Patient
 *
 * Manages the "Patient Record" page.
 * Contrôleur pour la page "Dossier Patient".
 *
 * Handles display of patient info, medical team, and record updates.
 * Gère l'affichage des informations du patient, de l'équipe médicale
 * et le traitement des mises à jour du dossier.
 *
 * @package DashMed\Modules\Controllers\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class PatientRecordController
{
    /** @var PDO Database connection | Connexion BDD */
    private PDO $pdo;

    /** @var PatientModel Patient model | Modèle Patient */
    private PatientModel $patientModel;

    /** @var PatientContextService Patient context service | Service de contexte patient */
    private PatientContextService $contextService;

    /**
     * Constructor.
     * Constructeur.
     *
     * Dependency injection allowed for testing.
     * Injection de dépendances possible pour faciliter les tests.
     *
     * @param PDO|null $pdo Database connection | Instance de connexion à la base de données.
     * @param PatientModel|null $patientModel Patient Model | Instance du modèle Patient.
     * @param PatientContextService|null $contextService Context Service | Service de contexte patient.
     */
    public function __construct(
        ?PDO $pdo = null,
        ?PatientModel $patientModel = null,
        ?PatientContextService $contextService = null
    ) {
        $this->pdo = $pdo ?? Database::getInstance();

        $this->patientModel = $patientModel ?? new PatientModel($this->pdo);
        $this->contextService = $contextService ?? new PatientContextService($this->patientModel);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Gets current patient ID via context service.
     * Récupère l'ID du patient courant via le service de contexte.
     *
     * @return int Patient ID | L'identifiant unique du patient.
     */
    private function getCurrentPatientId(): int
    {
        $this->contextService->handleRequest();
        return $this->contextService->getCurrentPatientId();
    }

    /**
     * HTTP GET Entry point.
     * Point d'entrée pour la méthode HTTP GET.
     *
     * Prepares data and displays view.
     * Prépare les données et affiche la vue.
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

            /** @var array{type: string, text: string}|null $msg */
            $msg = isset($_SESSION['patient_msg']) && is_array($_SESSION['patient_msg'])
                ? $_SESSION['patient_msg']
                : null;
            if (isset($_SESSION['patient_msg'])) {
                unset($_SESSION['patient_msg']);
            }

            $safeDoctors = array_map(function ($d) {
                $d['profession_name'] = (string) ($d['profession_name'] ?? '');
                return $d;
            }, $doctors);

            $view = new PatientRecordView(
                $consultationsPassees,
                $consultationsFutures,
                $patientData,
                $safeDoctors,
                $msg
            );
            $view->show();
        } catch (\Throwable $e) {
            error_log("[PatientRecordController] Erreur critique dans get(): " . $e->getMessage());
            $view = new PatientRecordView(
                [],
                [],
                [],
                [],
                ['type' => 'error', 'text' => 'Une erreur interne est survenue lors du chargement du dossier.']
            );
            $view->show();
        }
    }

    /**
     * HTTP POST Entry point.
     * Point d'entrée pour la méthode HTTP POST.
     *
     * Handles form submissions (record update).
     * Traite les soumissions de formulaire (mise à jour du dossier).
     *
     * @return void
     */
    public function post(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit();
        }

        $sessionCsrf = isset($_SESSION['csrf_patient']) && is_string($_SESSION['csrf_patient'])
            ? $_SESSION['csrf_patient']
            : '';
        $postCsrf = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
        if ($sessionCsrf === '' || $postCsrf === '' || !hash_equals($sessionCsrf, $postCsrf)) {
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Session expirée. Veuillez rafraîchir la page.'];
            header('Location: /?page=dossierpatient');
            exit;
        }

        $idPatient = $this->getCurrentPatientId();

        $rawFirstName = $_POST['first_name'] ?? '';
        $firstName = trim(is_string($rawFirstName) ? $rawFirstName : '');
        $rawLastName = $_POST['last_name'] ?? '';
        $lastName = trim(is_string($rawLastName) ? $rawLastName : '');
        $rawAdmCause = $_POST['admission_cause'] ?? '';
        $admissionCause = trim(is_string($rawAdmCause) ? $rawAdmCause : '');
        $rawMedHistory = $_POST['medical_history'] ?? '';
        $medicalHistory = trim(is_string($rawMedHistory) ? $rawMedHistory : '');
        $rawBirthDate = $_POST['birth_date'] ?? '';
        $birthDate = trim(is_string($rawBirthDate) ? $rawBirthDate : '');

        if ($firstName === '' || $lastName === '' || $admissionCause === '') {
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Merci de remplir tous les champs obligatoires.'];
            header('Location: /?page=dossierpatient');
            exit;
        }

        if ($birthDate !== '') {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $birthDate);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $birthDate) {
                $_SESSION['patient_msg'] =
                    ['type' => 'error', 'text' => 'Le format de la date de naissance est invalide.'];
                header('Location: /?page=dossierpatient');
                exit;
            }
            if ($dateObj > new \DateTime()) {
                $_SESSION['patient_msg'] =
                    ['type' => 'error', 'text' => 'La date de naissance ne peut pas être future.'];
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
                $_SESSION['patient_msg'] =
                    ['type' => 'success', 'text' => 'Dossier mis à jour avec succès.'];
            } else {
                $_SESSION['patient_msg'] =
                    ['type' => 'error', 'text' => 'Aucune modification détectée ou erreur de sauvegarde.'];
            }
        } catch (\Exception $e) {
            error_log("[PatientRecordController] Erreur UPDATE: " . $e->getMessage());
            $_SESSION['patient_msg'] =
                ['type' => 'error', 'text' => 'Erreur technique lors de la sauvegarde.'];
        }

        header('Location: /?page=dossierpatient');
        exit;
    }

    /**
     * Checks if user is logged in.
     * Vérifie si l'utilisateur est connecté via la session.
     *
     * @return bool
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Calculates age from birth date.
     * Calcule l'âge à partir d'une date de naissance.
     *
     * @param string|null $birthDateString Date format Y-m-d or d/m/Y | Date au format Y-m-d ou d/m/Y
     * @return int Age in years | Âge en années
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
     * Retrieves mocked consultation list.
     * Récupère une liste mockée de consultations pour l'affichage.
     *
     * Demo dataset.
     * Jeu de données de démonstration pour les consultations.
     *
     * @return consultation[]
     */
    private function getConsultations(): array
    {
        $consultations = [];

        $consultations[] = new Consultation(
            1,
            'Dr. Dupont',
            '08/10/2025',
            'Radio du genou',
            'Imagerie',
            'Résultats normaux',
            'doc123.pdf'
        );
        $consultations[] = new Consultation(
            2,
            'Dr. Martin',
            '15/10/2025',
            'Consultation de suivi',
            'Consultation',
            'Patient en bonne voie',
            'doc124.pdf'
        );
        $consultations[] = new Consultation(
            3,
            'Dr. Leblanc',
            '22/10/2025',
            'Examen sanguin',
            'Analyse',
            'Valeurs normales',
            'doc125.pdf'
        );
        $consultations[] = new Consultation(
            4,
            'Dr. Durant',
            '10/11/2025',
            'Contrôle post-op',
            'Consultation',
            'Cicatrisation ok',
            'doc126.pdf'
        );
        $consultations[] = new Consultation(
            5,
            'Dr. Bernard',
            '20/11/2025',
            'Radio thoracique',
            'Imagerie',
            'Contrôle routine',
            'doc127.pdf'
        );
        $consultations[] = new Consultation(
            6,
            'Dr. Petit',
            '05/12/2025',
            'Bilan sanguin',
            'Analyse',
            'Annuel',
            'doc128.pdf'
        );

        return $consultations;
    }
}
