<?php

namespace controllers\auth;

use modules\controllers\auth\LoginController;
use modules\models\UserModel;
use modules\views\auth\LoginView;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once __DIR__ . '/../../../app/controllers/auth/LoginController.php';
require_once __DIR__ . '/../../../app/models/UserModel.php';
require_once __DIR__ . '/../../../app/views/auth/LoginView.php';

require_once __DIR__ . '/../../mocks/controllers/TestableLoginController.php';

/**
 * Class LoginControllerTest | Tests du Contrôleur de Connexion
 *
 * Unit tests for LoginController.
 * Tests unitaires pour LoginController.
 *
 * @package Tests\Controllers\Auth
 * @author DashMed Team
 */
class LoginControllerTest extends TestCase
{
    private $userModelMock;
    private int $initialObLevel;

    /**
     * Setup.
     * Configuration.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->initialObLevel = ob_get_level();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Teardown.
     * Nettoyage.
     */
    protected function tearDown(): void
    {
        while (ob_get_level() > $this->initialObLevel) {
            ob_end_clean();
        }

        $_SESSION = [];
        $_POST = [];
        parent::tearDown();
    }

    private function createController(): TestableLoginController
    {
        $this->userModelMock = $this->createMock(UserModel::class);
        $controller = new TestableLoginController();
        $controller->setModel($this->userModelMock);
        return $controller;
    }

    /**
     * Test GET shows login page when not logged in.
     * Teste que GET affiche la page de connexion si non connecté.
     */
    public function testGetShowsLoginPageWhenNotLoggedIn(): void
    {
        unset($_SESSION['email']);

        $this->userModelMock = $this->createMock(UserModel::class);
        $this->userModelMock->expects($this->once())
            ->method('listUsersForLogin')
            ->willReturn([
                ['id_user' => 1, 'email' => 'test@test.com', 'first_name' => 'Jean', 'last_name' => 'Dupont']
            ]);

        $controller = new TestableLoginController();
        $controller->setModel($this->userModelMock);
        $controller->get();

        $this->assertNotEmpty($controller->renderedOutput, 'La vue devrait générer du contenu');
        $this->assertStringContainsString(
            'Se connecter',
            $controller->renderedOutput,
            'La page devrait contenir "Se connecter"'
        );
        $this->assertNull($controller->redirectUrl, 'Aucune redirection ne devrait avoir lieu');
    }

    /**
     * Test GET redirects if already logged in.
     * Teste que GET redirige si déjà connecté.
     */
    public function testGetRedirectsToDashboardWhenLoggedIn(): void
    {
        $_SESSION['email'] = 'user@example.com';

        $controller = $this->createController();
        $controller->get();

        $this->assertEquals('/?page=dashboard', $controller->redirectUrl);
        $this->assertTrue($controller->exitCalled);
    }

    /**
     * Test CSRF token generation.
     * Test de génération du token CSRF.
     */
    public function testGetGeneratesCsrfToken(): void
    {
        unset($_SESSION['_csrf']);
        unset($_SESSION['email']);

        $this->userModelMock = $this->createMock(UserModel::class);
        $this->userModelMock->method('listUsersForLogin')->willReturn([]);

        $controller = new TestableLoginController();
        $controller->setModel($this->userModelMock);
        $controller->get();

        $this->assertArrayHasKey('_csrf', $_SESSION, 'Le token CSRF doit être présent');
        $this->assertIsString($_SESSION['_csrf']);
        $this->assertSame(32, strlen($_SESSION['_csrf']), 'Le token CSRF doit faire 32 caractères');
    }

    /**
     * Test isUserLoggedIn returns true.
     * Teste que isUserLoggedIn retourne true.
     */
    public function testIsUserLoggedInReturnsTrueWhenEmailSet(): void
    {
        $_SESSION['email'] = 'user@example.com';

        $controller = $this->createController();

        $this->assertTrue($controller->isUserLoggedIn());
    }

