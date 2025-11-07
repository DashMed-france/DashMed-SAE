<?php

namespace modules\controllers\pages;

use modules\views\pages\dossierpatientView;
use modules\models\consultation;
use Database;
use PDO;

require_once __DIR__ . '/../../../assets/includes/database.php';

/**
 * Contrôleur du tableau de bord.
 */
class DossierpatientController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Affiche la vue du tableau de bord si l'utilisateur est connecté.
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit();
        }

        // TODO: récupérer dynamiquement l'ID du patient (route/session)
        $idPatient = 1;

        // Récupération des données patient
        $patientData = $this->getPatientData($idPatient);

        $toutesConsultations = $this->getConsultations();

        $dateAujourdhui = new \DateTime();
        $consultationsPassees = [];
        $consultationsFutures = [];

        foreach ($toutesConsultations as $consultation) {
            $dateConsultation = \DateTime::createFromFormat('d/m/Y', $consultation->getDate());

            if ($dateConsultation < $dateAujourdhui) {
                $consultationsPassees[] = $consultation;
            } else {
                $consultationsFutures[] = $consultation;
            }
        }

        $msg = $_SESSION['patient_msg'] ?? null;
        unset($_SESSION['patient_msg']);

        $view = new dossierpatientView($consultationsPassees, $consultationsFutures, $patientData, $msg);
        $view->show();
    }

    /**
     * Traite la mise à jour des données patient
     */
    public function post(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit();
        }

        // Vérification CSRF
        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_patient'] ?? '', $_POST['csrf'])) {
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Session expirée, réessayez.'];
            header('Location: /?page=dossierpatient');
            exit;
        }

        $idPatient = (int)($_POST['id_patient'] ?? 1); // TODO: récupérer depuis session/route

        // Récupération des données du formulaire
        $admissionCause = trim($_POST['admission_cause'] ?? '');
        $medicalHistory = trim($_POST['medical_history'] ?? '');

        if ($admissionCause === '' || $medicalHistory === '') {
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Tous les champs sont obligatoires.'];
            header('Location: /?page=dossierpatient');
            exit;
        }

        try {
            // Mise à jour dans la table patients
            $sql = "UPDATE patients 
                    SET description = :admission_cause,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id_patient = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':admission_cause' => $admissionCause,
                ':id' => $idPatient
            ]);

            // Mise à jour des antécédents médicaux (si table séparée existe)
            // Sinon, on pourrait les stocker dans un champ JSON ou texte

            $_SESSION['patient_msg'] = ['type' => 'success', 'text' => 'Informations patient mises à jour avec succès.'];
        } catch (\PDOException $e) {
            error_log('[DossierPatient] Update failed: ' . $e->getMessage());
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Erreur lors de la mise à jour.'];
        }

        header('Location: /?page=dossierpatient');
        exit;
    }

    /**
     * Récupère les données du patient
     */
    private function getPatientData(int $idPatient): array
    {
        $sql = "SELECT 
                    p.id_patient,
                    p.first_name,
                    p.last_name,
                    p.birth_date,
                    p.gender,
                    p.description as admission_cause
                FROM patients p
                WHERE p.id_patient = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $idPatient]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return [
                'id_patient' => $idPatient,
                'first_name' => 'Patient',
                'last_name' => 'Inconnu',
                'birth_date' => null,
                'gender' => 'F',
                'admission_cause' => 'Non renseigné',
                'age' => 0
            ];
        }

        // Calcul de l'âge
        if ($data['birth_date']) {
            $birthDate = new \DateTime($data['birth_date']);
            $today = new \DateTime();
            $data['age'] = $today->diff($birthDate)->y;
        } else {
            $data['age'] = 0;
        }

        return $data;
    }

    /**
     * Vérifie si l'utilisateur est connecté.
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Récupère les consultations du patient.
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