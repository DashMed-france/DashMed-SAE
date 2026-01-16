<?php

namespace controllers\pages\Monitoring;

use PHPUnit\Framework\TestCase;
use modules\controllers\pages\Monitoring\MonitoringController;
use modules\models\Monitoring\MonitorModel;
use modules\models\Monitoring\MonitorPreferenceModel;
use modules\models\PatientModel;
use modules\services\MonitoringService;
use PDO;

require_once __DIR__ . '/../../../../tests/mocks/Database.php';
require_once __DIR__ . '/../../../../tests/mocks/models/Monitoring/MonitorModel.php';
require_once __DIR__ . '/../../../../tests/mocks/models/Monitoring/MonitorPreferenceModel.php';
require_once __DIR__ . '/../../../../tests/mocks/models/PatientModel.php';
require_once __DIR__ . '/../../../../tests/mocks/services/MonitoringService.php';
require_once __DIR__ . '/../../../../tests/mocks/views/pages/Monitoring/MonitoringView.php';

require_once __DIR__ . '/../../../../app/controllers/pages/Monitoring/MonitoringController.php';

/**
 * Class MonitoringControllerTest | Tests Contrôleur de Surveillance
 *
 * Unit tests for MonitoringController.
 * Tests unitaires pour MonitoringController.
 *
 * @package Tests\Controllers\Pages\Monitoring
 * @author DashMed Team
 */
class MonitoringControllerTest extends TestCase
{
    private $pdoMock;
    private $monitorModelMock;
    private $prefModelMock;
    private $patientModelMock;
    private $monitoringServiceMock;
    private $controller;

    /**
     * Setup test environment.
     * Configuration de l'environnement de test.
     */
    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);


        \assets\includes\Database::setInstance($this->pdoMock);

        $this->monitorModelMock = $this->createMock(MonitorModel::class);
        $this->prefModelMock = $this->createMock(MonitorPreferenceModel::class);
        $this->patientModelMock = $this->createMock(PatientModel::class);
        $this->monitoringServiceMock = $this->createMock(MonitoringService::class);

        $_SESSION = [];

        $this->controller = new MonitoringController();

        $ref = new \ReflectionClass($this->controller);

        $p1 = $ref->getProperty('monitorModel');
        $p1->setAccessible(true);
        $p1->setValue($this->controller, $this->monitorModelMock);

        $p2 = $ref->getProperty('prefModel');
        $p2->setAccessible(true);
        $p2->setValue($this->controller, $this->prefModelMock);

        $p3 = $ref->getProperty('patientModel');
        $p3->setAccessible(true);
        $p3->setValue($this->controller, $this->patientModelMock);

        $p4 = $ref->getProperty('monitoringService');
        $p4->setAccessible(true);
        $p4->setValue($this->controller, $this->monitoringServiceMock);
    }

    /**
     * Teardown test environment.
     * Nettoyage de l'environnement de test.
     */
    protected function tearDown(): void
    {
        try {
            $ref = new \ReflectionProperty(\assets\includes\Database::class, 'pdo');
            $ref->setAccessible(true);
            $ref->setValue(null, null);
        } catch (\Exception $e) {
        }
    }

    /**
     * Test GET shows view success.
     * Teste que GET affiche la vue avec succès.
     */
    public function testGetShowViewSuccess()
    {
        $_SESSION['email'] = 'user@test.com';
        $_SESSION['user_id'] = 1;
        $_GET['room'] = 101;

        $this->patientModelMock->method('getPatientIdByRoom')->with(101)->willReturn(55);
        $this->monitorModelMock->method('getLatestMetrics')->willReturn(['hr' => 80]);
        $this->monitorModelMock->method('getRawHistory')->willReturn([]);
        $this->prefModelMock->method('getUserPreferences')->willReturn([]);
        $this->monitoringServiceMock->method('processMetrics')->willReturn(['processed' => true]);
        $this->monitorModelMock->method('getAllChartTypes')->willReturn(['line']);

        ob_start();
        $this->controller->get();
        $output = ob_get_clean();

        $this->assertThat(
            $output,
            $this->logicalOr(
                $this->stringContains("View Shown"),
                $this->stringContains("<!DOCTYPE html>"),
                $this->stringContains("Monitoring")
            ),
            "Output should contain Mock signature or Real View HTML elements"
        );
    }
}
