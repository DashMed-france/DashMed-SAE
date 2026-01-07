<?php

declare(strict_types=1);

namespace modules\controllers\pages;

use DateTime;
use Database;
use modules\views\pages\DashboardView;
use modules\models\ConsultationModel;
use modules\models\PatientModel;
use modules\models\Monitoring\MonitorModel;
use modules\models\Monitoring\MonitorPreferenceModel;
use modules\services\MonitoringService;
use modules\services\PatientContextService;
use PDO;

/**
 * Contrôleur du tableau de bord (patient, consultations, monitoring).
 */
final class DashboardController
{
    private PDO $pdo;
    private MonitorModel $monitorModel;
    private MonitorPreferenceModel $prefModel;
    private PatientModel $patientModel;
    private MonitoringService $monitoringService;
    private ConsultationModel $consultationModel;
    private PatientContextService $contextService;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
        $this->monitorModel = new MonitorModel($this->pdo, 'patient_data');
        $this->prefModel = new MonitorPreferenceModel($this->pdo);
        $this->patientModel = new PatientModel($this->pdo);
        $this->monitoringService = new MonitoringService();
        $this->consultationModel = new ConsultationModel($this->pdo);
        $this->contextService = new PatientContextService($this->patientModel);
    }

    public function post(): void
    {
        $this->handleChartPreferenceUpdate();
        $this->get();
    }

    // Affichage principal du dashboard
    public function get(): void
    {
        $userId = $this->requireAuthenticatedUser();

        $this->contextService->handleRequest();
        $patientId = $this->contextService->getCurrentPatientId();

        $rooms = $this->loadRooms();
        $patientData = $this->loadPatientData($patientId);
        [$pastConsultations, $futureConsultations] = $this->loadConsultations($patientId);
        [$processedMetrics, $userLayout] = $this->loadMonitoringData($userId, $patientId);
        $chartTypes = $this->monitorModel->getAllChartTypes();

        $view = new DashboardView(
            $pastConsultations,
            $futureConsultations,
            $rooms,
            $processedMetrics,
            $patientData,
            $chartTypes,
            $userLayout
        );
        $view->show();
    }

    /**
     * Traite la mise à jour de préférence de graphique.
     */
    private function handleChartPreferenceUpdate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['chart_pref_submit'])) {
            return;
        }

        try {
            $userId = $_SESSION['user_id'] ?? null;
            $parameterId = (string) ($_POST['parameter_id'] ?? '');
            $chartType = (string) ($_POST['chart_type'] ?? '');

            if (is_numeric($userId) && $parameterId !== '' && $chartType !== '') {
                $this->prefModel->saveUserChartPreference((int) $userId, $parameterId, $chartType);
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit();
            }
        } catch (\Exception $e) {
            error_log('[DashboardController] handleChartPreferenceUpdate error: ' . $e->getMessage());
        }
    }

    /**
     * Vérifie l'authentification et retourne l'ID utilisateur.
     */
    private function requireAuthenticatedUser(): int
    {
        if (!isset($_SESSION['email'])) {
            header('Location: /?page=login');
            exit();
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: /?page=login');
            exit();
        }

        return (int) $userId;
    }

    /**
     * Charge la liste des chambres avec patients.
     *
     * @return array<int|string, mixed>
     */
    private function loadRooms(): array
    {
        try {
            /** @var array<int|string, mixed> */
            return $this->patientModel->getAllRoomsWithPatients();
        } catch (\Throwable $e) {
            error_log('[DashboardController] loadRooms error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Charge les données du patient ou retourne des valeurs par défaut.
     *
     * @return array<string, mixed>
     */
    private function loadPatientData(?int $patientId): array
    {
        if ($patientId !== null) {
            $data = $this->patientModel->findById($patientId);
            if (is_array($data)) {
                return $data;
            }
        }

        return [
            'first_name' => 'Patient',
            'last_name' => 'Inconnu',
            'birth_date' => null,
            'admission_cause' => 'Aucun patient sélectionné',
            'id_patient' => 0,
        ];
    }

    /**
     * Charge et trie les consultations passées et futures.
     *
     * @return array{0: list<object>, 1: list<object>}
     */
    private function loadConsultations(?int $patientId): array
    {
        if ($patientId === null) {
            return [[], []];
        }

        $allConsultations = $this->consultationModel->getConsultationsByPatientId($patientId);

        $today = new DateTime();
        $past = [];
        $future = [];

        foreach ($allConsultations as $consultation) {
            try {
                $consultationDate = new DateTime($consultation->getDate());
            } catch (\Exception $e) {
                continue;
            }

            if ($consultationDate < $today) {
                $past[] = $consultation;
            } else {
                $future[] = $consultation;
            }
        }

        return [$past, $future];
    }

    /**
     * Charge les données de monitoring et le layout utilisateur.
     *
     * @return array{0: array<int|string, mixed>, 1: array<int|string, mixed>}
     */
    private function loadMonitoringData(int $userId, ?int $patientId): array
    {
        /** @var array<int|string, mixed> */
        $processedMetrics = [];
        /** @var array<int|string, mixed> */
        $userLayout = [];

        if ($patientId === null) {
            return [$processedMetrics, $userLayout];
        }

        try {
            $metrics = $this->monitorModel->getLatestMetrics($patientId);
            $rawHistory = $this->monitorModel->getRawHistory($patientId);
            $prefs = $this->prefModel->getUserPreferences($userId);
            /** @var array<int|string, mixed> */
            $processedMetrics = $this->monitoringService->processMetrics($metrics, $rawHistory, $prefs);
            /** @var array<int|string, mixed> */
            $userLayout = $this->prefModel->getUserLayoutSimple($userId);
        } catch (\Exception $e) {
            error_log('[DashboardController] loadMonitoringData error: ' . $e->getMessage());
        }

        return [$processedMetrics, $userLayout];
    }
}
