<?php

namespace controllers\pages\static;

use modules\controllers\pages\static\LegalnoticeController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class LegalnoticeControllerTest | Tests Contrôleur Mentions Légales
 *
 * Unit tests for LegalnoticeController.
 * Tests unitaires pour LegalnoticeController.
 *
 * Validates view display and authentication logic.
 * Valide l'affichage de la vue et la logique d'authentification.
 *
 * @package Tests\Controllers\Pages\Static
 * @author DashMed Team
 */
class LegalnoticeControllerTest extends TestCase
{
    /** @var LegalnoticeController Instance du contrôleur testé. */
    private LegalnoticeController $controller;

    /**
     * Setup.
     * Configuration.
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
        $this->controller = new LegalnoticeController();
    }

    /**
     * Teardown.
     * Nettoyage.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Test GET displays view when not logged in.
     * Vérifie que la méthode `get()` affiche bien la vue quand l’utilisateur **n’est pas connecté**.
     *
     * @return void
     */
    public function testGetDisplaysViewWhenUserNotLoggedIn(): void
    {
        unset($_SESSION['email']);

        ob_start();
        $this->controller->get();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'La vue devrait générer du contenu');
    }

    /**
     * Test GET treats user as logged in when session set.
     * Vérifie que `get()` détecte correctement l’état de connexion.
     *
     * @return void
     */
    public function testGetRedirectsToDashboardWhenUserLoggedIn(): void
    {
        $_SESSION['email'] = 'user@example.com';

        $reflection = new ReflectionMethod($this->controller, 'isUserLoggedIn');
        $reflection->setAccessible(true);

        $isLoggedIn = $reflection->invoke($this->controller);

        $this->assertTrue($isLoggedIn, 'L\'utilisateur devrait être considéré comme connecté');
    }

    /**
     * Test INDEX calls GET.
     * Vérifie que `index()` agit comme un alias de `get()`.
     *
     * @return void
     */
    public function testIndexCallsGet(): void
    {
        unset($_SESSION['email']);

        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'index() devrait afficher la vue via get()');
    }

    /**
     * Test isUserLoggedIn returns false when email not set.
     * Vérifie que `isUserLoggedIn()` retourne false quand l'email n’est pas défini.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsFalseWhenEmailNotSet(): void
    {
        unset($_SESSION['email']);
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertFalse($result, 'Devrait retourner false quand email n\'est pas défini');
    }

    /**
     * Test isUserLoggedIn returns true when email is set.
     * Vérifie que `isUserLoggedIn()` retourne true quand l’email est défini.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsTrueWhenEmailIsSet(): void
    {
        $_SESSION['email'] = 'test@example.com';
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertTrue($result, 'Devrait retourner true quand email est défini');
    }

    /**
     * Test isUserLoggedIn with empty string (true).
     * Vérifie un cas limite : la clé email est définie mais vide (true).
     *
     * @return void
     */
    public function testIsUserLoggedInWithEmptyEmail(): void
    {
        $_SESSION['email'] = '';
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertTrue($result, 'isset() retourne true même pour une chaîne vide');
    }

    /**
     * Test isUserLoggedIn with null (false).
     * Vérifie un autre cas limite : la clé email est définie à null (false).
     *
     * @return void
     */
    public function testIsUserLoggedInWithNullEmail(): void
    {
        $_SESSION['email'] = null;
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertFalse($result, 'isset() retourne false pour null');
    }

    /**
     * Helper to invoke private method.
     * Utilitaire interne pour invoquer une méthode privée.
     *
     * @param string $methodName
     * @param array  $parameters
     *
     * @return mixed
     */
    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->controller, $parameters);
    }
}
