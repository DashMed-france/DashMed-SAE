<?php

declare(strict_types=1);

namespace modules\controllers\pages\Monitoring;

use Database;
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
            $metrics = $this->monitorModel->getLatestMetrics($patientId);
            $rawHistory = $this->monitorModel->getRawHistory($patientId);

            // 2. Récupération des préférences utilisateur
            $userIdInt = is_int($userId) ? $userId : (is_numeric($userId) ? (int) $userId : 0);
            $prefs = $this->prefModel->getUserPreferences($userIdInt);

            // 3. Traitement / Fusion des données
            $processedMetrics = $this->monitoringService->processMetrics($metrics, $rawHistory, $prefs);

            // 4. Affichage de la Vue (les alertes sont gérées par le système global)
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
                    $userIdInt = is_int($userId) ? $userId : (is_numeric($userId) ? (int) $userId : 0);
                    $pIdStr = is_string($pId) ? $pId : '';
                    $cTypeStr = is_string($cType) ? $cType : '';
                    $this->prefModel->saveUserChartPreference($userIdInt, $pIdStr, $cTypeStr);
                    // Redirect to avoid form resubmission
                    $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
                    if (is_string($currentUrl)) {
                        header('Location: ' . $currentUrl);
                    }
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
        if (isset($_GET['room']) && is_numeric($_GET['room'])) {
            return (int) $_GET['room'];
        }
        if (isset($_COOKIE['room_id']) && is_numeric($_COOKIE['room_id'])) {
            return (int) $_COOKIE['room_id'];
        }
        return null;
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