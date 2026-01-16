<?php

namespace controllers\api;

use Exception;
use modules\controllers\api\SearchController;
use modules\models\SearchModel;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once __DIR__ . '/../../../app/controllers/api/SearchController.php';
require_once __DIR__ . '/../../../app/models/SearchModel.php';

require_once __DIR__ . '/../../mocks/controllers/TestableSearchController.php';

/**
 * Class SearchControllerTest | Tests API Recherche
 *
 * Unit tests for the search API.
 * Tests unitaires pour l'API de recherche.
 *
 * @package Tests\Controllers\Api
 * @author DashMed Team
 */
class SearchControllerTest extends TestCase
{
    private $searchModelMock;

    /**
     * Setup test session.
     * Configuration de la session de test.
     */
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
        $_GET = [];
    }

    /**
     * Teardown cleanup.
     * Nettoyage après test.
     */
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
     * Verify 401 unauthorized.
     * Vérifie le code 401 si non connecté.
     */
    public function testGetReturns401IfNotLoggedIn(): void
    {
        $controller = $this->createController();
        $controller->get();

        $this->assertEquals(401, $controller->responseStatus);
        $this->assertEquals(['error' => 'Non autorisé'], $controller->responseData);
    }

    /**
     * Verify empty results for short query.
     * Vérifie une liste vide pour une requête trop courte.
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
     * Verify empty results if no query.
     * Vérifie une liste vide sans requête.
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
     * Verify successful search.
     * Vérifie une recherche réussie.
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
     * Verify search without patient ID.
     * Vérifie la recherche sans ID patient.
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
     * Verify 500 error on exception.
     * Vérifie l'erreur 500 en cas d'exception.
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
     * Verify whitespace trimming.
     * Vérifie le nettoyage des espaces.
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
     * Verify invalid patient ID ignored.
     * Vérifie que l'ID patient invalide est ignoré.
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
