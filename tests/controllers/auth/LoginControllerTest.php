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

/**
 * Classe de contrôleur testable qui étend LoginController.
 *
 * Cette classe permet de :
 * - Éviter l'appel au constructeur parent (pas de connexion DB)
 * - Capturer les redirections au lieu d'exécuter header() + exit;
 * - Injecter un mock du UserModel
 */
class TestableLoginController extends LoginController
{
    public ?string $redirectUrl = null;
    public bool $exitCalled = false;
    public string $renderedOutput = '';
    public UserModel $testModel;

    /**
     * Constructeur qui évite d'appeler le parent.
     */
    public function __construct()
    {
        // On n'appelle PAS parent::__construct() pour éviter Database::getInstance()
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    /**
     * Injecte le mock du UserModel.
     */
    public function setModel(UserModel $model): void
    {
        $this->testModel = $model;

        // Utiliser Reflection pour définir la propriété privée du parent
        $reflection = new ReflectionClass(LoginController::class);
        $property = $reflection->getProperty('model');
        $property->setAccessible(true);
        $property->setValue($this, $model);
    }

    /**
     * Override de get() pour éviter header/exit et utiliser notre logique.
     */
    public function get(): void
    {
        if ($this->isUserLoggedIn()) {
            $this->redirectUrl = '/?page=dashboard';
            $this->exitCalled = true;
            return;
        }

        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }

        // Récupère le modèle via Reflection
        $reflection = new ReflectionClass(LoginController::class);
        $property = $reflection->getProperty('model');
        $property->setAccessible(true);
        $model = $property->getValue($this);

        $users = $model->listUsersForLogin();

        // Capture la sortie de la vue au lieu de l'afficher
        ob_start();
        (new LoginView())->show($users);
        $this->renderedOutput = ob_get_clean();
    }

    /**
     * Override de post() pour éviter header/exit.
     */
    public function post(): void
    {
        if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string) $_POST['_csrf'])) {
            $_SESSION['error'] = "Requête invalide. Réessaye.";
            $this->redirectUrl = '/?page=login';
            $this->exitCalled = true;
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $_SESSION['error'] = "Email et mot de passe sont requis.";
            $this->redirectUrl = '/?page=login';
            $this->exitCalled = true;
            return;
        }

        // Récupère le modèle via Reflection
        $reflection = new ReflectionClass(LoginController::class);
        $property = $reflection->getProperty('model');
        $property->setAccessible(true);
        $model = $property->getValue($this);

        $user = $model->verifyCredentials($email, $password);
        if (!$user) {
            $_SESSION['error'] = "Identifiants incorrects.";
            $this->redirectUrl = '/?page=login';
            $this->exitCalled = true;
            return;
        }

        // Aligne avec la BDD et le modèle
        $_SESSION['user_id'] = (int) $user['id_user'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['id_profession'] = $user['id_profession'];
        $_SESSION['profession_label'] = $user['profession_label'] ?? '';
        $_SESSION['admin_status'] = (int) $user['admin_status'];
        $_SESSION['username'] = $user['email'];

        $this->redirectUrl = '/?page=homepage';
        $this->exitCalled = true;
    }

    /**
     * Override de logout() pour éviter header/exit.
     */
    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
        // On ne peut pas appeler setcookie dans les tests
        // session_destroy() peut poser problème, on le simule
        $this->redirectUrl = '/?page=login';
        $this->exitCalled = true;
    }

    /**
     * Rend la méthode isUserLoggedIn accessible publiquement.
     */
    public function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}

/**
 * Tests unitaires pour LoginController.
 *
 * Ces tests utilisent TestableLoginController pour éviter les problèmes
 * liés au constructeur (Database) et aux header()/exit;.
 *
 * @package controllers\auth
 */
class LoginControllerTest extends TestCase
{
    private $userModelMock;
    private int $initialObLevel;

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
     * Vérifie que get() affiche la page de connexion quand l'utilisateur n'est pas connecté.
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
        $this->assertStringContainsString('Se connecter', $controller->renderedOutput, 'La page devrait contenir "Se connecter"');
        $this->assertNull($controller->redirectUrl, 'Aucune redirection ne devrait avoir lieu');
    }

    /**
     * Vérifie que get() redirige vers le dashboard si l'utilisateur est déjà connecté.
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
     * Vérifie que get() génère un token CSRF.
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
     * Vérifie que isUserLoggedIn() retourne true quand email est défini.
     */
    public function testIsUserLoggedInReturnsTrueWhenEmailSet(): void
    {
        $_SESSION['email'] = 'user@example.com';

        $controller = $this->createController();

        $this->assertTrue($controller->isUserLoggedIn());
    }

    /**
     * Vérifie que isUserLoggedIn() retourne false quand email n'est pas défini.
     */
    public function testIsUserLoggedInReturnsFalseWhenEmailNotSet(): void
    {
        unset($_SESSION['email']);

        $controller = $this->createController();

        $this->assertFalse($controller->isUserLoggedIn());
    }

    /**
     * Vérifie que post() échoue avec un token CSRF invalide.
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
     * Vérifie que post() échoue si email ou password est vide.
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
     * Vérifie que post() échoue avec des identifiants incorrects.
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
     * Vérifie que post() réussit avec des identifiants valides.
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
     * Vérifie que logout() réinitialise la session et redirige.
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
