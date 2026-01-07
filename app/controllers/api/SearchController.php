<?php

namespace modules\controllers\api;

use modules\models\SearchModel;
use Database;
use PDO;

require_once __DIR__ . '/../../../assets/includes/database.php';
require_once __DIR__ . '/../../models/SearchModel.php';

/**
 * API Controller for global search (Spotlight).
 *
 * This controller exposes a REST endpoint to perform asynchronous searches.
 * It secures access (authentication required) and delegates business logic to the model.
 *
 * @package modules\controllers\api
 */
class SearchController
{
    private PDO $pdo;
    private SearchModel $searchModel;

    /**
     * Initializes the controller with its dependencies.
     * Starts the session if necessary for access verification.
     */
    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->searchModel = new SearchModel($this->pdo);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Handles a GET search request.
     *
     * Expected parameters (GET):
     * - q: The search term.
     * - patient_id (optional): The patient context ID to filter results.
     *
     * Returns a structured JSON response or an HTTP error code.
     *
     * @return void
     */
    public function get(): void
    {
        if (!isset($_SESSION['email'])) {
            $this->jsonResponse(['error' => 'Non autorisé'], 401);
            return;
        }

        $query = trim($_GET['q'] ?? '');
        $patientId = isset($_GET['patient_id']) && is_numeric($_GET['patient_id']) ? (int) $_GET['patient_id'] : null;

        if (mb_strlen($query) < 2) {
            $this->jsonResponse(['results' => []]);
            return;
        }

        try {
            $results = $this->searchModel->searchGlobal($query, 5, $patientId);
            $this->jsonResponse(['results' => $results]);
        } catch (\Exception $e) {
            error_log("[SearchController] Erreur interne : " . $e->getMessage());
            $this->jsonResponse(['error' => 'Erreur Serveur'], 500);
        }
    }

    /**
     * Utility method to send a standardized JSON response.
     *
     * @param array $data   Data to serialize.
     * @param int   $status HTTP status code (default 200).
     * @return void
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}