<?php

use PHPUnit\Framework\TestCase;
use modules\controllers\pages\PatientRecordController;
use modules\models\PatientModel;
use modules\services\PatientContextService;

/**
 * Unit tests for the PatientRecordController.
 */
class PatientRecordControllerTest extends TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var PatientModel|\PHPUnit\Framework\MockObject\MockObject */
    private $patientModelMock;

    /** @var PatientContextService|\PHPUnit\Framework\MockObject\MockObject */
    private $contextServiceMock;

    /**
     * Setup before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->patientModelMock = $this->createMock(PatientModel::class);
        $this->contextServiceMock = $this->createMock(PatientContextService::class);
    }

    /**
     * Tests the instantiation of the controller.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $controller = new PatientRecordController($this->pdo, $this->patientModelMock, $this->contextServiceMock);
        $this->assertInstanceOf(PatientRecordController::class, $controller);
    }

    /**
     * Tests the logic for calculating age and displaying patient information.
     *
     * @return void
     */
    public function testCalculateAgeLogic()
    {
        $this->contextServiceMock->method('getCurrentPatientId')->willReturn(123);

        $this->patientModelMock->expects($this->once())
            ->method('findById')
            ->with(123)
            ->willReturn([
                'id_patient' => 123,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'birth_date' => '2000-01-01'
            ]);

        $this->patientModelMock->expects($this->once())
            ->method('getDoctors')
            ->with(123)
            ->willReturn([]);

        $controller = new PatientRecordController($this->pdo, $this->patientModelMock, $this->contextServiceMock);

        if (session_status() === PHP_SESSION_NONE) {
            $_SESSION['email'] = 'test@example.com';
        }

        ob_start();
        $controller->get();
        $output = ob_get_clean();

        $this->assertStringContainsString('John', $output);
        $this->assertStringContainsString('DOE', $output);
    }

    /**
     * Tests exception handling during the GET request.
     *
     * @return void
     */
    public function testGetExceptionHandling()
    {
        $this->contextServiceMock->method('getCurrentPatientId')->willReturn(999);
        $this->patientModelMock->method('findById')->willThrowException(new Exception("Database Down"));

        ob_start();
        $controller = new PatientRecordController($this->pdo, $this->patientModelMock, $this->contextServiceMock);
        $controller->get();
        $output = ob_get_clean();

        $this->assertStringContainsString('Une erreur interne est survenue', $output);
    }
}