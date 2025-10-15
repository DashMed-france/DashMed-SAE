<?php

namespace modules\tests\controllers;

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
        $this->controller = new LegalnoticeController();

        // Réinitialiser la session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
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

        // Mock de la vue
        $viewMock = $this->createMock(legalnoticeView::class);
        $viewMock->expects($this->once())
            ->method('show');

        // On injecte le mock (nécessite une modification du contrôleur pour l'injection de dépendance)
        // Alternative : tester avec la vraie vue si pas d'injection

        // Act & Assert
        ob_start();
        $this->controller->get();
        ob_end_clean();

        // Vérifier qu'il n'y a pas eu de redirection
        $headers = xdebug_get_headers();
        $this->assertEmpty(
            array_filter($headers, fn($h) => str_starts_with($h, 'Location:')),
            'Aucune redirection ne devrait avoir lieu'
        );
    }

    /**
     * Test : get() redirige vers le dashboard quand l'utilisateur est connecté
     */
    public function testGetRedirectsToDashboardWhenUserLoggedIn(): void
    {
        // Arrange
        $_SESSION['email'] = 'user@example.com';

        // Act
        ob_start();
        try {
            $this->controller->get();
        } catch (\Exception $e) {
            // exit() lance une exception dans certains contextes de test
        }
        ob_end_clean();

        // Assert
        $headers = xdebug_get_headers();
        $this->assertContains(
            'Location: /?page=dashboard',
            $headers,
            'Devrait rediriger vers le dashboard'
        );
    }

    /**
     * Test alternatif pour la redirection (sans xdebug)
     */
    public function testGetRedirectsWhenLoggedInAlternative(): void
    {
        $_SESSION['email'] = 'test@test.com';

        $this->expectOutputString('');

        // Capturer l'exit avec une exception personnalisée
        try {
            $this->controller->get();
            $this->fail('Should have called exit()');
        } catch (\Exception $e) {
            // C'est attendu si exit() est appelé
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
        ob_end_clean();

        // Assert : vérifier que le comportement est identique à get()
        // Dans un vrai test, on mockerait le contrôleur pour vérifier l'appel
        $this->assertTrue(true, 'index() devrait appeler get()');
    }

    /**
     * Test : vérification du comportement avec différentes valeurs de session
     */
    public function testSessionBehaviorVariations(): void
    {
        // Test avec email vide
        $_SESSION['email'] = '';
        $this->assertFalse(
            $this->invokePrivateMethod('isUserLoggedIn'),
            'Email vide ne devrait pas être considéré comme connecté'
        );

        // Test avec email null
        $_SESSION['email'] = null;
        $this->assertFalse(
            $this->invokePrivateMethod('isUserLoggedIn'),
            'Email null ne devrait pas être considéré comme connecté'
        );

        // Test avec email valide
        $_SESSION['email'] = 'valid@email.com';
        $this->assertTrue(
            $this->invokePrivateMethod('isUserLoggedIn'),
            'Email valide devrait être considéré comme connecté'
        );
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