    /**
     * Test isUserLoggedIn returns false.
     * Teste que isUserLoggedIn retourne false.
     */
    public function testIsUserLoggedInReturnsFalseWhenEmailNotSet(): void
    {
        unset($_SESSION['email']);

        $controller = $this->createController();

        $this->assertFalse($controller->isUserLoggedIn());
    }

    /**
     * Test POST failure with invalid CSRF.
     * Teste l'échec du POST avec un CSRF invalide.
     */
    public function testPostFailsWithInvalidCsrf(): void
    {
        $_SESSION['_csrf'] = 'valid_token';
        $_POST['_csrf'] = 'invalid_token';
        $_POST['email'] = 'user@example.com';
        $_POST['password'] = 'password123';

        $controller = $this->createController();
        $controller->post();

        $this->assertEquals('/?page=login', $controller->redirectUrl);
        $this->assertEquals('Requête invalide. Réessaye.', $_SESSION['error']);
    }

    /**
     * Test POST failure with empty credentials.
     * Teste l'échec du POST avec identifiants vides.
     */
    public function testPostFailsWithEmptyCredentials(): void
    {
        $_SESSION['_csrf'] = 'valid_token';
        $_POST['_csrf'] = 'valid_token';
        $_POST['email'] = '';
        $_POST['password'] = '';

        $controller = $this->createController();
        $controller->post();

        $this->assertEquals('/?page=login', $controller->redirectUrl);
        $this->assertEquals('Email et mot de passe sont requis.', $_SESSION['error']);
    }

    /**
     * Test POST failure with invalid credentials.
     * Teste l'échec du POST avec identifiants incorrects.
     */
    public function testPostFailsWithInvalidCredentials(): void
    {
        $_SESSION['_csrf'] = 'valid_token';
        $_POST['_csrf'] = 'valid_token';
        $_POST['email'] = 'user@example.com';
        $_POST['password'] = 'wrongpassword';

        $this->userModelMock = $this->createMock(UserModel::class);
        $this->userModelMock->expects($this->once())
            ->method('verifyCredentials')
            ->with('user@example.com', 'wrongpassword')
            ->willReturn(null);

        $controller = new TestableLoginController();
        $controller->setModel($this->userModelMock);
        $controller->post();

        $this->assertEquals('/?page=login', $controller->redirectUrl);
        $this->assertEquals('Identifiants incorrects.', $_SESSION['error']);
    }

    /**
     * Test POST success with valid credentials.
     * Teste le succès du POST avec identifiants valides.
     */
    public function testPostSucceedsWithValidCredentials(): void
    {
        $_SESSION['_csrf'] = 'valid_token';
        $_POST['_csrf'] = 'valid_token';
        $_POST['email'] = 'admin@example.com';
        $_POST['password'] = 'correctpassword';

        $mockUser = [
            'id_user' => 42,
            'email' => 'admin@example.com',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'id_profession' => 15,
            'profession_label' => 'Médecin généraliste',
            'admin_status' => 1
        ];

        $this->userModelMock = $this->createMock(UserModel::class);
        $this->userModelMock->expects($this->once())
            ->method('verifyCredentials')
            ->with('admin@example.com', 'correctpassword')
            ->willReturn($mockUser);

        $controller = new TestableLoginController();
        $controller->setModel($this->userModelMock);
        $controller->post();

        $this->assertEquals('/?page=homepage', $controller->redirectUrl);
        $this->assertEquals(42, $_SESSION['user_id']);
        $this->assertEquals('admin@example.com', $_SESSION['email']);
        $this->assertEquals('Admin', $_SESSION['first_name']);
        $this->assertEquals('User', $_SESSION['last_name']);
        $this->assertEquals(15, $_SESSION['id_profession']);
        $this->assertEquals(1, $_SESSION['admin_status']);
    }

    /**
     * Test logout clears session and redirects.
     * Teste que la déconnexion vide la session et redirige.
     */
    public function testLogoutClearsSessionAndRedirects(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $_SESSION['user_id'] = 1;

        $controller = $this->createController();
        $controller->logout();

        $this->assertEquals('/?page=login', $controller->redirectUrl);
        $this->assertTrue($controller->exitCalled);
        $this->assertEmpty($_SESSION);
    }
}
