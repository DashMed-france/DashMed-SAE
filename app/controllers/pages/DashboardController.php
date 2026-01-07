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
 * Controller for managing the dashboard page.
 *
 * This controller orchestrates the retrieval and display of all dashboard data
 * including patient information, consultations, and real-time monitoring metrics.
 */
class DashboardController
{
    /**
     * PDO database connection instance.
     *
     * @var PDO
     */
    private \PDO $pdo;

    /**
     * Model for managing monitoring data.
     *
     * @var MonitorModel
     */
    private MonitorModel $monitorModel;

    /**
     * Model for managing monitor preferences.
     *
     * @var MonitorPreferenceModel
     */
    private MonitorPreferenceModel $prefModel;

    /**
     * Model for managing patient data.
     *
     * @var PatientModel
     */
    private PatientModel $patientModel;

    /**
     * Service for processing monitoring data.
     *
     * @var MonitoringService
     */
    private MonitoringService $monitoringService;

    /**
     * Model for managing consultations.
     *
     * @var ConsultationModel
     */
    private ConsultationModel $consultationModel;

    /**
     * Service for managing patient context (selected patient).
     *
     * @var PatientContextService
     */
    private PatientContextService $contextService;

    /**
     * Constructor for DashboardController.
     *
     * Initializes all required models and services for dashboard functionality.
     *
     * @param PDO|null $pdo Optional PDO instance for dependency injection
     */
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
     * Handles POST requests to update preferences.
     *
     * Processes the POST request and then redirects to the GET method
     * to display the dashboard.
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
     * Handles chart preference updates submitted by the user. Saves the
     * preference and redirects to avoid form resubmission.
     *
     * @return void
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
     * Displays the dashboard view.
     *
     * This method orchestrates the retrieval of all data necessary for the dashboard:
     * - Verifies user authentication
     * - Manages patient context (via URL or Cookie)
     * - Retrieves consultations (past and future)
     * - Retrieves monitoring data (real-time metrics)
     * - Retrieves available chart types for configuration modals
     *
     * Redirects to login page if user is not authenticated.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit();
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: /?page=login');
            exit();
        }

        $this->contextService->handleRequest();

        $patientId = $this->contextService->getCurrentPatientId();

        try {
            $rooms = $this->patientModel->getAllRoomsWithPatients();
        } catch (\Throwable $e) {
            $rooms = [];
        }

        $patientData = null;
        if ($patientId) {
            $patientData = $this->patientModel->findById($patientId);
        }

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

        $processedMetrics = [];
        if ($patientId) {
            try {
                $metrics = $this->monitorModel->getLatestMetrics((int) $patientId);
                $rawHistory = $this->monitorModel->getRawHistory((int) $patientId);
                $prefs = $this->prefModel->getUserPreferences((int) $userId);
                $processedMetrics = $this->monitoringService->processMetrics($metrics, $rawHistory, $prefs);
            } catch (\Exception $e) {
                error_log("[DashboardController] Monitoring Data Error: " . $e->getMessage());
            }
        }

        $chartTypes = $this->monitorModel->getAllChartTypes();

        $view = new dashboardView($consultationsPassees, $consultationsFutures, $rooms, $processedMetrics, $patientData, $chartTypes);
        $view->show();
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