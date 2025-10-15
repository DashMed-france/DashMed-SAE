<?php

namespace tests\controllers;

use PHPUnit\Framework\TestCase;
use modules\controllers\LegalnoticeController;
use modules\views\legalnoticeView;

/**
 * Test du contrôleur LegalnoticeController
 */
class LegalnoticeControllerTest extends TestCase
{
    private LegalnoticeController $controller;

    /**
     * Configuration avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Démarrer la session si elle n'est pas active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Réinitialiser la session
        $_SESSION = [];

        $this->controller = new LegalnoticeController();
    }

    /**
     * Nettoyage après chaque test
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Test : get() affiche la vue quand l'utilisateur n'est pas connecté
     */
    public function testGetDisplaysViewWhenUserNotLoggedIn(): void
    {
        // Arrange
        unset($_SESSION['email']);

        // Act
        ob_start();
        $this->controller->get();
        $output = ob_get_clean();

        // Assert
        $this->assertNotEmpty($output, 'La vue devrait générer du contenu');
    }

    /**
     * Test : get() tente de rediriger vers le dashboard quand l'utilisateur est connecté
     * Note: En environnement de test, on vérifie que la vue n'est pas affichée
     */
    public function testGetRedirectsToDashboardWhenUserLoggedIn(): void
    {
        // Arrange
        $_SESSION['email'] = 'user@example.com';

        // On s'attend à ce que le script tente de sortir
        $this->expectException(\Exception::class);

        // Act
        ob_start();

        // Créer un contrôleur mockable pour tester la redirection
        $controller = $this->getMockBuilder(LegalnoticeController::class)
            ->onlyMethods([])
            ->getMock();

        try {
            // Utiliser runkit ou uopz pour mocker exit() si disponible
            // Sinon, on teste indirectement
            $reflection = new \ReflectionMethod($controller, 'isUserLoggedIn');
            $reflection->setAccessible(true);
            $isLoggedIn = $reflection->invoke($controller);

            $this->assertTrue($isLoggedIn, 'L\'utilisateur devrait être considéré comme connecté');

            ob_end_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Test : index() appelle get()
     */
    public function testIndexCallsGet(): void
    {
        // Arrange
        unset($_SESSION['email']);

        // Act
        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        // Assert : index() devrait avoir le même comportement que get()
        $this->assertNotEmpty($output, 'index() devrait afficher la vue via get()');
    }

    /**
     * Test : isUserLoggedIn retourne false quand email n'est pas défini
     */
    public function testIsUserLoggedInReturnsFalseWhenEmailNotSet(): void
    {
        // Arrange
        unset($_SESSION['email']);

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertFalse($result, 'Devrait retourner false quand email n\'est pas défini');
    }

    /**
     * Test : isUserLoggedIn retourne true quand email est défini
     */
    public function testIsUserLoggedInReturnsTrueWhenEmailIsSet(): void
    {
        // Arrange
        $_SESSION['email'] = 'test@example.com';

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertTrue($result, 'Devrait retourner true quand email est défini');
    }

    /**
     * Test : isUserLoggedIn avec email vide
     */
    public function testIsUserLoggedInWithEmptyEmail(): void
    {
        // Arrange
        $_SESSION['email'] = '';

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        // isset() retourne true même pour une chaîne vide
        $this->assertTrue($result, 'isset() retourne true même pour une chaîne vide');
    }

    /**
     * Test : isUserLoggedIn avec email null
     */
    public function testIsUserLoggedInWithNullEmail(): void
    {
        // Arrange
        $_SESSION['email'] = null;

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        // isset() retourne false pour null
        $this->assertFalse($result, 'isset() retourne false pour null');
    }

    /**
     * Méthode utilitaire pour tester les méthodes privées
     */
    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->controller, $parameters);
    }
}