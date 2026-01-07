<?php

namespace modules\controllers\pages\Monitoring;

use Database;
use DateTime;
use modules\views\pages\Monitoring\MonitoringView;
use modules\models\PatientModel;
use modules\models\Monitoring\MonitorModel;
use modules\models\Monitoring\MonitorPreferenceModel;
use modules\services\MonitoringService;

/**
 * Controller for patient monitoring and health metrics visualization.
 *
 * Manages the monitoring interface, handles user preferences for chart types,
 * retrieves patient health data, and coordinates with services to process
 * and display real-time monitoring information.
 *
 * @package modules\controllers\pages\Monitoring
 */
class MonitoringController
{
    /**
     * Model for patient monitoring data operations.
     *
     * @var MonitorModel
     */
    private MonitorModel $monitorModel;

    /**
     * Model for user chart preference operations.
     *
     * @var MonitorPreferenceModel
     */
    private MonitorPreferenceModel $prefModel;

    /**
     * Model for patient data operations.
     *
     * @var PatientModel
     */
    private PatientModel $patientModel;

    /**
     * Service for processing monitoring metrics.
     *
     * @var MonitoringService
     */
    private MonitoringService $monitoringService;

    /**
     * Initializes the controller with required models and services.
     * Starts the session if not already active.
     */
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $db = Database::getInstance();
        $this->monitorModel = new MonitorModel($db, 'patient_data');
        $this->prefModel = new MonitorPreferenceModel($db);
        $this->patientModel = new PatientModel($db);
        $this->monitoringService = new MonitoringService();
    }

    /**
     * Handles POST requests to update user preferences.
     * Redirects to the GET method after processing.
     *
     * @return void
     */
    public function post(): void
    {
        $this->handlePostRequest();
        $this->get();
    }

    /**
     * Main entry point for the monitoring page.
     *
     * This method handles:
     * - User session verification.
     * - Room ID retrieval (via GET or Cookie).
     * - Patient ID lookup associated with the room.
     * - Loading health metrics (latest values and history).
     * - Loading user preferences (chart types, ordering).
     * - Retrieving available chart types list.
     * - Instantiating and displaying the MonitoringView.
     *
     * On critical error, redirects to a generic error page.
     *
     * @return void
     */
    public function get(): void
    {
        try {
            if (!$this->isUserLoggedIn()) {
                header('Location: /?page=login');
                exit();
            }

            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                header('Location: /?page=login');
                exit();
            }

            $roomId = $this->getRoomId();
            $patientId = null;

            if ($roomId) {
                $patientId = $this->patientModel->getPatientIdByRoom($roomId);
            }

            if (!$patientId) {
                header('Location: /?page=dashboard');
                exit();
            }

            $metrics = $this->monitorModel->getLatestMetrics((int) $patientId);
            $rawHistory = $this->monitorModel->getRawHistory((int) $patientId);

            $prefs = $this->prefModel->getUserPreferences((int) $userId);

            $processedMetrics = $this->monitoringService->processMetrics($metrics, $rawHistory, $prefs, true);

            $chartTypes = $this->monitorModel->getAllChartTypes();

            $view = new MonitoringView($processedMetrics, $chartTypes);
            $view->show();
        } catch (\Exception $e) {
            error_log("MonitoringController::get Error: " . $e->getMessage());
            header('Location: /?page=error&msg=monitoring_error');
            exit();
        }
    }

    /**
     * Processes data submitted via the POST form.
     *
     * Handles chart preference updates for individual parameters.
     * Redirects to avoid form resubmission after successful update.
     *
     * Expected POST parameters:
     * - chart_pref_submit: Form submission flag.
     * - parameter_id: The metric parameter ID.
     * - chart_type: The selected chart type.
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
                    // Redirect to avoid form resubmission
                    $currentUrl = $_SERVER['REQUEST_URI'];
                    header('Location: ' . $currentUrl);
                    exit();
                }
            }
        } catch (\Exception $e) {
            error_log("MonitoringController::handlePostRequest Error: " . $e->getMessage());
        }
    }

    /**
     * Retrieves the room ID from GET parameter or cookie.
     *
     * @return int|null Room ID if found, null otherwise.
     */
    private function getRoomId(): ?int
    {
        return isset(
            $_GET['room']
        ) ? (int) $_GET['room'] : (isset($_COOKIE['room_id']) ? (int) $_COOKIE['room_id'] : null
        );
    }

    /**
     * Checks if a user is currently logged in.
     *
     * @return bool True if user is authenticated, false otherwise.
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}