<?php

namespace controllers\auth;

require_once __DIR__ . '/../../../tests/mocks/functions.php';

use modules\controllers\auth\LogoutController;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function session_start;
use function session_status;
use const PHP_SESSION_NONE;

realpath(__DIR__ . '/../../../modules/controllers/auth/LogoutController.php');

/**
 * Class LogoutControllerTest | Tests du Contrôleur de Déconnexion
 *
 * Unit tests for LogoutController.
 * Tests unitaires pour LogoutController.
 *
 * Validates session destruction and redirection.
 * Valide la destruction de session et la redirection.
 *
 * @package Tests\Controllers\Auth
 * @author DashMed Team
 */
final class LogoutControllerTest extends TestCase
{
    /**
     * Setup test environment.
     * Configuration de l'environnement de test.
     *
     * Starts session if needed and clears headers/globals.
     * Démarre une session si nécessaire et efface les en-têtes/globaux.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION = [];
        $_POST = [];
    }

    /**
     * Test successful logout.
     * Test de déconnexion réussie.
     *
     * Verifies session destruction and redirection to homepage.
     * Vérifie la destruction de la session et la redirection vers l'accueil.
     */
    public function testGetDestroysSessionAndRedirectsToHomepage(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $_SESSION['role'] = 'doctor';

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
     * Test logout without active session.
     * Test de déconnexion sans session active.
     */
    public function testGetWorksWithoutPreStartedSession(): void
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
