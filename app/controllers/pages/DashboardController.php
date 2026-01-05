<?php

namespace modules\controllers\pages;

use DateTime;
use modules\views\pages\dashboardView;
use modules\models\ConsultationModel;
use modules\services\ConsultationService;
use modules\models\PatientModel;
use modules\models\Monitoring\MonitorModel;
use modules\models\Monitoring\MonitorPreferenceModel;
use modules\services\MonitoringService;
use modules\services\PatientContextService;
use PDO;

require_once __DIR__ . '/../../../assets/includes/database.php';

/**
 * Contrôleur du tableau de bord.
 */
class DashboardController
{
    private \PDO $pdo;
    private MonitorModel $monitorModel;
    private MonitorPreferenceModel $prefModel;
    private PatientModel $patientModel;
    private MonitoringService $monitoringService;
    private ConsultationModel $consultationModel;
    private PatientContextService $contextService;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getInstance();
        $this->monitorModel = new MonitorModel($this->pdo, 'patient_data');
        $this->prefModel = new MonitorPreferenceModel($this->pdo);
        $this->patientModel = new PatientModel($this->pdo);
        $this->monitoringService = new MonitoringService();
        $this->consultationModel = new ConsultationModel($this->pdo);
        $this->contextService = new PatientContextService($this->patientModel);
    }
    /**
     * Gère la requête POST pour mettre à jour les préférences.
     * Redirige ensuite vers la méthode GET.
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
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chart_pref_submit'])) {
                $userId = $_SESSION['user_id'] ?? null;
                $pId = $_POST['parameter_id'] ?? '';
                $cType = $_POST['chart_type'] ?? '';

                if ($userId && $pId && $cType) {
                    $this->prefModel->saveUserChartPreference((int) $userId, $pId, $cType);
                    // Redirect to avoid form resubmission
                    $currentUrl = $_SERVER['REQUEST_URI'];
                    header('Location: ' . $currentUrl);
                    exit();
                }
            }
        } catch (\Exception $e) {
            error_log("DashboardController::handlePostRequest Error: " . $e->getMessage());
        }
    }
    /**
     * Affiche la vue du tableau de bord.
     * 
     * Cette méthode orchestre la récupération de toutes les données nécessaires au Dashboard :
     * - Vérification de l'authentification.
     * - Gestion du contexte patient (via URL ou Cookie).
     * - Récupération des consultations (passées et futures).
     * - Récupération des données de monitoring (métriques temps réel).
     * - Récupération des types de graphiques disponibles pour les modales de configuration.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit();
        }

        // Recuperation ID utilisateur
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: /?page=login');
            exit();
        }

        // Gestion du contexte (Cookies / URL)
        $this->contextService->handleRequest();

        // Récupération de l'ID patient via le contexte
        $patientId = $this->contextService->getCurrentPatientId();

        // Récupération des chambres pour le sélecteur
        try {
            $rooms = $this->patientModel->getAllRoomsWithPatients();
        } catch (\Throwable $e) {
            $rooms = [];
        }

        // Récupération des données complètes du patient
        $patientData = null;
        if ($patientId) {
            $patientData = $this->patientModel->findById($patientId);
        }

        // Fallback si pas de patient trouvé
        if (!$patientData) {
            $patientData = [
                'first_name' => 'Patient',
                'last_name' => 'Inconnu',
                'birth_date' => null,
                'admission_cause' => 'Aucun patient sélectionné',
                'id_patient' => 0
            ];
        }

        $toutesConsultations = $this->consultationModel->getConsultationsByPatientId($patientId);

        $dateAujourdhui = new DateTime();
        $consultationsPassees = [];
        $consultationsFutures = [];

        foreach ($toutesConsultations as $consultation) {
            try {
                $dateConsultation = new \DateTime($consultation->getDate());
            } catch (\Exception $e) {
                continue;
            }

            if ($dateConsultation < $dateAujourdhui) {
                $consultationsPassees[] = $consultation;
            } else {
                $consultationsFutures[] = $consultation;
            }
        }

        // --- MONITORING DATA FETCH (from Int-Cust) ---
        $processedMetrics = [];
        if ($patientId) {
            try {
                // 1. Récupération des données brutes
                $metrics = $this->monitorModel->getLatestMetrics((int) $patientId);
                $rawHistory = $this->monitorModel->getRawHistory((int) $patientId);

                // 2. Récupération des préférences utilisateur
                $prefs = $this->prefModel->getUserPreferences((int) $userId);

                // 3. Traitement
                $processedMetrics = $this->monitoringService->processMetrics($metrics, $rawHistory, $prefs);
            } catch (\Exception $e) {
                error_log("[DashboardController] Monitoring Data Error: " . $e->getMessage());
            }
        }

        // Récupération des types de graphiques pour l'affichage dynamique
        $chartTypes = $this->monitorModel->getAllChartTypes();

        $view = new dashboardView($consultationsPassees, $consultationsFutures, $rooms, $processedMetrics, $patientData, $chartTypes);
        $view->show();
    }

    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
