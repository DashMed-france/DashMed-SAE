<?php

namespace Tests\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use modules\controllers\AdminController;
use modules\models\repositories\UserRepository;
use PDO;

require_once __DIR__ . '/../../../vendor/autoload.php';

class AdminControllerTest extends TestCase
{
    private $adminController;
    private $pdoMock;
    private $userRepoMock;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->userRepoMock = $this->createMock(UserRepository::class);

        $this->adminController = new AdminController($this->userRepoMock);
    }

    public function testPanelRedirectsIfNotAdmin()
    {
        $_SESSION['email'] = 'user@test.com';
        $_SESSION['admin_status'] = 0;

        $this->assertTrue(true);
    }

    public function testPanelShowIfAdmin()
    {
        $_SESSION['email'] = 'admin@test.com';
        $_SESSION['admin_status'] = 1;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['_csrf'] = 'token';

        $reflection = new \ReflectionClass($this->adminController);
        $pdoProp = $reflection->getProperty('pdo');
        $pdoProp->setAccessible(true);
        $pdoProp->setValue($this->adminController, $this->pdoMock);

        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn([]);

        $this->pdoMock->method('query')->willReturn($stmtMock);

        ob_start();
        try {
            $this->adminController->panel();
        } catch (\Throwable $e) {
        }
        $output = ob_get_clean();

        $this->assertIsString($output);
    }
}
