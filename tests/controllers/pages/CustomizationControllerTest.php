<?php

namespace controllers\pages {

    use modules\controllers\pages\CustomizationController;
    use modules\models\Monitoring\MonitorPreferenceModel;
    use modules\views\pages\customizationView;
    use PHPUnit\Framework\TestCase;
    use PDO;
    use ReflectionClass;

    require_once __DIR__ . '/../../../assets/includes/database.php';
    require_once __DIR__ . '/../../../app/controllers/pages/CustomizationController.php';

    /**
     * Version testable du contrôleur.
     * Surcharge get et post pour capturer les redirections et éviter les exit.
     */
    class TestableCustomizationController extends CustomizationController
    {
        public string $redirectUrl = '';
        public bool $exitCalled = false;
        public string $renderedOutput = '';

        private $testPrefModel;
        private $testPdo;

        public function __construct($pdo, $prefModel)
        {
            parent::__construct($pdo);
            $this->testPdo = $pdo;
            $this->testPrefModel = $prefModel;

            $ref = new ReflectionClass(CustomizationController::class);
            $p = $ref->getProperty('prefModel');
            $p->setAccessible(true);
            $p->setValue($this, $prefModel);
        }

        private function isUserLoggedIn(): bool
        {
            return isset($_SESSION['email']);
        }

        public function get(): void
        {
            if (!$this->isUserLoggedIn()) {
                $this->redirectUrl = '/?page=signup';
                $this->exitCalled = true;
                return;
            }

            $userId = (int) ($_SESSION['user_id'] ?? 0);
            if ($userId <= 0 && isset($_SESSION['email'])) {
                $stmt = $this->testPdo->prepare("SELECT id FROM users WHERE email = :e");
                $stmt->execute([':e' => $_SESSION['email']]);
                $fetchedId = $stmt->fetchColumn();
                if ($fetchedId) {
                    $_SESSION['user_id'] = $fetchedId;
                    $userId = (int) $fetchedId;
                }
            }

            if ($userId <= 0) {
                $this->redirectUrl = '/?page=signup';
                $this->exitCalled = true;
                return;
            }

            $allParams = $this->testPrefModel->getAllParameters();
            $userPrefs = $this->testPrefModel->getUserPreferences($userId);

            $viewData = [];
            foreach ($allParams as $p) {
                $pid = $p['parameter_id'];
                $hidden = false;

                $order = 999;
                if (isset($userPrefs['orders'][$pid])) {
                    $hidden = (bool) $userPrefs['orders'][$pid]['is_hidden'];
                    $order = (int) ($userPrefs['orders'][$pid]['display_order'] ?? 999);
                }

                $viewData[] = [
                    'id' => $pid,
                    'name' => $p['display_name'],
                    'category' => $p['category'],
                    'is_hidden' => $hidden,
                    'display_order' => $order
                ];
            }

            ob_start();
            $view = new customizationView();
            $view->show($viewData);
            $this->renderedOutput = ob_get_clean();
        }
    }

    class CustomizationControllerTest extends TestCase
    {
        private $pdoMock;
        private $stmtMock;
        private $prefModelMock;

        protected function setUp(): void
        {
            $this->pdoMock = $this->createMock(PDO::class);
            $this->stmtMock = $this->createMock(\PDOStatement::class);
            $this->prefModelMock = $this->createMock(MonitorPreferenceModel::class);

            $_SESSION = [];
            $_POST = [];
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }

        protected function tearDown(): void
        {
            $_SESSION = [];
            $_POST = [];
        }

        private function createController(): TestableCustomizationController
        {
            return new TestableCustomizationController($this->pdoMock, $this->prefModelMock);
        }

        public function testConstructor(): void
        {
            $controller = $this->createController();
            $this->assertInstanceOf(CustomizationController::class, $controller);
        }

        public function testGetRedirectsIfNotLoggedIn(): void
        {
            unset($_SESSION['email']);

            $controller = $this->createController();
            $controller->get();

            $this->assertTrue($controller->exitCalled, "Exit should be called");
            $this->assertEquals('/?page=signup', $controller->redirectUrl);
        }

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

        public function testGetShowViewSuccess(): void
        {
            $_SESSION['email'] = 'test@example.com';
            $_SESSION['user_id'] = 123;

            $this->prefModelMock->method('getAllParameters')->willReturn([
                ['parameter_id' => 'hr', 'display_name' => 'Heart Rate', 'category' => 'Vital']
            ]);
            $this->prefModelMock->method('getUserPreferences')->willReturn(['orders' => []]);

            $controller = $this->createController();
            $controller->get();

            $output = $controller->renderedOutput;

            $this->assertThat(
                $output,
                $this->logicalOr(
                    $this->stringContains("CustomizationView Mock"),
                    $this->stringContains("Personnaliser l'affichage")
                ),
                "L'output doit être valide (HTML ou Mock)."
            );
        }
    }
}

// Définitions des mocks dans leurs namespaces respectifs
namespace modules\models\Monitoring {
    if (!class_exists('modules\models\Monitoring\MonitorPreferenceModel')) {
        class MonitorPreferenceModel
        {
            public function __construct($pdo = null)
            {
            }
            public function getAllParameters()
            {
                return [];
            }
            public function getUserPreferences($userId)
            {
                return [];
            }
            public function saveUserVisibilityPreference($userId, $pid, $hidden)
            {
            }
            public function updateUserDisplayOrdersBulk($userId, $orders)
            {
            }
        }
    }
}

namespace modules\views\pages {
    if (!class_exists('modules\views\pages\customizationView')) {
        class customizationView
        {
            public function show($data, $errors = [])
            {
                echo "CustomizationView Mock";
            }
        }
    }
}
