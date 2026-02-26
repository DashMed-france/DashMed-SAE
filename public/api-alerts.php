<?php

/**
 * API Endpoint to retrieve patient alerts
 * Usage: GET /api-alerts.php?patient_id=1 or ?room=5
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Paris');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../assets/includes/Database.php';

use modules\models\repositories\AlertRepository;
use modules\models\repositories\PatientRepository;
use modules\models\repositories\ConsultationRepository;
use modules\services\AlertService;
use assets\includes\Database;

try {
    session_start();

    $pdo = Database::getInstance();
    $patientRepo = new PatientRepository($pdo);
    $patientId = null;

    if (isset($_GET['patient_id'])) {
        $rawId = filter_var($_GET['patient_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $patientId = $rawId !== false ? $rawId : null;
    } elseif (isset($_GET['room'])) {
        $roomId = filter_var($_GET['room'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($roomId !== false) {
            $patientId = $patientRepo->getPatientIdByRoom($roomId);
        }
    } elseif (isset($_COOKIE['room_id'])) {
        $roomId = filter_var($_COOKIE['room_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($roomId !== false) {
            $patientId = $patientRepo->getPatientIdByRoom($roomId);
        }
    }

    if ($patientId === null) {
        echo json_encode([
            'success' => true,
            'alerts' => [],
            'count' => 0,
            'message' => 'Aucun patient sélectionné'
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    }

    $alertRepo = new AlertRepository($pdo);
    $alertService = new AlertService();
    $alertMessages = $alertService->buildAlertMessages($alertRepo->getOutOfThresholdAlerts($patientId));

    $consultRepo = new ConsultationRepository($pdo);
    $todayRdv = $consultRepo->getTodayConsultations($patientId);
    foreach ($todayRdv as $rdv) {
        $alertMessages[] = [
            'type' => 'info',
            'title' => '📅 RDV — ' . htmlspecialchars($rdv['title'], ENT_QUOTES, 'UTF-8'),
            'message' => $rdv['time'] . ' — Dr ' . htmlspecialchars($rdv['doctor'], ENT_QUOTES, 'UTF-8'),
            'parameterId' => 'rdv_' . $rdv['id'],
            'rdvType' => $rdv['type'],
            'rdvTime' => $rdv['time'],
            'doctor' => $rdv['doctor']
        ];
    }

    echo json_encode([
        'success' => true,
        'alerts' => $alertMessages,
        'count' => count($alertMessages),
        'patient_id' => $patientId
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('[api-alerts] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'message' => 'Une erreur est survenue lors de la récupération des alertes.'
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
