<?php
/**
 * Endpoint API pour récupérer les alertes d'un patient
 * Usage: GET /api-alerts.php?patient_id=1
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../assets/includes/database.php';

use modules\models\Alert\AlertRepository;
use modules\services\AlertService;


try {
    // Récupération de l'ID patient (depuis session ou paramètre)
    session_start();

    $patientId = null;

    // Priorité 1: Paramètre GET
    if (isset($_GET['patient_id'])) {
        $patientId = (int) $_GET['patient_id'];
    }
    // Priorité 2: Depuis la room en cookie/GET
    elseif (isset($_GET['room'])) {
        $roomId = (int) $_GET['room'];
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id_patient FROM patients WHERE room_id = :room_id AND status = 'En réanimation' LIMIT 1");
        $stmt->execute([':room_id' => $roomId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $patientId = (int) $row['id_patient'];
        }
    }
    // Priorité 3: Depuis cookie room_id
    elseif (isset($_COOKIE['room_id'])) {
        $roomId = (int) $_COOKIE['room_id'];
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id_patient FROM patients WHERE room_id = :room_id AND status = 'En réanimation' LIMIT 1");
        $stmt->execute([':room_id' => $roomId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $patientId = (int) $row['id_patient'];
        }
    }

    // Si pas de patient, retourner tableau vide
    if (!$patientId) {
        echo json_encode([
            'success' => true,
            'alerts' => [],
            'count' => 0,
            'message' => 'Aucun patient sélectionné'
        ]);
        exit;
    }

    // Récupération des alertes
    $pdo = Database::getInstance();
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}
