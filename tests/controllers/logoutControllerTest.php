<?php
declare(strict_types=1);

namespace modules\controllers;

function header(string $string, bool $replace = true, ?int $code = null): void
{
    throw new \RuntimeException('REDIRECT:' . $string);
}

namespace modules\tests\controllers;

use PHPUnit\Framework\TestCase;
use modules\controllers\logoutController;


realpath(__DIR__ . '/../../modules/controllers/logoutController.php');

final class LogoutControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (\session_status() === \PHP_SESSION_NONE) {
            @\session_start();
        }

        $_SESSION = [];
        $_POST    = [];
    }

    public function testGet_DestroysSession_And_RedirectsToHomepage(): void
    {
        // Arrange
        $_SESSION['email'] = 'user@example.com';
        $_SESSION['role']  = 'doctor';

        $controller = new logoutController();

        try {
            $controller->get();
            $this->fail('Une redirection était attendue');
        } catch (\RuntimeException $e) {
            // Vérifie que la redirection est bonne
            $this->assertStringContainsString(
                'REDIRECT:Location: /?page=homepage',
                $e->getMessage(),
                'La redirection attendue est absente.'
            );

            $this->assertSame([], $_SESSION, 'La session doit être vide après logout.');
        }
    }

    public function testGet_Works_WithoutPreStartedSession(): void
    {
        $_SESSION = [];

        $controller = new logoutController();

        try {
            $controller->get();
            $this->fail('Une redirection était attendue');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString(
                'REDIRECT:Location: /?page=homepage',
                $e->getMessage(),
                'La redirection attendue est absente.'
            );

            $this->assertSame([], $_SESSION, 'La session doit rester vide.');
        }
    }
}
