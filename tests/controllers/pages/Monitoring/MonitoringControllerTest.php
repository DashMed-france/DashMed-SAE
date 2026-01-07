<?php

namespace modules\models\Monitoring {
    if (!class_exists('modules\models\Monitoring\MonitorModel')) {
        class MonitorModel
        {
            public function __construct($pdo = null, $table = null)
            {
            }
            public function getLatestMetrics($patientId)
            {
            }
            public function getRawHistory($patientId)
            {
            }
            public function getAllChartTypes()
            {
            }
        }
    }
    if (!class_exists('modules\models\Monitoring\MonitorPreferenceModel')) {
        class MonitorPreferenceModel
        {
            public function __construct($pdo = null)
            {
            }
            public function getUserPreferences($userId)
            {
            }
            public function saveUserChartPreference($userId, $pId, $cType)
            {
            }
        }
    }
}
namespace modules\models {
    if (!class_exists('modules\models\PatientModel')) {
        class PatientModel
        {
            public function getPatientIdByRoom($roomId)
            {
            }
        }
    }
}
namespace modules\services {
    if (!class_exists('modules\services\MonitoringService')) {
        class MonitoringService
        {
            public function processMetrics($metrics, $raw, $prefs, $bool)
            {
            }
        }
    }
}
namespace modules\views\pages\Monitoring {
    if (!class_exists('modules\views\pages\Monitoring\MonitoringView')) {
        class MonitoringView
        {
            public $metrics;
            public $chartTypes;

            public function __construct($metrics, $chartTypes)
            {
                $this->metrics = $metrics;
                $this->chartTypes = $chartTypes;
            }

            public function show()
            {
                echo "View Shown";
            }
        }
    }
}

namespace controllers\pages\Monitoring {

    use PHPUnit\Framework\TestCase;
    use modules\controllers\pages\Monitoring\MonitoringController;
    use modules\models\Monitoring\MonitorModel;
    use modules\models\Monitoring\MonitorPreferenceModel;
    use modules\models\PatientModel;
    use modules\services\MonitoringService;
    use PDO;

    require_once __DIR__ . '/../../../../assets/includes/database.php';
    require_once __DIR__ . '/../../../../app/controllers/pages/Monitoring/MonitoringController.php';

    class MonitoringControllerTest extends TestCase
    {
        private $pdoMock;
        private $monitorModelMock;
        private $prefModelMock;
        private $patientModelMock;
        private $monitoringServiceMock;
        private $controller;

        protected function setUp(): void
        {
            $this->pdoMock = $this->createMock(PDO::class);

            try {
                $ref = new \ReflectionProperty('Database', 'instance');
                $ref->setAccessible(true);
                $ref->setValue(null, $this->pdoMock);
            } catch (\Exception $e) {
                $this->fail("Impossible d'injecter PDO dans Database: " . $e->getMessage());
            }

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

        protected function tearDown(): void
        {
            try {
                $ref = new \ReflectionProperty('Database', 'instance');
                $ref->setAccessible(true);
                $ref->setValue(null, null);
            } catch (\Exception $e) {
            }
        }

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
}
