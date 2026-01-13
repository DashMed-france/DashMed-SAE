<?php

declare(strict_types=1);

namespace modules\controllers\pages\Monitoring;

use Database;
use modules\views\pages\Monitoring\MonitoringView;
use modules\models\PatientModel;
use modules\models\Monitoring\MonitorModel;
use modules\models\Monitoring\MonitorPreferenceModel;
use modules\services\MonitoringService;

/**
 * Class MonitoringController | Contrôleur de Monitoring
 *
 * Manages the patient monitoring display.
 * Gère l'affichage du monitoring patient.
 *
 * @package DashMed\Modules\Controllers\Pages\Monitoring
 * @author DashMed Team
 * @license Proprietary
 */
class MonitoringController
{
    private MonitorModel $monitorModel;
    private MonitorPreferenceModel $prefModel;
    private PatientModel $patientModel;
    private MonitoringService $monitoringService;

    /**
     * Constructor | Constructeur
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
     * Handles POST requests: Update preferences.
     * Gère la requête POST pour mettre à jour les préférences.
     *
     * Redirects to GET after processing.
     * Redirige ensuite vers la méthode GET.
     *
     * @return void
     */
    public function post(): void
    {
        $this->handlePostRequest();
        $this->get();
    }

    /**
     * Main entry point for monitoring page (GET).
     * Point d'entrée principal pour la page de monitoring.
     *
     * Handles:
     * - User session verification.
     * - Room ID retrieval (GET/Cookie).
     * - Patient ID retrieval.
     * - Health metrics loading (latest and history).
     * - User preferences loading.
     * - Chart types loading.
     * - View instantiation.
     *
     * Cette méthode gère :
     * - La vérification de la session utilisateur.
     * - La récupération de l'identifiant de la chambre (via GET ou Cookie).
     * - La récupération de l'ID patient associé à la chambre.
     * - Le chargement des métriques de santé (dernières valeurs et historique).
     * - Le chargement des préférences utilisateur (types de graphiques, ordre).
     * - La récupération de la liste des types de graphiques disponibles.
     * - L'instanciation et l'affichage de la vue `MonitoringView`.
     *
     * Redirects to error page on failure.
     * En cas d'erreur critique, redirige vers une page d'erreur générique.
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
            $metrics = [];
            $rawHistory = [];

            if ($roomId) {
                $patientId = $this->patientModel->getPatientIdByRoom($roomId);
            }

            if ($patientId) {
                $metrics = $this->monitorModel->getLatestMetrics($patientId);
                $rawHistory = $this->monitorModel->getRawHistory($patientId);
            }

            $rawUserId = $_SESSION['user_id'] ?? 0;
            $prefs = $this->prefModel->getUserPreferences(is_numeric($rawUserId) ? (int) $rawUserId : 0);

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
     * Processes POST form data.
     * Traite les données soumises via le formulaire POST.
     *
     * @return void
     */
    private function handlePostRequest(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chart_pref_submit'])) {
                $userId = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])
                    ? (int) $_SESSION['user_id']
                    : null;
                $pId = isset($_POST['parameter_id']) && is_string($_POST['parameter_id']) ? $_POST['parameter_id'] : '';
                $cType = isset($_POST['chart_type']) && is_string($_POST['chart_type']) ? $_POST['chart_type'] : '';

                if ($userId !== null && $pId !== '' && $cType !== '') {
                    $this->prefModel->saveUserChartPreference($userId, $pId, $cType);

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
     * Retrieves room ID from GET or COOKIE.
     * Récupère l'ID de la chambre depuis GET ou COOKIE.
     *
     * @return int|null
     */
    private function getRoomId(): ?int
    {
        $rawRoom = $_GET['room'] ?? null;
        $rawCookie = $_COOKIE['room_id'] ?? null;
        if ($rawRoom !== null && is_numeric($rawRoom)) {
            return (int) $rawRoom;
        }
        if ($rawCookie !== null && is_numeric($rawCookie)) {
            return (int) $rawCookie;
        }
        return null;
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
