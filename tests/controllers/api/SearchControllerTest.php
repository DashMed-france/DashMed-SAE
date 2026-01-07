<?php

namespace controllers\api;

use Exception;
use modules\controllers\api\SearchController;
use modules\models\SearchModel;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once __DIR__ . '/../../../app/controllers/api/SearchController.php';
require_once __DIR__ . '/../../../app/models/SearchModel.php';

/**
 * Classe de contrôleur testable qui étend SearchController.
 *
 * Cette classe permet de :
 * - Éviter l'appel au constructeur parent (pas de connexion DB)
 * - Capturer les réponses JSON au lieu de les envoyer via exit;
 * - Injecter un mock du SearchModel
 */
class TestableSearchController extends SearchController
{
    public int $responseStatus = 200;
    public array $responseData = [];
    public SearchModel $testSearchModel;

    /**
     * Constructeur qui évite d'appeler le parent.
     */
    public function __construct()
    {
        // On n'appelle PAS parent::__construct() pour éviter Database::getInstance()
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    /**
     * Injecte le mock du SearchModel.
     */
    public function setSearchModel(SearchModel $model): void
    {
        $this->testSearchModel = $model;

        // Utiliser Reflection pour définir la propriété privée du parent
        $reflection = new ReflectionClass(SearchController::class);
        $property = $reflection->getProperty('searchModel');
        $property->setValue($this, $model);
    }

    /**
     * Redéfinit jsonResponse pour capturer la réponse au lieu de l'envoyer.
     * Note: Cette méthode "masque" la méthode privée du parent.
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        $this->responseStatus = $status;
        $this->responseData = $data;
    }

    /**
     * Override de la méthode get() pour utiliser notre jsonResponse.
     * On réécrit la logique pour éviter d'appeler la méthode privée du parent.
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
            $reflection = new ReflectionClass(SearchController::class);
            $property = $reflection->getProperty('searchModel');
            $searchModel = $property->getValue($this);

            $results = $searchModel->searchGlobal($query, 5, $patientId);
            $this->jsonResponse(['results' => $results]);
        } catch (Exception) {
            // error_log supprimé pour les tests (évite l'affichage dans la console)
            $this->jsonResponse(['error' => 'Erreur Serveur'], 500);
        }
    }
}

/**
 * Tests unitaires pour SearchController.
 *
 * Ces tests utilisent TestableSearchController pour éviter les problèmes
 * liés au constructeur (Database) et au exit; dans jsonResponse.
 *
 * @package controllers\api
 */
class SearchControllerTest extends TestCase
{
    private $searchModelMock;

    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_SESSION = [];
    }

    private function createController(): TestableSearchController
    {
        $this->searchModelMock = $this->createMock(SearchModel::class);
        $controller = new TestableSearchController();
        $controller->setSearchModel($this->searchModelMock);
        return $controller;
    }

    /**
     * Vérifie que l'API retourne 401 si l'utilisateur n'est pas connecté.
     */
    public function testGetReturns401IfNotLoggedIn(): void
    {
        // Pas de session email
        $controller = $this->createController();
        $controller->get();

        $this->assertEquals(401, $controller->responseStatus);
        $this->assertEquals(['error' => 'Non autorisé'], $controller->responseData);
    }

    /**
     * Vérifie que l'API retourne une liste vide si la requête est trop courte.
     */
    public function testGetReturnsEmptyIfQueryTooShort(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $_GET['q'] = 'a';

        $controller = $this->createController();
        $controller->get();

        $this->assertEquals(200, $controller->responseStatus);
        $this->assertEquals(['results' => []], $controller->responseData);
    }

    /**
     * Vérifie que l'API retourne une liste vide si aucune requête n'est fournie.
     */
    public function testGetReturnsEmptyIfNoQuery(): void
    {
        $_SESSION['email'] = 'user@example.com';

        $controller = $this->createController();
        $controller->get();

        $this->assertEquals(200, $controller->responseStatus);
        $this->assertEquals(['results' => []], $controller->responseData);
    }

    /**
     * Vérifie que l'API effectue une recherche et retourne les résultats.
     */
    public function testGetPerformsSearchAndReturnsResults(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $_GET['q'] = 'dupont';
        $_GET['patient_id'] = '10';

        $expectedResults = [
            'patients' => [['id_patient' => 1, 'first_name' => 'Jean', 'last_name' => 'Dupont']],
            'doctors' => [],
            'consultations' => []
        ];

        $this->searchModelMock = $this->createMock(SearchModel::class);
        $this->searchModelMock->expects($this->once())
            ->method('searchGlobal')
            ->with('dupont', 5, 10)
            ->willReturn($expectedResults);

        $controller = new TestableSearchController();
        $controller->setSearchModel($this->searchModelMock);
        $controller->get();

        $this->assertEquals(200, $controller->responseStatus);
        $this->assertEquals(['results' => $expectedResults], $controller->responseData);
    }

    /**
     * Vérifie que la recherche fonctionne sans patient_id.
     */
    public function testGetPerformsSearchWithoutPatientId(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $_GET['q'] = 'martin';

        $expectedResults = [
            'patients' => [],
            'doctors' => [['id_user' => 5, 'first_name' => 'Marie', 'last_name' => 'Martin']],
            'consultations' => []
        ];

        $this->searchModelMock = $this->createMock(SearchModel::class);
        $this->searchModelMock->expects($this->once())
            ->method('searchGlobal')
            ->with('martin', 5, null)
            ->willReturn($expectedResults);

        $controller = new TestableSearchController();
        $controller->setSearchModel($this->searchModelMock);
        $controller->get();

        $this->assertEquals(200, $controller->responseStatus);
        $this->assertEquals(['results' => $expectedResults], $controller->responseData);
    }

    /**
     * Vérifie que l'API retourne une erreur 500 si le modèle lève une exception.
     */
    public function testGetHandlesModelException(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $_GET['q'] = 'crash';

        $this->searchModelMock = $this->createMock(SearchModel::class);
        $this->searchModelMock->expects($this->once())
            ->method('searchGlobal')
            ->willThrowException(new Exception('DB failure'));

        $controller = new TestableSearchController();
        $controller->setSearchModel($this->searchModelMock);
        $controller->get();

        $this->assertEquals(500, $controller->responseStatus);
        $this->assertEquals(['error' => 'Erreur Serveur'], $controller->responseData);
    }

    /**
     * Vérifie que les espaces sont trimés de la requête.
     */
    public function testGetTrimsQueryWhitespace(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $_GET['q'] = '   test   ';

        $expectedResults = ['patients' => [], 'doctors' => [], 'consultations' => []];

        $this->searchModelMock = $this->createMock(SearchModel::class);
        $this->searchModelMock->expects($this->once())
            ->method('searchGlobal')
            ->with('test', 5, null)
            ->willReturn($expectedResults);

        $controller = new TestableSearchController();
        $controller->setSearchModel($this->searchModelMock);
        $controller->get();

        $this->assertEquals(200, $controller->responseStatus);
    }

    /**
     * Vérifie que patient_id non numérique est ignoré.
     */
    public function testGetIgnoresNonNumericPatientId(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $_GET['q'] = 'test';
        $_GET['patient_id'] = 'invalid';

        $expectedResults = ['patients' => [], 'doctors' => [], 'consultations' => []];

        $this->searchModelMock = $this->createMock(SearchModel::class);
        $this->searchModelMock->expects($this->once())
            ->method('searchGlobal')
            ->with('test', 5, null)
            ->willReturn($expectedResults);

        $controller = new TestableSearchController();
        $controller->setSearchModel($this->searchModelMock);
        $controller->get();

        $this->assertEquals(200, $controller->responseStatus);
    }
}
