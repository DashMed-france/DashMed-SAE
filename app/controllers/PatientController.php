<?php

declare(strict_types=1);

namespace modules\controllers;

use DateTime;
use assets\includes\Database;
use modules\models\repositories\ConsultationRepository;
use modules\models\repositories\PatientRepository;
use modules\models\repositories\UserRepository;
use modules\models\entities\Consultation;
use modules\models\repositories\MonitorRepository;
use modules\models\repositories\MonitorPreferenceRepository;
use modules\services\MonitoringService;
use modules\services\PatientContextService;
use modules\views\patient\DashboardView;
use modules\views\patient\MedicalprocedureView;
use modules\views\patient\PatientrecordView;
use modules\views\patient\MonitoringView;
use PDO;

/**
 * Class PatientController
 *
 * Centralizes all patient-centric actions.
 *
 * Replaces: DashboardController, MonitoringController,
 *           PatientrecordController, MedicalprocedureController.
 *
 * @package DashMed\Modules\Controllers
 * @author DashMed Team
 * @license Proprietary
 */
class PatientController
{
    /** @var PDO Database connection */
    private PDO $pdo;

    /** @var PatientRepository Patient repository */
    private PatientRepository $patientRepo;

    /** @var ConsultationRepository Consultation repository */
    private ConsultationRepository $consultationRepo;

    /** @var UserRepository User repository */
    private UserRepository $userRepo;

    /** @var MonitorRepository Monitor model */
    private MonitorRepository $monitorModel;

    /** @var MonitorPreferenceRepository Preferences model */
    private MonitorPreferenceRepository $prefModel;

    /** @var MonitoringService Monitoring service */
    private MonitoringService $monitoringService;

    /** @var PatientContextService Context service */
    private PatientContextService $contextService;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Database connection (optional)
     */
    public function __construct(?PDO $pdo = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->pdo = $pdo ?? Database::getInstance();
        $this->patientRepo = new PatientRepository($this->pdo);
        $this->consultationRepo = new ConsultationRepository($this->pdo);
        $this->userRepo = new UserRepository($this->pdo);
        $this->monitorModel = new MonitorRepository($this->pdo, 'patient_data');
        $this->prefModel = new MonitorPreferenceRepository($this->pdo);
        $this->monitoringService = new MonitoringService();
        $this->contextService = new PatientContextService($this->patientRepo);
    }

