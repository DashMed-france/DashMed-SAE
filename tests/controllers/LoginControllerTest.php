<?php

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use modules\controllers\LoginController;

class LoginControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() === \PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
        $_POST    = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST    = [];
        parent::tearDown();
    }

    /** Affiche la page de login quand pas connecté (et pas de redirection) */
    public function testGet_ShowsLoginPage_WhenNotLoggedIn(): void
    {
        unset($_SESSION['email']);

        ob_start();
        $controller = new LoginController();
        $controller->get();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'La vue devrait générer du contenu');
    }

    /** Génère un token CSRF de 32 caractères à l’appel de get() */
    public function testGet_GeneratesCsrfToken(): void
    {
        unset($_SESSION['_csrf']);

        ob_start();
        (new LoginController())->get();
        ob_end_clean();

        $this->assertArrayHasKey('_csrf', $_SESSION);
        $this->assertIsString($_SESSION['_csrf']);
        $this->assertSame(32, strlen($_SESSION['_csrf']));
    }

    /** isUserLoggedIn() -> false si pas de session email */
    public function testIsUserLoggedIn_ReturnsFalse_WhenNoEmail(): void
    {
        unset($_SESSION['email']);
        $controller = new LoginController();

        $ref = new \ReflectionMethod($controller, 'isUserLoggedIn');
        $ref->setAccessible(true);
        $this->assertFalse($ref->invoke($controller));
    }

    /** isUserLoggedIn() -> true si email en session */
    public function testIsUserLoggedIn_ReturnsTrue_WhenEmailSet(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $controller = new LoginController();

        $ref = new \ReflectionMethod($controller, 'isUserLoggedIn');
        $ref->setAccessible(true);
        $this->assertTrue($ref->invoke($controller));
    }
}
