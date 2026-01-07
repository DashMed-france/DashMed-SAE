<?php
declare(strict_types=1);

namespace modules\controllers\auth;

use RuntimeException;

/**
 * Redefinition of the PHP `header()` function for testing purposes.
 * * Prevents actual HTTP redirections and throws an exception instead.
 */
function header(string $string, bool $replace = true, ?int $code = null): void
{
    throw new RuntimeException('REDIRECT:' . $string);
}

namespace controllers\auth;

use modules\controllers\auth\LogoutController;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function session_start;
use function session_status;
use const PHP_SESSION_NONE;

realpath(__DIR__ . '/../../../modules/controllers/auth/LogoutController.php');

/**
 * Logout Controller PHPUnit Tests
 * ----------------------------------
 * Validates the logout process by ensuring session destruction and proper redirection.
 */
final class LogoutControllerTest extends TestCase
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
    }

    /**
     * Checks that `get()` destroys the session and redirects to the homepage.
     *
     * @return void
     */
    public function testGet_DestroysSession_And_RedirectsToHomepage(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $_SESSION['role']  = 'doctor';

        $controller = new LogoutController();

        try {
            $controller->get();

            $this->fail('Une redirection était attendue');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(
                'REDIRECT:Location: /?page=homepage',
                $e->getMessage(),
                'La redirection attendue est absente.'
            );

            $this->assertSame([], $_SESSION, 'La session doit être vide après logout.');
        }
    }

    /**
     * Checks that `get()` works properly even without a pre-existing session.
     *
     * @return void
     */
    public function testGet_Works_WithoutPreStartedSession(): void
    {
        $_SESSION = [];

        $controller = new LogoutController();

        try {
            $controller->get();

            $this->fail('Une redirection était attendue');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(
                'REDIRECT:Location: /?page=homepage',
                $e->getMessage(),
                'La redirection attendue est absente.'
            );

            $this->assertSame([], $_SESSION, 'La session doit rester vide.');
        }
    }
}