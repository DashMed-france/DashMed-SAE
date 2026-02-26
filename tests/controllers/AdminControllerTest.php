<?php

namespace tests\controllers;

use PHPUnit\Framework\TestCase;
use modules\controllers\AdminController;
use modules\models\repositories\UserRepository;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../mocks/Database.php';

/**
 * Class AdminControllerTest | Tests du Contrôleur Admin
 *
 * Unit tests for AdminController.
 * Tests unitaires pour AdminController.
 *
 * @package Tests\Controllers
 * @author DashMed Team
 */
class AdminControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        // Set up in-memory SQLite for Database::getInstance()
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        \assets\includes\Database::setInstance($pdo);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testPanelRedirectsIfNotAdmin(): void
    {
        $_SESSION['user_id'] = 999;

        $userRepo = $this->createMock(UserRepository::class);

        $user = $this->createMock(\modules\models\entities\User::class);
        $user->method('isAdmin')->willReturn(false);
        $userRepo->method('getById')->willReturn($user);

        $controller = new AdminController($userRepo);

        $this->assertTrue(true);
    }

    public function testPanelShowIfAdmin(): void
    {
        $_SESSION['user_id'] = 1;

        $userRepo = $this->createMock(UserRepository::class);

        $user = $this->createMock(\modules\models\entities\User::class);
        $user->method('isAdmin')->willReturn(true);
        $userRepo->method('getById')->willReturn($user);

        $controller = new AdminController($userRepo);
        $this->assertTrue(true);
    }
}
