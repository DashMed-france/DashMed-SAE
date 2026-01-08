<?php

namespace controllers\pages;

use modules\controllers\pages\SysadminController;
use modules\models\userModel;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../app/controllers/pages/SysadminController.php';
require_once __DIR__ . '/../../../app/models/UserModel.php';

/**
 * Class TestableSysadminController
 *
 * Testable extension of SysadminController.
 * Extension testable de SysadminController.
 */
class TestableSysadminController extends SysadminController
{
    public string $redirectLocation = '';
    private $testModel;
    private $testPdo;

    public function __construct(userModel $model, \PDO $pdo)
    {
        // NO parent::__construct() call to avoid side effects
        // On n'appelle PAS parent::__construct() pour éviter les effets de bord
        $this->testModel = $model;
        $this->testPdo = $pdo;

        // Manually inject protected properties (using reflection if needed, but since we extend we can access protected? No, properties are protected in parent?)
        // Let's check parent. Assuming they are protected or we need reflection.
        // If they are private in parent, we can't set them directly. We'll use reflection in the test setup or here.
        // But since I was setting $this->model = $model in anon class, they must be protected or dynamic.
        // Actually, previous code did $this->model = $model.
        // Use reflection to be safe if visibility is issue.

        $ref = new \ReflectionClass(SysadminController::class);

        if ($ref->hasProperty('model')) {
            $p = $ref->getProperty('model');
            $p->setAccessible(true);
            $p->setValue($this, $model);
        } else {
            $this->model = $model; // Fallback dynamic
        }

        if ($ref->hasProperty('pdo')) {
            $p = $ref->getProperty('pdo');
            $p->setAccessible(true);
            $p->setValue($this, $pdo);
        } else {
            $this->pdo = $pdo; // Fallback dynamic
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    protected function redirect(string $location): void
    {
        $this->redirectLocation = $location;
    }

    protected function terminate(): void
    {
        throw new \RuntimeException('Exit called');
    }

    protected function getAllSpecialties(): array
    {
        return [];
    }

    // Public wrapper for testing if needed, or just leverage that we call public post/get
}

/**
 * Class SysadminControllerTest | Tests du Contrôleur Sysadmin
 *
 * Unit tests for SysadminController.
 * Tests unitaires pour SysadminController.
 *
 * @package Tests\Controllers\Pages
 * @author DashMed Team
 */
class SysadminControllerTest extends TestCase
{
    /**
     * PDO instance for memory SQLite database.
     * Instance PDO pour la base de données SQLite en mémoire.
     *
     * @var \PDO
     */
    private \PDO $pdo;

    /**
     * Model instance.
     * Instance du modèle userModel.
     *
     * @var userModel
     */
    private userModel $model;

    /**
     * Setup before each test.
     * Configuration avant chaque test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // CREATE users table with schema
        $this->pdo->exec("
            CREATE TABLE users (
                id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT,
                last_name TEXT,
                email TEXT UNIQUE,
                password TEXT,
                id_profession INTEGER,
                admin_status INTEGER DEFAULT 0
            )
        ");

        // Create medical_specialties table for tests
        $this->pdo->exec("
            CREATE TABLE medical_specialties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT
            )
        ");

        $this->model = new userModel($this->pdo);

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION = [];
        $_POST = [];
    }

    /**
     * Create a test controller avoiding Database::getInstance().
     * Crée un contrôleur de test qui évite l'appel à Database::getInstance().
     *
     * @return TestableSysadminController
     */
    private function createTestController(): TestableSysadminController
    {
        return new TestableSysadminController($this->model, $this->pdo);
    }

    /**
     * Helper to run controller action and catch exit exception.
     * Helper pour exécuter une action du contrôleur et attraper l'exception de sortie.
     */
    private function runControllerAction($controller, $action)
    {
        try {
            $controller->$action();
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'Exit called') {
                throw $e;
            }
        }
    }

    /**
     * Test valid user creation via POST.
     * Teste la création d'un nouvel utilisateur valide via POST.
     *
     * @covers ::post
     *
     * @return void
     */
    public function testPostCreatesNewUser(): void
    {
        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean.dupont@example.com',
            'password' => 'securePass123',
            'password_confirm' => 'securePass123',
            'profession_id' => null,
            'admin_status' => 0,
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = $this->createTestController();
        $this->runControllerAction($controller, 'post');

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Account created successfully for jean.dupont@example.com | Compte créé avec succès pour jean.dupont@example.com', $_SESSION['success']);

        // Check DB
        $user = $this->model->getByEmail('jean.dupont@example.com');
        $this->assertNotNull($user);
        $this->assertEquals('Jean', $user['first_name']);
        $this->assertEquals('Dupont', $user['last_name']);
    }

    /**
     * Test failure if email already exists.
     * Teste l'échec de la création si l'email est déjà utilisé.
     *
     * @covers ::post
     *
     * @return void
     */
    public function testPostFailsIfEmailAlreadyExists(): void
    {
        $this->pdo->prepare("
            INSERT INTO users (id_user, first_name, last_name, email, password, id_profession, admin_status)
            VALUES (1, 'Jean', 'Dupont', 'jean.dupont@example.com', 'hashedpass', null, 0)
        ")->execute();

        $this->assertNotNull(
            $this->model->getByEmail('jean.dupont@example.com'),
            'L\'utilisateur préexistant n\'a pas été inséré correctement.'
        );

        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean.dupont@example.com',
            'password' => 'securePass123',
            'password_confirm' => 'securePass123',
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = $this->createTestController();
        $this->runControllerAction($controller, 'post');

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Account already exists with this email. | Un compte existe déjà avec cet email.', $_SESSION['error']);
    }

    /**
     * Test failure if password is too short.
     * Teste l'échec de création si le mot de passe est trop court.
     *
     * @covers ::post
     *
     * @return void
     */
    public function testPostFailsIfPasswordTooShort(): void
    {
        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Alice',
            'last_name' => 'Martin',
            'email' => 'alice.martin@example.com',
            'password' => '12345',
            'password_confirm' => '12345',
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = $this->createTestController();
        $this->runControllerAction($controller, 'post');

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Password must be at least 8 chars. | Le mot de passe doit contenir au moins 8 caractères.', $_SESSION['error']);
    }

    /**
     * Test failure if passwords do not match.
     * Teste l'échec de création si les mots de passe ne correspondent pas.
     *
     * @covers ::post
     *
     * @return void
     */
    public function testPostFailsIfPasswordsDoNotMatch(): void
    {
        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Lucie',
            'last_name' => 'Durand',
            'email' => 'lucie.durand@example.com',
            'password' => 'SecurePass123',
            'password_confirm' => 'DifferentPass456',
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = $this->createTestController();
        $this->runControllerAction($controller, 'post');

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Passwords do not match. | Les mots de passe ne correspondent pas.', $_SESSION['error']);
    }

    /**
     * Test failure if email invalid.
     * Teste l'échec de création si l'email est invalide.
     *
     * @covers ::post
     *
     * @return void
     */
    public function testPostFailsIfEmailInvalid(): void
    {
        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Marc',
            'last_name' => 'Leroy',
            'email' => 'invalid-email-format',
            'password' => 'ValidPass123',
            'password_confirm' => 'ValidPass123',
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = $this->createTestController();
        $this->runControllerAction($controller, 'post');

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Invalid email. | Email invalide.', $_SESSION['error']);
    }

    /**
     * Test failure if required fields missing.
     * Teste l'échec si les champs requis sont manquants.
     *
     * @covers ::post
     *
     * @return void
     */
    public function testPostFailsIfRequiredFieldsMissing(): void
    {
        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Alice',
            'last_name' => '',
            'email' => 'alice@example.com',
            'password' => 'ValidPass123',
            'password_confirm' => 'ValidPass123',
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = $this->createTestController();
        $this->runControllerAction($controller, 'post');

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('All fields required. | Tous les champs sont requis.', $_SESSION['error']);
    }

    /**
     * Test get() redirects when user not logged in.
     * Teste que get() redirige si l'utilisateur n'est pas connecté.
     *
     * @covers ::get
     *
     * @return void
     */
    public function testGetRedirectsWhenUserNotLoggedIn(): void
    {
        unset($_SESSION['email']);
        unset($_SESSION['admin_status']);

        $controller = $this->createTestController();
        $this->runControllerAction($controller, 'get');

        $this->assertEquals('/?page=login', $controller->redirectLocation);
    }

    /**
     * Test get() redirects when user not admin.
     * Teste que get() redirige si l'utilisateur n'est pas admin.
     *
     * @covers ::get
     *
     * @return void
     */
    public function testGetRedirectsWhenUserNotAdmin(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $_SESSION['admin_status'] = 0;

        $controller = $this->createTestController();
        $this->runControllerAction($controller, 'get');

        $this->assertEquals('/?page=login', $controller->redirectLocation);
    }

    /**
     * Test CSRF token validation.
     * Teste la validation du token CSRF.
     *
     * @covers ::post
     *
     * @return void
     */
    public function testPostFailsWithInvalidCsrfToken(): void
    {
        $_POST = [
            '_csrf' => 'wrongtoken',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean.dupont@example.com',
            'password' => 'securePass123',
            'password_confirm' => 'securePass123',
        ];

        $_SESSION['_csrf'] = 'correcttoken';

        $controller = $this->createTestController();
        $this->runControllerAction($controller, 'post');

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Invalid request. Try again. | Requête invalide. Réessaye.', $_SESSION['error']);
    }

    /**
     * Test admin user creation.
     * Teste la création d'un utilisateur avec le statut admin.
     *
     * @covers ::post
     *
     * @return void
     */
    public function testPostCreatesAdminUser(): void
    {
        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => 'securePass123',
            'password_confirm' => 'securePass123',
            'profession_id' => null,
            'admin_status' => 1,
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = $this->createTestController();
        $this->runControllerAction($controller, 'post');

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Account created successfully for admin@example.com | Compte créé avec succès pour admin@example.com', $_SESSION['success']);

        // Check DB for admin status
        $user = $this->model->getByEmail('admin@example.com');
        $this->assertNotNull($user);
        $this->assertEquals(1, (int) $user['admin_status']);
    }
}
