<?php

namespace controllers\auth;

use modules\controllers\auth\LoginController;
use modules\models\userModel;
use modules\views\auth\LoginView;
use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionMethod;
use const PHP_SESSION_NONE;

require_once __DIR__ . '/../../../app/controllers/auth/LoginController.php';
require_once __DIR__ . '/../../../app/models/UserModel.php';
require_once __DIR__ . '/../../../app/views/auth/LoginView.php';

/**
 * Login Controller PHPUnit Tests
 * ---------------------------------
 * These tests validate the behavior of the `LoginController`
 * under real session conditions and simulated HTTP requests.
 *
 * @package   DashMed\Tests
 * @author    DashMed Team
 */
class LoginControllerTest extends TestCase
{
    /**
     * Prepares a clean environment before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION = [];
        $_POST    = [];

        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Cleans up global variables after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST    = [];
        parent::tearDown();
    }

    /**
     * Checks that `get()` displays the login page when the user is not logged in.
     *
     * @return void
     */
    public function testGet_ShowsLoginPage_WhenNotLoggedIn(): void
    {
        unset($_SESSION['email']);

        ob_start();
        $controller = new LoginController();
        $controller->get();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'La vue devrait générer du contenu');
        $this->assertStringContainsString('Se connecter', $output, 'La page devrait contenir le titre "Se connecter"');
    }

    /**
     * Checks that `get()` generates a unique CSRF token in the session.
     *
     * @return void
     */
    public function testGet_GeneratesCsrfToken(): void
    {
        unset($_SESSION['_csrf']);

        ob_start();
        (new LoginController())->get();
        ob_end_clean();

        $this->assertArrayHasKey('_csrf', $_SESSION, 'Le token CSRF doit être présent dans la session');
        $this->assertIsString($_SESSION['_csrf'], 'Le token CSRF doit être une chaîne');
        $this->assertSame(32, strlen($_SESSION['_csrf']), 'Le token CSRF doit faire 32 caractères');
    }

    /**
     * Checks that `isUserLoggedIn()` returns true when session email is set.
     *
     * @return void
     */
    public function testIsUserLoggedIn_ReturnsTrue_WhenEmailSet(): void
    {
        $_SESSION['email'] = 'user@example.com';

        $controller = new LoginController();

        $ref = new ReflectionMethod($controller, 'isUserLoggedIn');
        $ref->setAccessible(true);

        $this->assertTrue($ref->invoke($controller), 'Devrait retourner true quand email est défini');
    }
}