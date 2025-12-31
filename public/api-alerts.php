<?php

/**
 * Endpoint API pour récupérer les alertes d'un patient
 * Usage: GET /api-alerts.php?patient_id=1 ou ?room=5
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../assets/includes/database.php';

use modules\models\Alert\AlertRepository;
use modules\models\PatientModel;
use modules\services\AlertService;

try {
    // Récupération de l'ID patient (depuis session ou paramètre)
    session_start();

    $pdo = Database::getInstance();
    $patientModel = new PatientModel($pdo);
    $patientId = null;

    // Priorité 1: Paramètre GET patient_id
    if (isset($_GET['patient_id'])) {
        $rawId = filter_var($_GET['patient_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $patientId = $rawId !== false ? $rawId : null;
    } elseif (isset($_GET['room'])) {
        // Priorité 2: Depuis la room en GET
        $roomId = filter_var($_GET['room'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($roomId !== false) {
            $patientId = $patientModel->getPatientIdByRoom($roomId);
        }
    } elseif (isset($_COOKIE['room_id'])) {
        // Priorité 3: Depuis cookie room_id
        $roomId = filter_var($_COOKIE['room_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($roomId !== false) {
            $patientId = $patientModel->getPatientIdByRoom($roomId);
        }
    }

    // Si pas de patient, retourner tableau vide
    if ($patientId === null) {
        echo json_encode([
            'success' => true,
            'alerts' => [],
            'count' => 0,
            'message' => 'Aucun patient sélectionné'
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    }

    // Récupération des alertes
    $alertRepo = new AlertRepository($pdo);
    $alertService = new AlertService();

    $rawAlerts = $alertRepo->getOutOfThresholdAlerts($patientId);
    $alertMessages = $alertService->buildAlertMessages($rawAlerts);

    // Réponse JSON
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
