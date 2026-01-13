<?php

namespace modules\controllers\api;

use modules\models\SearchModel;
use Database;
use PDO;

/**
 * Class SearchController | Contrôleur de Recherche
 *
 * API Controller for global search (Spotlight).
 * Contrôleur API pour la recherche globale (Spotlight).
 *
 * Exposes a REST endpoint for asynchronous searches.
 * Expose un endpoint REST pour effectuer des recherches asynchrones.
 * Requires authentication.
 * Nécessite une authentification.
 *
 * @package DashMed\Modules\Controllers\Api
 * @author DashMed Team
 * @license Proprietary
 */
class SearchController
{
    /** @var PDO Database connection | Connexion BDD */
    private PDO $pdo;

    /** @var SearchModel Search model instance | Instance du modèle de recherche */
    private SearchModel $searchModel;

    /**
     * Constructor | Constructeur
     *
     * Initializes controller and dependencies. Starts session if needed.
     * Initialise le contrôleur et ses dépendances. Démarre la session si nécessaire.
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
     * Traite une requête GET de recherche.
     *
     * Expected GET parameters:
     * - q: Search term
     * - patient_id (optional): Context patient ID
     *
     * Paramètres attendus (GET) :
     * - q : Le terme de recherche.
     * - patient_id (optionnel) : L'ID du contexte patient.
     *
     * Returns JSON response.
     * Retourne une réponse JSON.
     *
     * @return void
     */
    public function get(): void
    {
        // Security check | Vérification de sécurité
        if (!isset($_SESSION['email'])) {
            $this->jsonResponse(['error' => 'Unauthorized | Non autorisé'], 401);
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
            $this->jsonResponse(['error' => 'Server Error | Erreur Serveur'], 500);
        }
    }

    /**
     * Sends a standardized JSON response.
     * Envoie une réponse JSON standardisée.
     *
     * @param array<string, mixed> $data Data to serialize | Données à sérialiser
     * @param int $status HTTP Status Code (default 200) | Code HTTP (défaut 200)
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
