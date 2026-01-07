<?php

namespace modules\controllers\api;

use modules\models\SearchModel;
use Database;
use PDO;

/**
 * Contrôleur API pour la recherche globale (Spotlight).
 *
 * Ce contrôleur expose un endpoint REST pour effectuer des recherches asynchrones.
 * Il sécurise l'accès (authentification requise) et délègue la logique métier au modèle.
 *
 * @package modules\controllers\api
 */
class SearchController
{
    private PDO $pdo;
    private SearchModel $searchModel;

    /**
     * Initialise le contrôleur avec ses dépendances.
     * Démarre la session si nécessaire pour la vérification d'accès.
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
     * Traite une requête GET de recherche.
     *
     * Paramètres attendus (GET) :
     * - q : Le terme de recherche.
     * - patient_id (optionnel) : L'ID du contexte patient pour filtrer les résultats.
     *
     * Retourne une réponse JSON structurée ou un code d'erreur HTTP.
     *
     * @return void
     */
    public function get(): void
    {
        // Vérification de sécurité
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
     * Utilitaire pour envoyer une réponse JSON standardisée.
     *
     * @param array $data   Données à sérialiser.
     * @param int   $status Code HTTP (défaut 200).
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
