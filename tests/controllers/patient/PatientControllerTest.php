<?php

namespace Tests\Controllers\Patient;

use PHPUnit\Framework\TestCase;
use modules\controllers\PatientController;
use modules\models\repositories\ConsultationRepository;
use modules\models\repositories\PatientRepository;
use modules\models\repositories\UserRepository;
use modules\models\monitoring\MonitorModel;
use modules\models\monitoring\MonitorPreferenceModel;
use modules\services\MonitoringService;
use modules\services\PatientContextService;
use PDO;

class PatientControllerTest extends TestCase
{
    private $patientController;
    private $pdoMock;
    private $patientRepoMock;
    private $consultationRepoMock;
    private $userRepoMock;
    private $monitorModelMock;
    private $prefModelMock;
    private $monitoringServiceMock;
    private $contextServiceMock;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);

        $this->patientRepoMock = $this->createMock(PatientRepository::class);
        $this->consultationRepoMock = $this->createMock(ConsultationRepository::class);
        $this->userRepoMock = $this->createMock(UserRepository::class);
        $this->monitorModelMock = $this->createMock(MonitorModel::class);
        $this->prefModelMock = $this->createMock(MonitorPreferenceModel::class);

        $this->monitoringServiceMock = $this->createMock(MonitoringService::class);
        $this->contextServiceMock = $this->createMock(PatientContextService::class);

        $this->patientController = new PatientController($this->pdoMock);

        $this->injectProperty($this->patientController, 'patientRepo', $this->patientRepoMock);
        $this->injectProperty($this->patientController, 'consultationRepo', $this->consultationRepoMock);
        $this->injectProperty($this->patientController, 'userRepo', $this->userRepoMock);
        $this->injectProperty($this->patientController, 'monitorModel', $this->monitorModelMock);
        $this->injectProperty($this->patientController, 'prefModel', $this->prefModelMock);
        $this->injectProperty($this->patientController, 'monitoringService', $this->monitoringServiceMock);
        $this->injectProperty($this->patientController, 'contextService', $this->contextServiceMock);

    }

    private function injectProperty($object, $propertyName, $value)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    public function testDashboardCallsDependenciesAndRendersView()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['email'] = 'test@example.com';
        $_SESSION['user_id'] = 1;

        $this->contextServiceMock->expects($this->once())->method('handleRequest');
        $this->contextServiceMock->expects($this->once())
            ->method('getCurrentPatientId')
            ->willReturn(123);

        $this->patientRepoMock->expects($this->once())
            ->method('getAllRoomsWithPatients')
            ->willReturn([]);

        $this->patientRepoMock->expects($this->once())
            ->method('findById')
            ->with(123)
            ->willReturn(['first_name' => 'John', 'last_name' => 'Doe']);

        ob_start();
        $this->patientController->dashboard();
        $output = ob_get_clean();

        $this->assertStringContainsString('request_method', strtolower($_SERVER['REQUEST_METHOD'] ?? ''));
    }
}
