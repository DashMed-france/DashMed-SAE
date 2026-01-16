<?php

namespace controllers\pages;

use modules\controllers\pages\PatientrecordController;
use modules\models\PatientModel;
use modules\models\Consultation;
use modules\services\PatientContextService;
use modules\views\pages\PatientrecordView;
use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionClass;

require_once __DIR__ . '/../../../app/controllers/pages/PatientrecordController.php';
require_once __DIR__ . '/../../../app/models/PatientModel.php';
require_once __DIR__ . '/../../../app/models/Consultation.php';
require_once __DIR__ . '/../../../app/services/PatientContextService.php';
require_once __DIR__ . '/../../../app/views/pages/PatientrecordView.php';

require_once __DIR__ . '/../../mocks/controllers/TestablePatientrecordController.php';

/**
 * Class PatientRecordControllerTest | Tests du Contrôleur de Dossier Patient
 *
 * Unit tests for PatientrecordController.
 * Tests unitaires pour PatientrecordController.
 *
 * @package Tests\Controllers\Pages
 * @author DashMed Team
 */
class PatientRecordControllerTest extends TestCase
{
    private $pdoMock;
    private $patientModelMock;
    private $contextServiceMock;

    /**
     * Setup.
     * Configuration.
     */
    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(\PDO::class);
        $this->patientModelMock = $this->createMock(PatientModel::class);
        $this->contextServiceMock = $this->createMock(PatientContextService::class);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
    }

    private function createController(): TestablePatientrecordController
    {
        return new TestablePatientrecordController(
            $this->pdoMock,
            $this->patientModelMock,
            $this->contextServiceMock
        );
    }

    /**
     * Test instantiation.
     * Teste l'instanciation.
     */
    public function testInstantiation()
    {
        $controller = $this->createController();
        $this->assertInstanceOf(PatientrecordController::class, $controller);
    }

    /**
     * Test GET redirects if not logged in.
     * Teste que GET redirige si non connecté.
     */
    public function testGetRedirectsIfNotLoggedIn()
    {
        unset($_SESSION['email']);
        $controller = $this->createController();
        $controller->get();

        $this->assertTrue($controller->exitCalled);
        $this->assertEquals('/?page=login', $controller->redirectUrl);
    }

    /**
     * Test GET shows view when logged in.
     * Teste que GET affiche la vue si connecté.
     */
    public function testGetShowViewWhenLoggedIn()
    {
        $_SESSION['email'] = 'test@example.com';
        $_SESSION['admin_status'] = 0;

        $this->contextServiceMock->expects($this->once())
            ->method('handleRequest');
        $this->contextServiceMock->expects($this->once())
            ->method('getCurrentPatientId')
            ->willReturn(123);

        $this->patientModelMock->expects($this->once())
            ->method('findById')
            ->with(123)
            ->willReturn([
                'id_patient' => 123,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'birth_date' => '2000-01-01',
                'admission_cause' => 'Test',
                'medical_history' => 'None',
                'gender' => 'H'
            ]);

        $this->patientModelMock->expects($this->once())
            ->method('getDoctors')
            ->with(123)
            ->willReturn([]);

        $controller = $this->createController();
        $controller->get();

        $output = $controller->renderedOutput;
        $this->assertStringContainsString('John', $output);
        $this->assertStringContainsString('DOE', $output);
    }

    /**
     * Test GET handles exception.
     * Teste que GET gère les exceptions.
     */
    public function testGetHandlesException()
    {
        $_SESSION['email'] = 'test@example.com';
        $_SESSION['user_id'] = 1;

        $this->contextServiceMock->method('getCurrentPatientId')->willReturn(123);
        $this->patientModelMock->method('findById')->willThrowException(new \Exception('DB Crash'));

        $controller = $this->createController();
        $controller->get();

        $output = $controller->renderedOutput;
        $this->assertStringContainsString('Une erreur interne est survenue', $output);
    }

    /**
     * Test calculate age logic.
     * Teste la logique de calcul de l'âge.
     */
    public function testCalculateAgeLogic()
    {
        $controller = $this->createController();
        $age = $controller->publicCalculateAge('2000-01-01');
        $expectedMin = date('Y') - 2000 - 1;
        $this->assertGreaterThanOrEqual($expectedMin, $age);
    }

    /**
     * Test isUserLoggedIn.
     * Teste la vérification de connexion utilisateur.
     */
    public function testIsUserLoggedIn()
    {
        $_SESSION['email'] = 'test@test.com';
        $controller = $this->createController();
        $this->assertTrue($controller->publicIsUserLoggedIn());

        unset($_SESSION['email']);
        $this->assertFalse($controller->publicIsUserLoggedIn());
    }
}
