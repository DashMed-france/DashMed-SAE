<?php

namespace controllers\pages;

use modules\models\Monitoring\MonitorPreferenceModel;
use modules\views\pages\CustomizationView;
use PHPUnit\Framework\TestCase;
use PDO;

require_once __DIR__ . '/../../../tests/mocks/views/pages/CustomizationView.php';
require_once __DIR__ . '/../../../tests/mocks/models/Monitoring/MonitorPreferenceModel.php';
require_once __DIR__ . '/../../../tests/mocks/controllers/TestableCustomizationController.php';

/**
 * Class CustomizationControllerTest | Tests du Contrôleur de Personnalisation
 *
 * Unit tests for CustomizationController.
 * Tests unitaires pour CustomizationController.
 *
 * @package Tests\Controllers\Pages
 * @author DashMed Team
 */
class CustomizationControllerTest extends TestCase
{
    private $pdoMock;
    private $stmtMock;
    private $prefModelMock;

    /**
     * Setup test environment.
     * Configuration de l'environnement de test.
     */
    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(\PDOStatement::class);
        $this->prefModelMock = $this->createMock(MonitorPreferenceModel::class);

        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Teardown test environment.
     * Nettoyage de l'environnement de test.
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    private function createController(): TestableCustomizationController
    {
        return new TestableCustomizationController($this->pdoMock, $this->prefModelMock);
    }

    /**
     * Test constructor.
     * Teste le constructeur.
     */
    public function testConstructor(): void
    {
        $controller = $this->createController();
        $this->assertInstanceOf(\modules\controllers\pages\CustomizationController::class, $controller);
    }

    /**
     * Test GET redirects if not logged in.
     * Teste que GET redirige si non connecté.
     */
    public function testGetRedirectsIfNotLoggedIn(): void
    {
        unset($_SESSION['email']);

        $controller = $this->createController();
        $controller->get();

        $this->assertTrue($controller->exitCalled, "Exit should be called");
        $this->assertEquals('/?page=signup', $controller->redirectUrl);
    }

    /**
     * Test GET redirects if user ID invalid.
     * Teste que GET redirige si l'ID utilisateur est invalide.
     */
    public function testGetRedirectsIfUserIdInvalid(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $_SESSION['user_id'] = 0;

        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->method('execute');
        $this->stmtMock->method('fetchColumn')->willReturn(false);

        $controller = $this->createController();
        $controller->get();

        $this->assertTrue($controller->exitCalled);
        $this->assertEquals('/?page=signup', $controller->redirectUrl);
    }

    /**
     * Test GET shows view success.
     * Teste que GET affiche la vue avec succès.
     */
    public function testGetShowViewSuccess(): void
    {
        $_SESSION['email'] = 'test@example.com';
        $_SESSION['user_id'] = 123;

        $this->prefModelMock->method('getAllParameters')->willReturn([
            ['parameter_id' => 'hr', 'display_name' => 'Heart Rate', 'category' => 'Vital']
        ]);
        $this->prefModelMock->method('getUserLayoutSimple')->willReturn([]);

        $controller = $this->createController();
        $controller->get();

        $output = $controller->renderedOutput;

        $this->assertThat(
            $output,
            $this->logicalOr(
                $this->stringContains("CustomizationView Mock"),
                $this->stringContains("Personnaliser le tableau de bord")
            ),
            "L'output doit être valide (HTML ou Mock)."
        );
    }
}
