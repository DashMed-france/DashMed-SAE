<?php

namespace modules\controllers\pages;

use DateTime;
use modules\views\pages\dashboardView;
use modules\services\ConsultationService;
use modules\models\PatientModel;
use modules\models\Monitoring\MonitorModel;
use modules\models\Monitoring\MonitorPreferenceModel;
use modules\services\MonitoringService;
use PDO;

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

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getInstance();
        $this->monitorModel = new MonitorModel($this->pdo, 'patient_data');
        $this->prefModel = new MonitorPreferenceModel($this->pdo);
        $this->patientModel = new PatientModel($this->pdo);
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
     * Affiche la vue du tableau de bord si l'utilisateur est connecté.
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

        // Contient dans le cookie l'ID de la chambre
        if (isset($_GET['room']) && ctype_digit($_GET['room'])) {
            setcookie('room_id', $_GET['room'], time() + 60 * 60 * 24 * 30, '/');
            $_COOKIE['room_id'] = $_GET['room'];
        }


        $toutesConsultations = ConsultationService::getAllConsultations();

        $dateAujourdhui = new DateTime();
        $consultationsPassees = [];
        $consultationsFutures = [];

        foreach ($toutesConsultations as $consultation) {
            $dateConsultation = \DateTime::createFromFormat('Y-m-d', $consultation->getDate());

            if ($dateConsultation < $dateAujourdhui) {
                $consultationsPassees[] = $consultation;
            } else {
                $consultationsFutures[] = $consultation;
            }
        }

        $rooms = $this->getRooms();

        // --- MONITORING DATA FETCH ---
        $patientId = null;
        $currentRoomId = isset($_COOKIE['room_id']) ? (int) $_COOKIE['room_id'] : null;

        if ($currentRoomId) {
            $patientId = $this->patientModel->getPatientIdByRoom($currentRoomId);
        }

        $processedMetrics = [];
        if ($patientId) {
            // 1. Récupération des données brutes
            $metrics = $this->monitorModel->getLatestMetrics((int) $patientId);
            $rawHistory = $this->monitorModel->getRawHistory((int) $patientId);

            // 2. Récupération des préférences utilisateur
            $prefs = $this->prefModel->getUserPreferences((int) $userId);

            // 3. Traitement
            $processedMetrics = $this->monitoringService->processMetrics($metrics, $rawHistory, $prefs);
        }
        // -----------------------------

        $view = new dashboardView($consultationsPassees, $consultationsFutures, $rooms, $processedMetrics);
        $view->show();
    }

    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Returns all rooms using RoomModel.
     *
     * @return array of rooms.
     */
    public function getRooms(): array
    {
        $roomModel = new \modules\models\RoomModel($this->pdo);
        return $roomModel->getAllRooms();
    }
}
