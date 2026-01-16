<?php

namespace controllers\api;

use modules\controllers\api\SearchController;
use modules\models\SearchModel;
use ReflectionClass;

use function session_start;
use function session_status;

use const PHP_SESSION_ACTIVE;

require_once __DIR__ . '/../../../app/controllers/api/SearchController.php';
require_once __DIR__ . '/../../../app/models/SearchModel.php';

/**
 * Class TestableSearchController | Contrôleur Testable
 *
 * Extension to verify API responses without sending HTTP headers.
 * Extension permettant de vérifier les réponses API sans envoyer d'en-têtes HTTP.
 */
class TestableSearchController extends SearchController
{
    public int $responseStatus = 200;
    public array $responseData = [];
    public SearchModel $testSearchModel;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    public function setSearchModel(SearchModel $model): void
    {
        $this->testSearchModel = $model;

        $reflection = new ReflectionClass(SearchController::class);
        $property = $reflection->getProperty('searchModel');
        $property->setValue($this, $model);
    }

    protected function jsonResponse(array $data, int $status = 200): void
    {
        $this->responseStatus = $status;
        $this->responseData = $data;
    }

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
            $reflection = new ReflectionClass(SearchController::class);
            $property = $reflection->getProperty('searchModel');
            $searchModel = $property->getValue($this);

            $results = $searchModel->searchGlobal($query, 5, $patientId);
            $this->jsonResponse(['results' => $results]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Erreur Serveur'], 500);
        }
    }
}
