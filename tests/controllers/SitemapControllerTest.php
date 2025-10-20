<?php

namespace tests\controllers;

use PHPUnit\Framework\TestCase;
use modules\controllers\SitemapController;
use modules\views\sitemapView;

class SitemapControllerTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testGet_ShowsViewWhenUserNotLoggedIn(): void
    {
        $mockView = $this->createMock(sitemapView::class);
        $mockView->expects($this->once())->method('show');

        $controller = new SitemapController($mockView);
        $controller->get();
    }

    public function testGet_RedirectsWhenUserLoggedIn(): void
    {
        $_SESSION['email'] = 'test@example.com';

        $mockView = $this->createMock(sitemapView::class);
        $mockView->expects($this->never())->method('show');

        $controller = $this->getMockBuilder(SitemapController::class)
            ->setConstructorArgs([$mockView])
            ->onlyMethods(['redirect'])
            ->getMock();

        $controller->expects($this->once())
            ->method('redirect')
            ->with('/?page=dashboard');

        $controller->get();
    }

    public function testIndex_CallsGet(): void
    {
        $mockView = $this->createMock(sitemapView::class);
        $mockView->expects($this->once())->method('show');

        $controller = new SitemapController($mockView);
        $controller->index();
    }
}