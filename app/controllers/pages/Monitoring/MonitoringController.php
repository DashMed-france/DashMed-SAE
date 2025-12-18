<?php
namespace modules\controllers\pages\Monitoring;

use Database;
use DateTime;
use modules\views\pages\Monitoring\MonitoringView;
use modules\models\PatientModel;
use modules\models\Monitoring\MonitorModel;
use modules\models\Monitoring\MonitorPreferenceModel;
use modules\services\MonitoringService;

require_once __DIR__ . '/../../../../assets/includes/database.php';

class MonitoringController
{
    private MonitorModel $monitorModel;
    private MonitorPreferenceModel $prefModel;
    private PatientModel $patientModel;
    private MonitoringService $monitoringService;

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
     * Gère la requête POST pour mettre à jour les préférences.
     * Redirige ensuite vers la méthode GET.
     */
    public function post(): void
    {
        $this->handlePostRequest();
        $this->get();
    }

    /**
     * Affiche la page de monitoring.
     * Gère l'authentification, la récupération des données et le rendu de la vue.
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
                // Ne devrait pas arriver si connecté
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

            // 1. Récupération des données brutes
            $metrics = $this->monitorModel->getLatestMetrics((int) $patientId);
            $rawHistory = $this->monitorModel->getRawHistory((int) $patientId);

            // 2. Récupération des préférences utilisateur
            $prefs = $this->prefModel->getUserPreferences((int) $userId);

            // 3. Traitement / Fusion des données
            $processedMetrics = $this->monitoringService->processMetrics($metrics, $rawHistory, $prefs);

            // 4. Affichage de la Vue
            $view = new MonitoringView($processedMetrics);
            $view->show();
        } catch (\Exception $e) {
            error_log("MonitoringController::get Error: " . $e->getMessage());
            // Gestionnaire d'erreur global pour cette page
            // Redirection vers une page d'erreur générique ou log de l'erreur
            header('Location: /?page=error&msg=monitoring_error');
            exit();
        }
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
            error_log("MonitoringController::handlePostRequest Error: " . $e->getMessage());
        }
    }

    /**
     * Récupère l'ID de la chambre depuis GET ou COOKIE.
     *
     * @return int|null
     */
    private function getRoomId(): ?int
    {
        return isset($_GET['room']) ? (int) $_GET['room'] : (isset($_COOKIE['room_id']) ? (int) $_COOKIE['room_id'] : null);
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