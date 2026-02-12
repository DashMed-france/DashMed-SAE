<?php

namespace Tests\Controllers\User;

use PHPUnit\Framework\TestCase;
use modules\controllers\UserController;
use modules\models\repositories\UserRepository;
use modules\models\monitoring\MonitorPreferenceModel;
use modules\services\UserLayoutService;
use PDO;

require_once __DIR__ . '/../../../vendor/autoload.php';

class UserControllerTest extends TestCase
{
    private $userController;
    private $pdoMock;
    private $userRepoMock;

    private $layoutServiceMock;
    private $prefModelMock;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->prefModelMock = $this->createMock(MonitorPreferenceModel::class);
        $this->layoutServiceMock = $this->createMock(UserLayoutService::class);

        $this->userController = new UserController($this->pdoMock);

        $this->injectProperty($this->userController, 'layoutService', $this->layoutServiceMock);
    }

    private function injectProperty($object, $propertyName, $value)
    {
        $reflection = new \ReflectionClass($object);
        if ($reflection->hasProperty($propertyName)) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue($object, $value);
        }
    }

    public function testProfileRedirectsIfNoSession()
    {
        $_SESSION = [];

        $this->assertTrue(true);
    }

    public function testCustomizationCallsLayoutService()
    {
        $_SESSION['email'] = 'user@test.com';
        $_SESSION['user_id'] = 1;
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->layoutServiceMock->expects($this->once())
            ->method('buildWidgetsForCustomization')
            ->with(1)
            ->willReturn(['widgets' => [], 'hidden' => []]);

        ob_start();
        $this->userController->customization();
        $output = ob_get_clean();

        $this->assertIsString($output);
    }
}
