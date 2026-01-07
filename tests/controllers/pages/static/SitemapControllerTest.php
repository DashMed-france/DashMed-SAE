<?php

namespace controllers\pages\static;

use modules\controllers\pages\static\SitemapController;
use modules\views\pages\static\sitemapView;
use PHPUnit\Framework\TestCase;

/**
 * Class SitemapControllerTest
 *
 * Unit tests for the SitemapController.
 * Verifies the behavior of get() and index() methods based on user session state.
 *
 * @coversDefaultClass \modules\controllers\pages\static\SitemapController
 */
class SitemapControllerTest extends TestCase
{
    /**
     * Configures the environment before each test.
     * Starts a session if necessary and resets $_SESSION.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    /**
     * Cleans up the environment after each test.
     * Resets $_SESSION.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    /**
     * Tests that the get() method shows the view when the user is not logged in.
     *
     * @covers ::get
     * @uses sitemapView::show
     *
     * @return void
     */
    public function testGet_ShowsViewWhenUserNotLoggedIn(): void
    {
        $mockView = $this->createMock(sitemapView::class);
        $mockView->expects($this->once())->method('show');

        $controller = new SitemapController($mockView);
        $controller->get();
    }

    /**
     * Tests that the get() method redirects to the dashboard when the user is logged in.
     *
     * @covers ::get
     *
     * @return void
     */
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

    /**
     * Tests that the index() method calls the get() method.
     *
     * @covers ::index
     * @uses ::get
     *
     * @return void
     */
    public function testIndex_CallsGet(): void
    {
        $mockView = $this->createMock(sitemapView::class);
        $mockView->expects($this->once())->method('show');

        $controller = new SitemapController($mockView);
        $controller->index();
    }
}