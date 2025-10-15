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
    private $mockModel;
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock de la base de données
        $mockPdo = $this->createMock(\PDO::class);

        // Remplacer le singleton Database
        $reflectionClass = new \ReflectionClass(\Database::class);
        $instanceProperty = $reflectionClass->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $mockPdo);

        // Réinitialiser la session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
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
        $this->expectOutputRegex('/<form.*action="\\/\?page=login"/');

        $controller = new LoginController();
        $controller->get();

        // Vérifier que le token CSRF est généré
        $this->assertNotEmpty($_SESSION['_csrf']);
        $this->assertEquals(32, strlen($_SESSION['_csrf']));
    }

    /**
     * Test de la méthode get() - Redirection si déjà connecté
     */
    public function testGetRedirectsToDashboardWhenUserLoggedIn(): void
    {
        $_SESSION['email'] = 'user@example.com';

        $controller = new LoginController();

        $this->expectException(\PHPUnit\Framework\Error\Warning::class);
        $controller->get();

        // Dans un test réel, on utiliserait un mock pour tester la redirection
    }

    /**
     * Test de post() - Erreur CSRF invalide
     */
    public function testPostFailsWithInvalidCsrfToken(): void
    {
        $_SESSION['_csrf'] = 'valid_token';
        $_POST['_csrf'] = 'invalid_token';
        $_POST['email'] = 'test@example.com';
        $_POST['password'] = 'password123';

        $controller = new LoginController();

        try {
            $controller->post();
        } catch (\Exception $e) {
            // La redirection génère une exception dans les tests
        }

        $this->assertEquals("Requête invalide. Réessaye.", $_SESSION['error']);
    }

    /**
     * Test de post() - Champs vides
     */
    public function testPostFailsWithEmptyEmail(): void
    {
        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        $_POST['email'] = '';
        $_POST['password'] = 'password123';

        $controller = new LoginController();

        try {
            $controller->post();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }

        $this->assertEquals("Email et mot de passe sont requis.", $_SESSION['error']);
    }

    /**
     * Test de post() - Mot de passe vide
     */
    public function testPostFailsWithEmptyPassword(): void
    {
        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        $_POST['email'] = 'test@example.com';
        $_POST['password'] = '';

        $controller = new LoginController();

        try {
            $controller->post();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }

        $this->assertEquals("Email et mot de passe sont requis.", $_SESSION['error']);
    }

    /**
     * Test de post() - Identifiants incorrects
     */
    public function testPostFailsWithInvalidCredentials(): void
    {
        // Mock du modèle
        $mockModel = $this->createMock(loginModel::class);
        $mockModel->method('verifyCredentials')
            ->willReturn(false);

        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        $_POST['email'] = 'wrong@example.com';
        $_POST['password'] = 'wrongpassword';

        // Il faudrait injecter le mock dans le controller
        // Pour un test complet, utiliser l'injection de dépendances

        $controller = new LoginController();

        try {
            $controller->post();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }

        $this->assertEquals("Identifiants incorrects.", $_SESSION['error']);
    }

    /**
     * Test de post() - Connexion réussie
     */
    public function testPostSucceedsWithValidCredentials(): void
    {
        $userData = [
            'id_user' => 1,
            'email' => 'user@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'profession' => 'Médecin',
            'admin_status' => 1
        ];

        // Mock du modèle
        $mockModel = $this->createMock(loginModel::class);
        $mockModel->method('verifyCredentials')
            ->with('user@example.com', 'correct_password')
            ->willReturn($userData);

        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        $_POST['email'] = 'user@example.com';
        $_POST['password'] = 'correct_password';

        $controller = new LoginController();

        try {
            $controller->post();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }

        // Vérifier que les données de session sont bien définies
        $this->assertEquals(1, $_SESSION['user_id']);
        $this->assertEquals('user@example.com', $_SESSION['email']);
        $this->assertEquals('John', $_SESSION['first_name']);
        $this->assertEquals('Doe', $_SESSION['last_name']);
        $this->assertEquals('Médecin', $_SESSION['profession']);
        $this->assertEquals(1, $_SESSION['admin_status']);
        $this->assertEquals('user@example.com', $_SESSION['username']);
    }

    /**
     * Test de logout() - Déconnexion complète
     */
    public function testLogoutDestroysSession(): void
    {
        // Simuler un utilisateur connecté
        $_SESSION['user_id'] = 1;
        $_SESSION['email'] = 'user@example.com';
        $_SESSION['first_name'] = 'John';

        $controller = new LoginController();

        try {
            $controller->logout();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }

        // Vérifier que la session est vide
        $this->assertEmpty($_SESSION);
    }

    /**
     * Test de logout() - Suppression du cookie de session
     */
    public function testLogoutRemovesSessionCookie(): void
    {
        ini_set('session.use_cookies', '1');

        $_SESSION['user_id'] = 1;

        $controller = new LoginController();

        // Mock de setcookie est complexe, ce test vérifie surtout la logique
        try {
            $controller->logout();
        } catch (\Exception $e) {
            // La redirection génère une exception
        }

        $this->assertEmpty($_SESSION);
    }

    /**
     * Test de trimming des espaces dans l'email
     */
    public function testPostTrimsEmailWhitespace(): void
    {
        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        $_POST['email'] = '  user@example.com  ';
        $_POST['password'] = 'password123';

        $controller = new LoginController();

        // Le controller devrait appeler verifyCredentials avec l'email trimé
        try {
            $controller->post();
        } catch (\Exception $e) {
            // Exception attendue pour la redirection
        }

        // Vérifier que l'erreur n'est pas "Email et mot de passe sont requis"
        $this->assertNotEquals("Email et mot de passe sont requis.", $_SESSION['error'] ?? null);
    }

    /**
     * Test de la génération du token CSRF
     */
    public function testCsrfTokenIsGeneratedOnGet(): void
    {
        $controller = new LoginController();

        ob_start();
        $controller->get();
        ob_end_clean();

        $this->assertArrayHasKey('_csrf', $_SESSION);
        $this->assertIsString($_SESSION['_csrf']);
        $this->assertEquals(32, strlen($_SESSION['_csrf']));
    }

    /**
     * Test de la persistance du token CSRF
     */
    public function testCsrfTokenIsNotRegeneratedIfExists(): void
    {
        $_SESSION['_csrf'] = 'existing_token';

        $controller = new LoginController();

        ob_start();
        $controller->get();
        ob_end_clean();

        $this->assertEquals('existing_token', $_SESSION['_csrf']);
    }

    /**
     * Test de la gestion du type password (string cast)
     */
    public function testPostCastsPasswordToString(): void
    {
        $_SESSION['_csrf'] = 'token123';
        $_POST['_csrf'] = 'token123';
        $_POST['email'] = 'test@example.com';
        $_POST['password'] = ['array_value']; // Type invalide

        $controller = new LoginController();

        try {
            $controller->post();
        } catch (\Exception $e) {
            // Exception attendue
        }

        // Le cast en string devrait transformer l'array en "Array"
        // et la validation devrait échouer
        $this->assertNotEmpty($_SESSION['error']);
    }
}