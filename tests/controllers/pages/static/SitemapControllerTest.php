<?php

namespace controllers\pages\static;

use modules\controllers\pages\static\SitemapController;
use modules\views\pages\static\sitemapView;
use PHPUnit\Framework\TestCase;

/**
 * Class SitemapControllerTest | Tests Contrôleur Plan du Site
 *
 * Unit tests for SitemapController.
 * Tests unitaires pour le SitemapController.
 *
 * Checks get methods according to user session state.
 * Vérifie le comportement des méthodes get() selon l'état de la session utilisateur.
 *
 * @coversDefaultClass \modules\controllers\pages\static\SitemapController
 * @package Tests\Controllers\Pages\Static
 * @author DashMed Team
 */
class SitemapControllerTest extends TestCase
{
    /**
     * Setup test environment.
     * Configure l'environnement avant chaque test.
     *
     * Starts session if needed and resets $_SESSION.
     * Démarre une session si nécessaire et réinitialise $_SESSION.
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
     * Teardown test environment.
     * Nettoie l'environnement après chaque test.
     *
     * Resets $_SESSION.
     * Réinitialise $_SESSION.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    /**
     * Test GET shows view when user not logged in.
     * Teste que la méthode get() affiche la vue lorsque l'utilisateur n'est pas connecté.
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
     * Test GET redirects when user logged in.
     * Teste que la méthode get() redirige vers le dashboard lorsque l'utilisateur est connecté.
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
     * Test INDEX calls GET.
     * Teste que la méthode index() appelle la méthode get().
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
