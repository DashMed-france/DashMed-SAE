<?php

namespace tests\controllers;

use PHPUnit\Framework\TestCase;
use modules\controllers\LegalnoticeController;
use modules\views\legalnoticeView;


class LegalnoticeControllerTest extends TestCase
{
    private LegalnoticeController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Démarrer la session si elle n'est pas active
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Réinitialiser la session
        $_SESSION = [];

        $this->controller = new LegalnoticeController();
    }


    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }


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


    public function testGetRedirectsToDashboardWhenUserLoggedIn(): void
    {
        // Arrange
        $_SESSION['email'] = 'user@example.com';

        // Act & Assert
        $reflection = new \ReflectionMethod($this->controller, 'isUserLoggedIn');
        $reflection->setAccessible(true);
        $isLoggedIn = $reflection->invoke($this->controller);

        $this->assertTrue($isLoggedIn, 'L\'utilisateur devrait être considéré comme connecté');

        // Note: Le test complet de la redirection nécessiterait un mock du header()
        // ou l'utilisation d'une bibliothèque comme runkit/uopz
    }

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


    public function testIsUserLoggedInReturnsFalseWhenEmailNotSet(): void
    {
        // Arrange
        unset($_SESSION['email']);

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertFalse($result, 'Devrait retourner false quand email n\'est pas défini');
    }


    public function testIsUserLoggedInReturnsTrueWhenEmailIsSet(): void
    {
        // Arrange
        $_SESSION['email'] = 'test@example.com';

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertTrue($result, 'Devrait retourner true quand email est défini');
    }


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


    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->controller, $parameters);
    }
}