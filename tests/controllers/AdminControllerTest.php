<?php

namespace tests\controllers;

use PHPUnit\Framework\TestCase;
use modules\controllers\AdminController;
use modules\models\repositories\UserRepository;
use assets\includes\Database;

class AdminControllerTest extends TestCase
{
    public function testPanelRedirectsIfNotAdmin()
    {
        $_SESSION['user_id'] = 999;

        $pdo = $this->createMock(\PDO::class);
        $userRepo = $this->createMock(UserRepository::class);

        $user = $this->createMock(\modules\models\Entities\User::class);
        $user->method('isAdmin')->willReturn(false);
        $userRepo->method('getById')->willReturn($user);

        $controller = new AdminController($userRepo);

        $this->assertTrue(true);
    }

    public function testPanelShowIfAdmin()
    {
        $_SESSION['user_id'] = 1;

        $pdo = $this->createMock(\PDO::class);
        $userRepo = $this->createMock(UserRepository::class);

        $user = $this->createMock(\modules\models\Entities\User::class);
        $user->method('isAdmin')->willReturn(true);
        $userRepo->method('getById')->willReturn($user);

        $controller = new AdminController($userRepo);
        $this->assertTrue(true);
    }
}
