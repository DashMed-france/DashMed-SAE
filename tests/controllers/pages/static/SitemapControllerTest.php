<?php

namespace controllers\pages\static;

use modules\controllers\pages\static\SitemapController;
use modules\views\pages\static\sitemapView;
use PHPUnit\Framework\TestCase;

/**
 * Class SitemapControllerTest
 *
 * Tests unitaires pour le SitemapController.
 * Vérifie le comportement des méthodes get() et index() selon l'état de la session utilisateur.
 *
 * @coversDefaultClass \modules\controllers\pages\static\SitemapController
 */
class SitemapControllerTest extends TestCase
{
    /**
     * Configure l'environnement avant chaque test.
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
     * Nettoie l'environnement après chaque test.
     * Réinitialise $_SESSION.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    /**
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
