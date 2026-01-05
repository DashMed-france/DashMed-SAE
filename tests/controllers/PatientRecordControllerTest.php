<?php

use PHPUnit\Framework\TestCase;
use modules\controllers\pages\PatientRecordController;
use modules\models\PatientModel;
use modules\services\PatientContextService;

// Ensure we can autoload or access classes
// In a real setup, autoloader handles this. For this standalone test:
// require_once __DIR__ . '/../../app/controllers/pages/PatientRecordController.php';

class PatientRecordControllerTest extends TestCase
{
    private $pdo;
    private $patientModelMock;
    private $contextServiceMock;

    protected function setUp(): void
    {
        // 1. InMemory SQLite Database
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 2. Create Mocks for Dependencies
        // We mock PatientModel to avoid needing a real DB schema for `users`/`patients` in this specific test
        $this->patientModelMock = $this->createMock(PatientModel::class);
        $this->contextServiceMock = $this->createMock(PatientContextService::class);
    }

    public function testInstantiation()
    {
        $controller = new PatientRecordController($this->pdo, $this->patientModelMock, $this->contextServiceMock);
        $this->assertInstanceOf(PatientRecordController::class, $controller);
    }

    public function testCalculateAgeLogic()
    {
        // Since calculateAge is private/internal logic usually tested via public interface,
        // we can structurally test the generic behaviors via `get()` if we mock the model correctly.
        // However, `get()` outputs HTML and headers, which is hard to test in CLI without output buffering.
        // So we will focus on verifying that the Controller *tries* to fetch data.

        // Expect getCurrentPatientId to be called
        $this->contextServiceMock->method('getCurrentPatientId')->willReturn(123);

        // Expect findById to be called
        $this->patientModelMock->expects($this->once())
            ->method('findById')
            ->with(123)
            ->willReturn([
                'id_patient' => 123,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'birth_date' => '2000-01-01'
            ]);

        // Expect getDoctors to be called
        $this->patientModelMock->expects($this->once())
            ->method('getDoctors')
            ->with(123)
            ->willReturn([]);

        // Valid instantiation
        $controller = new PatientRecordController($this->pdo, $this->patientModelMock, $this->contextServiceMock);

        // To test `get()`, we need to suppress headers output or mock session/header issues.
        // PHPUnit runs in CLI, so header() calls might fail or do nothing.
        // We can use output buffering to capture the View output.

        // Mock Session
        if (session_status() === PHP_SESSION_NONE) {
            $_SESSION['email'] = 'test@example.com';
        }

        ob_start();
        $controller->get();
        $output = ob_get_clean();

        $this->assertStringContainsString('John', $output);
        $this->assertStringContainsString('DOE', $output); // Uppercase in View
    }

    public function testGetExceptionHandling()
    {
        // Simulate a critical error in Model
        $this->contextServiceMock->method('getCurrentPatientId')->willReturn(999);
        $this->patientModelMock->method('findById')->willThrowException(new Exception("Database Down"));

        // Capture Output
        ob_start();
        $controller = new PatientRecordController($this->pdo, $this->patientModelMock, $this->contextServiceMock);
        $controller->get();
        $output = ob_get_clean();

        // Check if error view is rendered
        $this->assertStringContainsString('Une erreur interne est survenue', $output);
    }
}
