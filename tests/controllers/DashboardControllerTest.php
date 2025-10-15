<?php
declare(strict_types=1);

namespace controllers;

use PHPUnit\Framework\TestCase;

// 1) Charger le FAKE (et SURTOUT PAS la vraie vue)
require_once __DIR__ . '/../Fakes/DashboardView.php';

// 2) Charger le contrôleur réel
require_once __DIR__ . '/../../app/controllers/dashboardController.php';

use modules\controllers\DashboardController;
use app\views\dashboardView; // <- doit matcher le namespace du FAKE

final class DashboardControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() !== \PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];

        if (function_exists('header_remove')) {
            header_remove();
        }

        dashboardView::$wasShown = false;
        // Optionnel : buffer pour éviter "headers already sent"
        if (!ob_get_level()) {
            ob_start();
        }
    }

    protected function tearDown(): void
    {
        // Nettoie le buffer si utilisé
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function testGet_WhenUserLoggedIn_ShowsDashboardAndNoRedirect(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $controller = new dashboardController();

        $this->callControllerMethod($controller);

        $this->assertTrue(dashboardView::$wasShown, 'La vue doit être affichée si connecté.');
        $this->assertFalse($this->hasLocationHeader(), 'Aucune redirection attendue.');
    }

    public function testGet_WhenUserNotLoggedIn_RedirectsToLoginAndDoesNotShowView(): void
    {
        $controller = new dashboardController();

        $this->callControllerMethod($controller);

        $this->assertFalse(dashboardView::$wasShown, 'Vue non affichée sans connexion.');
        $this->assertSame('/?page=login', $this->getLocationHeader() ?? '', 'Redirection vers /?page=login attendue.');
    }

    private function callControllerMethod($controller): void
    {
        if (method_exists($controller, 'get')) {
            $controller->get();
            return;
        }
        if (method_exists($controller, 'index')) {
            $controller->index();
            return;
        }
        $this->fail('dashboardController ne possède ni get() ni index().');
    }

    private function hasLocationHeader(): bool
    {
        foreach (headers_list() as $h) {
            if (stripos($h, 'Location:') === 0) return true;
        }
        return false;
    }

    private function getLocationHeader(): ?string
    {
        foreach (headers_list() as $h) {
            if (stripos($h, 'Location:') === 0) {
                return trim(substr($h, strlen('Location:')));
            }
        }
        return null;
    }
}
