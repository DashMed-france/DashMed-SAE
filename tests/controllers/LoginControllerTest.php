<?php

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use modules\controllers\LoginController;
use modules\models\loginModel;

/**
 * Tests unitaires pour le LoginController
 */
class LoginControllerTest extends TestCase
{
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Démarrer la session si elle n'est pas active
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Réinitialiser les variables globales
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        parent::tearDown();
    }

    /**
     * Test de la méthode get() - Affichage de la page de connexion
     */
    public function testGetShowsLoginPageWhenUserNotLoggedIn(): void
    {
        // Arrange
        unset($_SESSION['email']);

        // Act
        ob_start();
        $controller = new LoginController();
        $controller->get();
        $output = ob_get_clean();

        // Assert
        $this->assertNotEmpty($output, 'La vue devrait générer du contenu');
        $this->assertNotEmpty($_SESSION['_csrf'], 'Le token CSRF devrait être généré');
        $this->assertEquals(32, strlen($_SESSION['_csrf']), 'Le token CSRF devrait faire 32 caractères');
    }

    /**
     * Test de la méthode get() - Vérification de la logique de redirection
     */
    public function testGetChecksUserLoggedInStatus(): void
    {
        // Arrange
        $_SESSION['email'] = 'user@example.com';
        $controller = new LoginController();

        // Act & Assert
        $reflection = new \ReflectionMethod($controller, 'isUserLoggedIn');
        $reflection->setAccessible(true);
        $isLoggedIn = $reflection->invoke($controller);

        $this->assertTrue($isLoggedIn, 'L\'utilisateur devrait être considéré comme connecté');
    }

    /**
     * Test de post() - Erreur CSRF invalide
     */
    public function testPostFailsWithInvalidCsrfToken(): void
    {
        // Arrange
        $_SESSION['_csrf'] = 'valid_token';
        $_POST['_csrf'] = 'invalid_token';
        $_POST['email'] = 'test@example.com';
        $_POST['password'] = 'password123';

        $controller = new LoginController();

        // Act
        ob_start();
        try {
            $controller->post();
        } catch (\Exception $e) {
            // La redirection génère une exception dans les tests
        }
        ob_end_clean();

        // Assert
        $this->assertEquals("Requête invalide. Réessaye.", $_SESSION['error']);
    }

    /**
     * Test de post() - CSRF manquant dans POST
     */
    public function testPostFailsWithMissingCsrfInPost(): void
    {
        // Arrange
        $_SESSION['_csrf'] = 'valid_token';
        unset($_POST['_csrf']);
        $_POST['email'] = 'test@example.com';
        $_POST['password'] = 'password123';

        $controller = new LoginController();

        // Act
        ob_start();
        try {
            $controller->post();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }
        ob_end_clean();

        // Assert
        $this->assertEquals("Requête invalide. Réessaye.", $_SESSION['error']);
    }

    /**
     * Test de post() - Email vide
     */
    public function testPostFailsWithEmptyEmail(): void
    {
        // Arrange
        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        $_POST['email'] = '';
        $_POST['password'] = 'password123';

        $controller = new LoginController();

        // Act
        ob_start();
        try {
            $controller->post();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }
        ob_end_clean();

        // Assert
        $this->assertEquals("Email et mot de passe sont requis.", $_SESSION['error']);
    }

    /**
     * Test de post() - Mot de passe vide
     */
    public function testPostFailsWithEmptyPassword(): void
    {
        // Arrange
        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        $_POST['email'] = 'test@example.com';
        $_POST['password'] = '';

        $controller = new LoginController();

        // Act
        ob_start();
        try {
            $controller->post();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }
        ob_end_clean();

        // Assert
        $this->assertEquals("Email et mot de passe sont requis.", $_SESSION['error']);
    }

    /**
     * Test de post() - Email et mot de passe vides
     */
    public function testPostFailsWithBothFieldsEmpty(): void
    {
        // Arrange
        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        $_POST['email'] = '';
        $_POST['password'] = '';

        $controller = new LoginController();

        // Act
        ob_start();
        try {
            $controller->post();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }
        ob_end_clean();

        // Assert
        $this->assertEquals("Email et mot de passe sont requis.", $_SESSION['error']);
    }

    /**
     * Test de post() - Trimming des espaces dans l'email
     */
    public function testPostTrimsEmailWhitespace(): void
    {
        // Arrange
        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        $_POST['email'] = '  user@example.com  ';
        $_POST['password'] = 'password123';

        $controller = new LoginController();

        // Act
        ob_start();
        try {
            $controller->post();
        } catch (\Exception $e) {
            // Exception attendue pour la redirection
        }
        ob_end_clean();

        // Assert - Vérifier que l'erreur n'est pas "Email et mot de passe sont requis"
        // car l'email trimé ne devrait pas être vide
        $this->assertNotEquals("Email et mot de passe sont requis.", $_SESSION['error'] ?? null);
    }

    /**
     * Test de post() - Email avec uniquement des espaces
     */
    public function testPostFailsWithWhitespaceOnlyEmail(): void
    {
        // Arrange
        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        $_POST['email'] = '   ';
        $_POST['password'] = 'password123';

        $controller = new LoginController();

        // Act
        ob_start();
        try {
            $controller->post();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }
        ob_end_clean();

        // Assert
        $this->assertEquals("Email et mot de passe sont requis.", $_SESSION['error']);
    }

    /**
     * Test de la génération du token CSRF
     */
    public function testCsrfTokenIsGeneratedOnGet(): void
    {
        // Arrange
        unset($_SESSION['_csrf']);
        $controller = new LoginController();

        // Act
        ob_start();
        $controller->get();
        ob_end_clean();

        // Assert
        $this->assertArrayHasKey('_csrf', $_SESSION);
        $this->assertIsString($_SESSION['_csrf']);
        $this->assertEquals(32, strlen($_SESSION['_csrf']));
    }

    /**
     * Test de la persistance du token CSRF
     */
    public function testCsrfTokenIsNotRegeneratedIfExists(): void
    {
        // Arrange
        $_SESSION['_csrf'] = 'existing_token_1234567890123456';
        $controller = new LoginController();

        // Act
        ob_start();
        $controller->get();
        ob_end_clean();

        // Assert
        $this->assertEquals('existing_token_1234567890123456', $_SESSION['_csrf']);
    }

    /**
     * Test de logout() - Déconnexion complète
     */
    public function testLogoutDestroysSession(): void
    {
        // Arrange
        $_SESSION['user_id'] = 1;
        $_SESSION['email'] = 'user@example.com';
        $_SESSION['first_name'] = 'John';
        $_SESSION['last_name'] = 'Doe';

        $controller = new LoginController();

        // Act
        ob_start();
        try {
            $controller->logout();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }
        ob_end_clean();

        // Assert
        $this->assertEmpty($_SESSION, 'La session devrait être vide après logout');
    }

    /**
     * Test de isUserLoggedIn - Utilisateur non connecté
     */
    public function testIsUserLoggedInReturnsFalseWhenNotLoggedIn(): void
    {
        // Arrange
        unset($_SESSION['email']);
        $controller = new LoginController();

        // Act
        $reflection = new \ReflectionMethod($controller, 'isUserLoggedIn');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($controller);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test de isUserLoggedIn - Utilisateur connecté
     */
    public function testIsUserLoggedInReturnsTrueWhenLoggedIn(): void
    {
        // Arrange
        $_SESSION['email'] = 'user@example.com';
        $controller = new LoginController();

        // Act
        $reflection = new \ReflectionMethod($controller, 'isUserLoggedIn');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($controller);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test de isUserLoggedIn - Email vide
     */
    public function testIsUserLoggedInWithEmptyEmail(): void
    {
        // Arrange
        $_SESSION['email'] = '';
        $controller = new LoginController();

        // Act
        $reflection = new \ReflectionMethod($controller, 'isUserLoggedIn');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($controller);

        // Assert
        $this->assertTrue($result, 'isset() retourne true même pour une chaîne vide');
    }

    /**
     * Test de isUserLoggedIn - Email null
     */
    public function testIsUserLoggedInWithNullEmail(): void
    {
        // Arrange
        $_SESSION['email'] = null;
        $controller = new LoginController();

        // Act
        $reflection = new \ReflectionMethod($controller, 'isUserLoggedIn');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($controller);

        // Assert
        $this->assertFalse($result, 'isset() retourne false pour null');
    }

    /**
     * Test du casting du password en string
     */
    public function testPostCastsPasswordToString(): void
    {
        // Arrange
        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        $_POST['email'] = 'test@example.com';
        $_POST['password'] = ['array_value']; // Type invalide

        $controller = new LoginController();

        // Act
        ob_start();
        try {
            $controller->post();
        } catch (\Exception $e) {
            // Exception attendue
        }
        ob_end_clean();

        // Assert - Le cast en string transforme l'array en "Array"
        // qui n'est pas vide, donc la validation passe mais les credentials sont invalides
        $this->assertNotEmpty($_SESSION['error']);
    }

    /**
     * Test de post() - Champs manquants dans $_POST
     */
    public function testPostHandlesMissingFields(): void
    {
        // Arrange
        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        // email et password non définis

        $controller = new LoginController();

        // Act
        ob_start();
        try {
            $controller->post();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }
        ob_end_clean();

        // Assert
        $this->assertEquals("Email et mot de passe sont requis.", $_SESSION['error']);
    }
}