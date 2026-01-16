<?php

namespace controllers\pages;

use PHPUnit\Framework\TestCase;
use modules\controllers\pages\DashboardController;
use modules\views\pages\DashboardView;
use PDO;

require_once __DIR__ . '/../../../tests/mocks/Database.php';
require_once __DIR__ . '/../../../tests/mocks/models/Monitoring/MonitorModel.php';
require_once __DIR__ . '/../../../tests/mocks/models/Monitoring/MonitorPreferenceModel.php';
require_once __DIR__ . '/../../../tests/mocks/models/PatientModel.php';
require_once __DIR__ . '/../../../tests/mocks/models/ConsultationModel.php';
require_once __DIR__ . '/../../../tests/mocks/services/MonitoringService.php';
require_once __DIR__ . '/../../../tests/mocks/services/PatientContextService.php';
require_once __DIR__ . '/../../../tests/mocks/services/ConsultationService.php';
require_once __DIR__ . '/../../../tests/mocks/views/pages/DashboardView.php';

require_once __DIR__ . '/../../../app/controllers/pages/DashboardController.php';

/**
 * Class DashboardControllerTest | Tests du Contrôleur Dashboard
 *
 * Unit tests for DashboardController.
 * Tests unitaires pour DashboardController.
 *
 * Uses mocks for all dependencies (Models, Services, View).
 * Utilise des bouchons (mocks) pour toutes les dépendances.
 *
 * @package Tests\Controllers\Pages
 * @author DashMed Team
 */
class DashboardControllerTest extends TestCase
{
    private $pdoMock;
    private $controller;

    private $monitorModelMock;
    private $prefModelMock;
    private $patientModelMock;
    private $consultationModelMock;
    private $monitoringServiceMock;
    private $contextServiceMock;

    /**
     * Sets up the test environment with mocks.
     * Prépare l'environnement de test avec des mocks.
     */
    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);

        \assets\includes\Database::setInstance($this->pdoMock);

        $this->monitorModelMock = $this->createMock(
            \modules\models\Monitoring\MonitorModel::class
        );
        $this->prefModelMock = $this->createMock(
            \modules\models\Monitoring\MonitorPreferenceModel::class
        );
        $this->patientModelMock = $this->createMock(
            \modules\models\PatientModel::class
        );
        $this->consultationModelMock = $this->createMock(
            \modules\models\ConsultationModel::class
        );
        $this->monitoringServiceMock = $this->createMock(
            \modules\services\MonitoringService::class
        );
        $this->contextServiceMock = $this->createMock(
            \modules\services\PatientContextService::class
        );

        $_SESSION = [];

        $this->controller = new DashboardController();

        $ref = new \ReflectionClass($this->controller);

        $props = [
            'monitorModel' => $this->monitorModelMock,
            'prefModel' => $this->prefModelMock,
            'patientModel' => $this->patientModelMock,
            'consultationModel' => $this->consultationModelMock,
            'monitoringService' => $this->monitoringServiceMock,
            'contextService' => $this->contextServiceMock,
        ];

        foreach ($props as $name => $mock) {
            if ($ref->hasProperty($name)) {
                $p = $ref->getProperty($name);
                $p->setAccessible(true);
                $p->setValue($this->controller, $mock);
            }
        }

        if (property_exists(DashboardView::class, 'shown')) {
            DashboardView::$shown = false;
        }
    }

    /**
     * Teardown: Clean up static properties.
     * Nettoyage : Réinitialise les propriétés statiques.
     */
    protected function tearDown(): void
    {
        try {
            $ref = new \ReflectionProperty(\assets\includes\Database::class, 'pdo');
            $ref->setAccessible(true);
            $ref->setValue(null, null);
        } catch (\Exception $e) {
        }
        $_SESSION = [];
    }

    /**
     * Test GET request success scenarios.
     * Teste les scénarios de succès pour la méthode GET.
     *
     * Verifies that the view is rendered with mocked data.
     * Vérifie que la vue est affichée avec les données mockées.
     */
    public function testGetShowViewSuccess()
    {
        $_SESSION['email'] = 'user@test.com';
        $_SESSION['user_id'] = 1;

        $this->contextServiceMock->method('getCurrentPatientId')->willReturn(10);
        $this->patientModelMock->method('getAllRoomsWithPatients')->willReturn([]);
        $this->patientModelMock->method('findById')->willReturn(['first_name' => 'Jean']);
        $this->consultationModelMock->method('getConsultationsByPatientId')->willReturn([]);
        $this->monitorModelMock->method('getLatestMetrics')->willReturn([]);
        $this->monitorModelMock->method('getRawHistory')->willReturn([]);
        $this->prefModelMock->method('getUserPreferences')->willReturn([]);
        $this->prefModelMock->method('getUserLayoutSimple')->willReturn([]);
        $this->monitorModelMock->method('getAllChartTypes')->willReturn([]);
        $this->monitoringServiceMock->method('processMetrics')->willReturn([]);

        ob_start();
        $this->controller->get();
        $output = ob_get_clean();

        $this->assertThat(
            $output,
            $this->logicalOr(
                $this->stringContains('Dashboard View Mock'),
                $this->stringContains('<!DOCTYPE html>'),
                $this->stringContains('Dashboard'),
                $this->stringContains('nav-space')
            ),
            "L'output doit être valide (HTML ou Mock)."
        );
    }
}
