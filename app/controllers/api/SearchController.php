<?php

namespace modules\controllers\api;

use modules\models\SearchModel;
use assets\includes\Database;
use PDO;

/**
 * Class SearchController
 *
 * API Controller for global search (Spotlight).
 *
 * Exposes a REST endpoint for asynchronous searches.
 * Requires authentication.
 *
 * @package DashMed\Modules\Controllers\Api
 * @author DashMed Team
 * @license Proprietary
 */
class SearchController
{
    /** @var PDO Database connection */
    private PDO $pdo;

    /** @var SearchModel Search model instance */
    private SearchModel $searchModel;

    /**
     * Constructor
     *
     * Initializes controller and dependencies. Starts session if needed.
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
     * Handles GET search requests.
     *
     * Expected GET parameters:
     * - q: Search term
     * - patient_id (optional): Context patient ID
     *
     * Returns JSON response.
     *
     * @return void
     */
    public function get(): void
    {
        if (!isset($_SESSION['email'])) {
            $this->jsonResponse(['error' => 'Non autorisé'], 401);
            return;
        }

        $rawQuery = $_GET['q'] ?? '';
        $query = trim(is_string($rawQuery) ? $rawQuery : '');
        $rawPatientId = $_GET['patient_id'] ?? null;
        $patientId = $rawPatientId !== null && is_numeric($rawPatientId) ? (int) $rawPatientId : null;

        if (mb_strlen($query) < 2) {
            $this->jsonResponse(['results' => []]);
            return;
        }

        try {
            $results = $this->searchModel->searchGlobal($query, 5, $patientId);
            $this->jsonResponse(['results' => $results]);
        } catch (\Exception $e) {
            error_log("[SearchController] Internal Error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Erreur Serveur'], 500);
        }
    }

    /**
     * Sends a standardized JSON response.
     *
     * @param array<string, mixed> $data Data to serialize
     * @param int $status HTTP Status Code (default 200)
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