    /**
     * Dashboard entry point (GET & POST).
     *
     * @return void
     */
    public function dashboard(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleChartPreferenceUpdate();
        }
        $this->dashboardGet();
    }

    /**
     * Displays the main dashboard.
     *
     * @return void
     */
    private function dashboardGet(): void
    {
        $userId = $this->requireAuth();

        $this->contextService->handleRequest();
        $patientId = $this->contextService->getCurrentPatientId();

        $rooms = $this->loadRooms();
        $patientData = $this->loadPatientData($patientId);
        [$pastConsultations, $futureConsultations] = $this->loadConsultations($patientId);
        [$processedMetrics, $userLayout] = $this->loadMonitoringData($userId, $patientId);
        $chartTypes = $this->monitorModel->getAllChartTypes();

        $pastCons = array_values($pastConsultations);
        $futCons = array_values($futureConsultations);

        $view = new DashboardView(
            $pastCons,
            $futCons,
            $rooms,
            $processedMetrics,
            $patientData,
            $chartTypes,
            $userLayout
        );
        $view->show();
    }

    /**
     * Monitoring entry point (GET & POST).
     *
     * @return void
     */
    public function monitoring(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleChartPreferenceUpdate();
        }
        $this->monitoringGet();
    }

    /**
     * Displays the monitoring view.
     *
     * @return void
     */
    private function monitoringGet(): void
    {
        try {
            $this->requireAuth();

            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId && empty($_GET["debug"])) {
                header('Location: /?page=login');
                exit();
            }

            $roomId = $this->getRoomId();
            $patientId = null;
            $metrics = [];
            $rawHistory = [];

            if ($roomId) {
                $patientId = $this->patientRepo->getPatientIdByRoom($roomId);
            }

            if ($patientId) {
                $metrics = $this->monitorModel->getLatestMetrics($patientId);
                $rawHistory = $this->monitorModel->getRawHistory($patientId);
            }

            $rawUserId = $_SESSION['user_id'] ?? 0;
            $prefs = $this->prefModel->getUserPreferences(is_numeric($rawUserId) ? (int) $rawUserId : 0);

            $processedMetrics = $this->monitoringService->processMetrics($metrics, $rawHistory, $prefs, true);

            $chartTypes = $this->monitorModel->getAllChartTypes();

            $view = new MonitoringView($processedMetrics, $chartTypes, $patientId);
            $view->show();
        } catch (\Exception $e) {
            error_log("PatientController::monitoring Error: " . $e->getMessage());
            header('Location: /?page=error&msg=monitoring_error');
            exit();
        }
    }


    /**
     * Patient record entry point (GET & POST).
     *
     * @return void
     */
    public function record(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->recordPost();
        } else {
            $this->recordGet();
        }
    }

    /**
     * Displays the patient record view.
     *
     * @return void
     */
    private function recordGet(): void
    {
        $this->requireAuth();

        $this->contextService->handleRequest();
        $idPatient = $this->contextService->getCurrentPatientId();

        try {
            $patientData = $this->patientRepo->findById($idPatient);

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

            $doctors = $this->patientRepo->getDoctors($idPatient);

            $allConsultations = $this->consultationRepo->getConsultationsByPatientId($idPatient);
            $pastConsultations = [];
            $futureConsultations = [];
            $today = new \DateTime();

            foreach ($allConsultations as $consultation) {
                $dStr = $consultation->getDate();
                $dObj = \DateTime::createFromFormat('d/m/Y', $dStr);
                if (!$dObj) {
                    $dObj = \DateTime::createFromFormat('Y-m-d', $dStr);
                }
                if ($dObj && $dObj < $today) {
                    $pastConsultations[] = $consultation;
                } else {
                    $futureConsultations[] = $consultation;
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

            $view = new PatientrecordView(
                $pastConsultations,
                $futureConsultations,
                $patientData,
                $safeDoctors,
                $msg
            );
            $view->show();
        } catch (\Throwable $e) {
            error_log("[PatientController::record] Critical Error: " . $e->getMessage());
            $view = new PatientrecordView(
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
     * Processes patient record update (POST).
     *
     * @return void
     */
    private function recordPost(): void
    {
        $this->requireAuth();

        $sessionCsrf = isset($_SESSION['csrf_patient']) && is_string($_SESSION['csrf_patient'])
            ? $_SESSION['csrf_patient']
            : '';
        $postCsrf = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
        if ($sessionCsrf === '' || $postCsrf === '' || !hash_equals($sessionCsrf, $postCsrf)) {
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Session expirée. Veuillez rafraîchir la page.'];
            header('Location: /?page=dossierpatient');
            exit;
        }

        $this->contextService->handleRequest();
        $idPatient = $this->contextService->getCurrentPatientId();

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

            $success = $this->patientRepo->update($idPatient, $updateData);

            if ($success) {
                $_SESSION['patient_msg'] =
                    ['type' => 'success', 'text' => 'Dossier mis à jour avec succès.'];
            } else {
                $_SESSION['patient_msg'] =
                    ['type' => 'error', 'text' => 'Aucune modification détectée ou erreur de sauvegarde.'];
            }
        } catch (\Exception $e) {
            error_log("[PatientController::recordPost] Erreur UPDATE: " . $e->getMessage());
            $_SESSION['patient_msg'] =
                ['type' => 'error', 'text' => 'Erreur technique lors de la sauvegarde.'];
        }

        header('Location: /?page=dossierpatient');
        exit;
    }

    /**
     * Consultations entry point (GET & POST).
     *
     * @return void
     */
    public function consultations(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->consultationsPost();
        }
        $this->consultationsGet();
    }

    /**
     * Displays consultations list.
     *
     * @return void
     */
    private function consultationsGet(): void
    {
        $this->requireAuth();

        $this->contextService->handleRequest();
        $patientId = $this->contextService->getCurrentPatientId();

        $consultations = [];
        if ($patientId) {
            $consultations = $this->consultationRepo->getConsultationsByPatientId($patientId);
        }

        usort($consultations, function ($a, $b) {
            $dateA = \DateTime::createFromFormat('Y-m-d', $a->getDate());
            $dateB = \DateTime::createFromFormat('Y-m-d', $b->getDate());
            if (!$dateA) {
                return 1;
            }
            if (!$dateB) {
                return -1;
            }
            return $dateB <=> $dateA;
        });

        $doctors = $this->userRepo->getAllDoctors();

        $currentUserId = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])
            ? (int) $_SESSION['user_id']
            : 0;
        $isAdmin = $this->isAdminUser($currentUserId);

        $view = new MedicalprocedureView($consultations, $doctors, $isAdmin, $currentUserId, $patientId);
        $view->show();
    }

    /**
     * Processes consultation POST actions (add/update/delete).
     *
     * @return void
     */
    private function consultationsPost(): void
    {
        if (!isset($_POST['action'])) {
            return;
        }

        $this->requireAuth();

        $this->contextService->handleRequest();
        $patientId = $this->contextService->getCurrentPatientId();
        $currentUserId = $_SESSION['user_id'] ?? null;

        if (!$patientId || !$currentUserId) {
            return;
        }

        $rawCurrentUserId = $_SESSION['user_id'] ?? 0;
        $isAdmin = $this->isAdminUser(is_numeric($rawCurrentUserId) ? (int) $rawCurrentUserId : 0);

        $rawAction = $_POST['action'];
        $action = is_string($rawAction) ? $rawAction : '';
        $currentUserInt = is_numeric($rawCurrentUserId) ? (int) $rawCurrentUserId : 0;

        if ($action === 'add_consultation') {
            $this->handleAddConsultation($patientId, $currentUserInt, $isAdmin);
        } elseif ($action === 'update_consultation') {
            $this->handleUpdateConsultation($patientId, $currentUserInt, $isAdmin);
        } elseif ($action === 'delete_consultation') {
            $this->handleDeleteConsultation($patientId);
        }
    }

    /**
     * Adds a consultation.
     */
    private function handleAddConsultation(int $patientId, int $currentUserId, bool $isAdmin): void
    {
        $rawDoctorId = $_POST['doctor_id'] ?? null;
        $doctorId = ($isAdmin && $rawDoctorId !== null && is_numeric($rawDoctorId) && (int) $rawDoctorId > 0)
            ? (int) $rawDoctorId
            : $currentUserId;

        $rawTitle = $_POST['consultation_title'] ?? '';
        $title = trim(is_string($rawTitle) ? $rawTitle : '');
        $rawDate = $_POST['consultation_date'] ?? '';
        $date = is_string($rawDate) ? $rawDate : '';
        $rawTime = $_POST['consultation_time'] ?? '';
        $time = is_string($rawTime) ? $rawTime : '';
        $rawType = $_POST['consultation_type'] ?? 'Autre';
        $type = is_string($rawType) ? $rawType : 'Autre';
        $rawNote = $_POST['consultation_note'] ?? '';
        $note = trim(is_string($rawNote) ? $rawNote : '');

        if ($title !== '' && $date !== '' && $time !== '') {
            $fullDate = $date . ' ' . $time . ':00';
            $success = $this->consultationRepo->createConsultation(
                $patientId,
                $doctorId,
                $fullDate,
                $type,
                $note,
                $title
            );
            if ($success) {
                header("Location: ?page=medicalprocedure&id_patient=" . $patientId);
                exit;
            }
        }
    }

    /**
     * Updates a consultation.
     */
    private function handleUpdateConsultation(int $patientId, int $currentUserId, bool $isAdmin): void
    {
        $rawConsId = $_POST['id_consultation'] ?? 0;
        $consultationId = is_numeric($rawConsId) ? (int) $rawConsId : 0;

        if ($consultationId) {
            $consultation = $this->consultationRepo->getConsultationById($consultationId);
            if (!$consultation) {
                return;
            }
            if (!$isAdmin && $consultation->getDoctorId() !== $currentUserId) {
                error_log(
                    "Access denied: User $currentUserId tried to modify consultation $consultationId"
                );
                return;
            }
        }

        $rawDoctorId2 = $_POST['doctor_id'] ?? null;
        $doctorId = ($isAdmin && $rawDoctorId2 !== null && is_numeric($rawDoctorId2) && (int) $rawDoctorId2 > 0)
            ? (int) $rawDoctorId2
            : $currentUserId;

        $rawTitle2 = $_POST['consultation_title'] ?? '';
        $title = trim(is_string($rawTitle2) ? $rawTitle2 : '');
        $rawDate2 = $_POST['consultation_date'] ?? '';
        $date = is_string($rawDate2) ? $rawDate2 : '';
        $rawTime2 = $_POST['consultation_time'] ?? '';
        $time = is_string($rawTime2) ? $rawTime2 : '';
        $rawType2 = $_POST['consultation_type'] ?? 'Autre';
        $type = is_string($rawType2) ? $rawType2 : 'Autre';
        $rawNote2 = $_POST['consultation_note'] ?? '';
        $note = trim(is_string($rawNote2) ? $rawNote2 : '');

        if ($consultationId && $title !== '' && $date !== '' && $time !== '') {
            $fullDate = $date . ' ' . $time . ':00';
            $success = $this->consultationRepo->updateConsultation(
                $consultationId,
                $doctorId,
                $fullDate,
                $type,
                $note,
                $title
            );
            if ($success) {
                header("Location: ?page=medicalprocedure&id_patient=" . $patientId);
                exit;
            }
        }
    }

    /**
     * Deletes a consultation.
     */
    private function handleDeleteConsultation(int $patientId): void
    {
        $rawDelConsId = $_POST['id_consultation'] ?? 0;
        $consultationId = is_numeric($rawDelConsId) ? (int) $rawDelConsId : 0;
        $rawDelUserId = $_SESSION['user_id'] ?? 0;
        $currentUserId = is_numeric($rawDelUserId) ? (int) $rawDelUserId : 0;
        $isAdmin = $this->isAdminUser($currentUserId);

        if ($consultationId) {
            $consultation = $this->consultationRepo->getConsultationById($consultationId);
            if (!$consultation) {
                return;
            }
            if (!$isAdmin && $consultation->getDoctorId() !== $currentUserId) {
                error_log(
                    "Access denied: User $currentUserId tried to delete consultation $consultationId"
                );
                return;
            }
            $success = $this->consultationRepo->deleteConsultation($consultationId);
            if ($success) {
                header("Location: ?page=medicalprocedure&id_patient=" . $patientId);
                exit;
            }
        }
    }

    /**
     * Requires authentication, redirects to login if not.
     *
     * @return int User ID
     */
    private function requireAuth(): int
    {
        if (!isset($_SESSION['email'])) {
            header('Location: /?page=login');
            exit();
        }
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId && empty($_GET["debug"])) {
            header('Location: /?page=login');
            exit();
        }
        return (int) $userId;
    }

    /**
     * Checks if user is admin.
     *
     * @param int $userId
     * @return bool
     */
    private function isAdminUser(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $user = $this->userRepo->getById($userId);
        return $user !== null && $user->isAdmin();
    }

    /**
     * Handles chart preference update via POST.
     *
     * @return void
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
            $isModal = isset($_POST['is_modal_pref']) && $_POST['is_modal_pref'] === '1';
            $prefType = (string) ($_POST['preference_type'] ?? ($isModal ? 'modal_chart' : 'chart'));

            if (is_numeric($userId) && $parameterId !== '' && $chartType !== '') {
                $this->prefModel->saveUserChartPreference((int) $userId, $parameterId, $chartType, $prefType);
                
                if ($isModal || $prefType === 'duration') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit();
                } else {
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit();
                }
            }
        } catch (\Exception $e) {
            error_log('[PatientController] handleChartPreferenceUpdate error: ' . $e->getMessage());
        }
    }


    /**
     * Retrieves historical data for a specific medical parameter.
     * 
     * Uses query parameters to determine the scope and applies Largest Triangle Three Buckets (LTTB)
     * downsampling if the dataset exceeds 5000 points to optimize client-side rendering performance.
     * Unbuffered streaming is used for large requests to maintain a low memory footprint.
     * Outputs JSON-encoded history data.
     *
     * @return void
     */
    public function apiHistory(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId && !isset($_GET["debug"]) && !isset($_GET["debug_stream"])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Non autorisé']);
                return;
            }

            // Release session lock to allow concurrent requests
            session_write_close();

            $roomId = $this->getRoomId();
            $patientId = null;

            if ($roomId) {
                $patientId = $this->patientRepo->getPatientIdByRoom($roomId);
            }
            if (!$patientId) {
                $this->contextService->handleRequest();
                $patientId = $this->contextService->getCurrentPatientId();
            }

            if (!$patientId) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Patient introuvable']);
                return;
            }

            $rawParameterId = $_GET['param'] ?? '';
            $parameterId = is_string($rawParameterId) ? $rawParameterId : '';
            $rawTargetDate = $_GET['date'] ?? null;
            $targetDate = is_string($rawTargetDate) ? $rawTargetDate : null;

            if ($parameterId === '') {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Paramètre manquant']);
                return;
            }

            $isRaw = isset($_GET['raw']) && $_GET['raw'] === '1';
            $isCsv = isset($_GET['format']) && $_GET['format'] === 'csv';

            /**
             * TOTAL VISIBILITY MODE (?raw=1)
             * Uses Streaming to push millions of rows directly to the browser 
             * with ZERO memory overhead on the server.
             */
            if ($isRaw) {
                if ($isCsv) {
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="history_' . $parameterId . '.csv"');
                    $out = fopen('php://output', 'w');
                    fputcsv($out, ['timestamp', 'value', 'alert_flag']);
                    
                    $stream = $this->monitorModel->streamRawHistoryByParameter($patientId, $parameterId, $targetDate, 0);
                    foreach ($stream as $row) {
                        fputcsv($out, [$row['timestamp'], $row['value'], $row['alert_flag']]);
                    }
                    fclose($out);
                } else {
                    header('Content-Type: application/json');
                    echo '[';
                    $stream = $this->monitorModel->streamRawHistoryByParameter($patientId, $parameterId, $targetDate, 0);
                    $first = true;
                    foreach ($stream as $row) {
                        if (!$first) echo ',';
                        echo json_encode([
                            'time_iso' => date('c', (int)strtotime($row['timestamp'] . ' UTC')),
                            'value' => (string)round((float)$row['value'], 2),
                            'flag' => (string)$row['alert_flag']
                        ]);
                        $first = false;
                    }
                    echo ']';
                }
                return;
            }

            // --- PERFORMANCE MODE (CHARTING) ---
            header('Content-Type: application/json');
            
            // Server-side cache (30s)
            $cacheKey = md5("history_v2_{$patientId}_{$parameterId}_{$targetDate}");
            $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dashmed_cache' . DIRECTORY_SEPARATOR . $cacheKey . '.json';
            
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 30)) {
                echo file_get_contents($cacheFile);
                return;
            }

            /**
             * INTELLIGENT DOWNSAMPLING (LTTB)
             * If the dataset is large (> 5000 points), we downsample it on the server
             * to 5000 points. This preserves ALL peaks/valleys visually while 
             * keeping the JSON payload small and the ECharts rendering fast.
             */
            $count = $this->monitorModel->countRawHistoryByParameter($patientId, $parameterId, $targetDate);
            $threshold = 5000;

            if ($count > $threshold) {
                $monitorModel = $this->monitorModel; // Local ref for closure
                $stream = $monitorModel->streamRawHistoryByParameter($patientId, $parameterId, $targetDate, 0);
                $downsampling = new \modules\services\DownsamplingService();
                
                // Optimized Generator for memory-efficient processing
                $formattedStream = function() use ($stream) {
                    foreach ($stream as $row) {
                        $rawTs = (strpos($row['timestamp'], '+') === false && strpos($row['timestamp'], 'Z') === false) 
                            ? $row['timestamp'] . ' UTC' : $row['timestamp'];
                        yield [
                            'time_iso' => date('c', (int)strtotime($rawTs)),
                            'value' => (string)round((float)$row['value'], 2),
                            'flag' => (string)$row['alert_flag']
                        ];
                    }
                };

                // Use the streaming version of LTTB (O(k) memory)
                $generator = $formattedStream();
                $formatted = $downsampling->downsampleLTTBStream(new \IteratorIterator($generator), $count, $threshold);
            } else {
                $history = $this->monitorModel->getRawHistoryByParameter($patientId, $parameterId, $targetDate, 0);
                $formatted = [];
                foreach ($history as $hItem) {
                    $ts = $hItem['timestamp'];
                    $rawTs = (strpos($ts, '+') === false && strpos($ts, 'Z') === false) ? $ts . ' UTC' : $ts;
                    $formatted[] = [
                        'time_iso' => date('c', (int)strtotime($rawTs)),
                        'value' => $hItem['value'] !== null ? (string)round((float)$hItem['value'], 2) : '',
                        'flag' => (string)$hItem['alert_flag']
                    ];
                }
            }

            $jsonResult = json_encode($formatted);
            
            // Ensure cache directory exists
            if (!is_dir(dirname($cacheFile))) mkdir(dirname($cacheFile), 0777, true);
            file_put_contents($cacheFile, $jsonResult);
            
            echo $jsonResult;

        } catch (\Exception $e) {
            error_log('[PatientController] apiHistory error: ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur interne']);
        }
    }

    /**
     * Retrieves the latest real-time metrics for the current patient.
     * 
     * Identifies the patient context, fetches the most recent metric values,
     * processes them through the MonitoringService to apply user preferences,
     * and rigorously formats timestamps into UTC ISO-8601 for frontend synchronization.
     * Outputs a JSON array of processed metrics.
     *
     * @return void
     */
    public function apiLiveMetrics(): void
    {
        header('Content-Type: application/json');

        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId && empty($_GET["debug"])) {
                echo json_encode(['error' => 'Non autorisé']);
                return;
            }

            // Release session lock to allow concurrent requests (prevents UI freezing)
            session_write_close();

            $roomId = $this->getRoomId();
            $patientId = null;

            if ($roomId) {
                $patientId = $this->patientRepo->getPatientIdByRoom($roomId);
            }
            if (!$patientId) {
                $this->contextService->handleRequest();
                $patientId = $this->contextService->getCurrentPatientId();
            }

            if (!$patientId) {
                echo json_encode(['error' => 'Patient introuvable']);
                return;
            }

            $metrics = $this->monitorModel->getLatestMetrics($patientId);
            
            $rawUserId = $_SESSION['user_id'] ?? 0;
            $prefs = $this->prefModel->getUserPreferences(is_numeric($rawUserId) ? (int) $rawUserId : 0);
            
            // Only need a lightweight history or just empty if we only care about the latest value
            $rawHistory = $this->monitorModel->getRawHistory($patientId, 1);
            
            $processedMetrics = $this->monitoringService->processMetrics($metrics, $rawHistory, $prefs, true);
            $formatted = [];
            
            foreach ($processedMetrics as $metric) {
                if ($metric instanceof \modules\models\entities\Indicator) {
                    $viewData = $metric->getViewData();
                    $historyHtmlData = $viewData['history_html_data'] ?? [];
                    $latestTimeIso = '';
                    if (!empty($historyHtmlData)) {
                        $latestTimeIso = $historyHtmlData[0]['time_iso'] ?? '';
                    }

                    $timeRaw = $metric->getTimestamp();
                    $rawTs = (is_string($timeRaw) && strpos($timeRaw, '+') === false && strpos($timeRaw, 'Z') === false) ? $timeRaw . ' UTC' : $timeRaw;
                    
                    $formatted[] = [
                        'parameter_id' => $metric->getId(),
                        'slug' => $viewData['slug'] ?? 'param',
                        'value' => $viewData['value'] ?? '',
                        'unit' => $viewData['unit'] ?? '',
                        'state_class' => $viewData['card_class'] ?? '',
                        'is_crit_flag' => (bool)($viewData['is_crit_flag'] ?? false),
                        'time_iso' => $timeRaw ? date('c', (int) strtotime($rawTs)) : ($latestTimeIso),
                        'chart_type' => $viewData['chart_type'] ?? 'line',
                        'display_name' => $viewData['display_name'] ?? ''
                    ];
                }
            }

            echo json_encode($formatted);
        } catch (\Exception $e) {
            error_log('[PatientController] apiLiveMetrics error: ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur interne']);
        }
    }

    /**
     * Retrieves room ID from GET or COOKIE.
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
     * Loads list of rooms with patients.
     *
     * @return array<int, array{room_id: int|string, first_name?: string}>
     */
    private function loadRooms(): array
    {
        try {
            /** @var array<int, array{room_id: int|string, first_name?: string}> */
            return $this->patientRepo->getAllRoomsWithPatients();
        } catch (\Throwable $e) {
            error_log('[PatientController] loadRooms error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Loads patient data.
     *
     * @param int|null $patientId
     * @return array<string, mixed>
     */
    private function loadPatientData(?int $patientId): array
    {
        if ($patientId !== null) {
            $data = $this->patientRepo->findById($patientId);
            if (is_array($data)) {
                return $data;
            }
        }
        return [
            'first_name' => 'Patient',
            'last_name' => 'Inconnu',
            'birth_date' => null,
            'admission_cause' => 'Aucun patient sélectionné | No patient selected',
            'id_patient' => 0,
        ];
    }

    /**
     * Loads and sorts consultations.
     *
     * @param int|null $patientId
     * @return array{0: array<int, mixed>, 1: array<int, mixed>}
     */
    private function loadConsultations(?int $patientId): array
    {
        if ($patientId === null) {
            return [[], []];
        }
        $allConsultations = $this->consultationRepo->getConsultationsByPatientId($patientId);
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
     * Loads monitoring data and user layout.
     *
     * @param int $userId
     * @param int|null $patientId
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>}
     */
    private function loadMonitoringData(int $userId, ?int $patientId): array
    {
        /** @var array<int, array<string, mixed>> $processedMetrics */
        $processedMetrics = [];
        /** @var array<string, mixed> $userLayout */
        $userLayout = [];

        if ($patientId === null) {
            return [$processedMetrics, $userLayout];
        }

        try {
            $metrics = $this->monitorModel->getLatestMetrics($patientId);
            
            /**
             * PERFORMANCE OPTIMIZATION:
             * Instead of loading the entire history table for all parameters (O(N)),
             * we fetch only the last 1000 points per parameter (O(Params * 1000)).
             * This prevents memory exhaustion on the initial page load while 
             * keeping sparklines perfectly accurate.
             */
            $rawHistory = $this->monitorModel->getLatestHistoryForAllParameters($patientId, 1000);
            
            $prefs = $this->prefModel->getUserPreferences($userId);
            /** @var array<int, array<string, mixed>> */
            $processedMetrics = $this->monitoringService->processMetrics($metrics, $rawHistory, $prefs);
            /** @var array<string, mixed> */
            $userLayout = (array) $this->prefModel->getUserLayoutSimple($userId);

            if (!empty($userLayout)) {
                $allHidden = true;
                foreach ($userLayout as $item) {
                    if (is_array($item) && empty($item['is_hidden'])) {
                        $allHidden = false;
                        break;
                    }
                }
                if ($allHidden) {
                    $userLayout = [];
                }
            }
        } catch (\Exception $e) {
            error_log('[PatientController] loadMonitoringData error: ' . $e->getMessage());
        }

        return [$processedMetrics, $userLayout];
    }

    /**
     * Calculates age from birth date.
     *
     * @param string|null $birthDateString
     * @return int
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
     * Streams real-time metrics for the current patient using SSE (Server-Sent Events).
     *
     * @return void
     */
    public function apiStream(): void
    {
        // Increase execution time for long-polling/SSE
        set_time_limit(0);
        ignore_user_abort(false);

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable Nginx buffering

        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId && empty($_GET["debug"])) {
                echo "data: " . json_encode(['error' => 'Non autorisé']) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                return;
            }

            session_write_close();

            $roomId = $this->getRoomId();
            $patientId = null;

            if ($roomId) {
                $patientId = $this->patientRepo->getPatientIdByRoom($roomId);
            }
            if (!$patientId) {
                $this->contextService->handleRequest();
                $patientId = $this->contextService->getCurrentPatientId();
            }

            if (!$patientId) {
                echo "data: " . json_encode(['error' => 'Patient introuvable']) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                return;
            }

            $rawUserId = $_SESSION['user_id'] ?? 0;
            $prefs = $this->prefModel->getUserPreferences(is_numeric($rawUserId) ? (int) $rawUserId : 0);

            /** @var \modules\models\entities\Indicator[] $indicators */
            $indicators = $this->monitorModel->getLatestMetrics($patientId);
            $indicatorsById = [];
            $lastSentTimestamp = '1970-01-01 00:00:00';

            foreach ($indicators as $ind) {
                if ($ind instanceof \modules\models\entities\Indicator) {
                    $indicatorsById[$ind->getId()] = $ind;
                    if ($ind->getTimestamp() > $lastSentTimestamp) {
                        $lastSentTimestamp = $ind->getTimestamp();
                    }
                }
            }

            /**
             * Continuous streaming loop.
             * Tracks the last sent timestamp from the DB to avoid time drift
             * and ensures no data points are skipped between polling intervals.
             */
            while (true) {
                if (connection_aborted()) {
                    break;
                }
                
                // Fetch ALL data since $lastSentTimestamp for all indicators
                $allNewHistory = $this->monitorModel->getRawHistory($patientId, 0, $lastSentTimestamp);
                
                if (!empty($allNewHistory)) {
                    $formatted = [];
                    foreach ($allNewHistory as $hItem) {
                        $pid = $hItem['parameter_id'];
                        $ts = $hItem['timestamp'];
                        $val = $hItem['value'];
                        $flag = $hItem['alert_flag'];

                        if ($ts > $lastSentTimestamp) {
                            $lastSentTimestamp = $ts;
                        }

                        $indicator = $indicatorsById[$pid] ?? null;
                        if ($indicator) {
                            /**
                             * Synchronize indicator state with the historical record
                             * to generate correct metadata (color, slug, etc).
                             */
                            $indicator->setValue($val !== null && $val !== '' ? (float)$val : null);
                            $indicator->setTimestamp($ts);
                            $indicator->setAlertFlag((int)$flag);

                            $vd = $this->monitoringService->prepareViewData($indicator);
                            $rawTs = (strpos($ts, '+') === false && strpos($ts, 'Z') === false) ? $ts . ' UTC' : $ts;

                            $formatted[] = [
                                'parameter_id' => $pid,
                                'slug' => $vd['slug'] ?? 'param',
                                'value' => $vd['value'] ?? '',
                                'unit' => $vd['unit'] ?? '',
                                'state_class' => $vd['card_class'] ?? '',
                                'is_crit_flag' => (bool)($vd['is_crit_flag'] ?? false),
                                'time_iso' => date('c', (int)strtotime($rawTs)),
                                'display_name' => $vd['display_name'] ?? ''
                            ];
                        }
                    }

                    if (!empty($formatted)) {
                        echo "data: " . json_encode($formatted) . "\n\n";
                        if (ob_get_level() > 0) ob_flush();
                        flush();
                    }
                }

                sleep(1);
            }
        } catch (\Exception $e) {
            error_log('[PatientController] apiStream error: ' . $e->getMessage());
            echo "data: " . json_encode(['error' => 'Erreur interne']) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
    }
}
