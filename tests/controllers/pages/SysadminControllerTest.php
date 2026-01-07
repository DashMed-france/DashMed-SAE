<?php

namespace controllers\pages;

use modules\controllers\pages\SysadminController;
use modules\models\userModel;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SysadminController.
 *
 * @covers \modules\controllers\pages\SysadminController
 */
class SysadminControllerTest extends TestCase
{
    /**
     * PDO instance for the in-memory SQLite database.
     *
     * @var \PDO
     */
    private \PDO $pdo;

    /**
     * Instance of the userModel.
     *
     * @var userModel
     */
    private userModel $model;

    /**
     * Setup before each test.
     * Creates an in-memory SQLite database and initializes the model.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE users (
                id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT,
                last_name TEXT,
                email TEXT UNIQUE,
                password TEXT,
                profession TEXT,
                admin_status INTEGER DEFAULT 0
            )
        ");

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
     * Creates a test controller that avoids calling Database::getInstance().
     *
     * @return SysadminController
     */
    private function createTestController(): SysadminController
    {
        return new class ($this->model, $this->pdo) extends SysadminController {
            public string $redirectLocation = '';
            private \PDO $testPdo;
            private userModel $testModel;

            public function __construct(userModel $model, \PDO $pdo)
            {
                $this->testModel = $model;
                $this->testPdo = $pdo;

                if (session_status() !== PHP_SESSION_ACTIVE) {
                    @session_start();
                }

                $reflection = new \ReflectionClass(parent::class);

                $modelProperty = $reflection->getProperty('model');
                $modelProperty->setAccessible(true);
                $modelProperty->setValue($this, $model);

                $pdoProperty = $reflection->getProperty('pdo');
                $pdoProperty->setAccessible(true);
                $pdoProperty->setValue($this, $pdo);
            }

            protected function redirect(string $location): void
            {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void
            {
                throw new class extends \Exception {
                };
            }

            protected function getAllSpecialties(): array
            {
                return [];
            }
        };
    }

    /**
     * Tests the creation of a new valid user via POST.
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Compte créé avec succès pour jean.dupont@example.com', $_SESSION['success']);

        $user = $this->model->getByEmail('jean.dupont@example.com');
        $this->assertNotNull($user);
        $this->assertEquals('Jean', $user['first_name']);
        $this->assertEquals('Dupont', $user['last_name']);
    }

    /**
     * Tests creation failure if the email is already in use.
     *
     * @covers ::post
     *
     * @return void
     */
    public function testPostFailsIfEmailAlreadyExists(): void
    {
        $this->pdo->prepare("
            INSERT INTO users (id_user, first_name, last_name, email, password, profession, admin_status)
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Un compte existe déjà avec cet email.', $_SESSION['error']);
    }

    /**
     * Tests creation failure if the password is too short.
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Le mot de passe doit contenir au moins 8 caractères.', $_SESSION['error']);
    }

    /**
     * Tests creation failure if passwords do not match.
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Les mots de passe ne correspondent pas.', $_SESSION['error']);
    }

    /**
     * Tests creation failure if the email is invalid.
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Email invalide.', $_SESSION['error']);
    }

    /**
     * Tests failure if required fields are missing.
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Tous les champs sont requis.', $_SESSION['error']);
    }

    /**
     * Tests that get() redirects if the user is not logged in.
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

        try {
            $controller->get();
        } catch (\Throwable $e) {
        }

        $this->assertEquals('/?page=login', $controller->redirectLocation);
    }

    /**
     * Tests that get() redirects if the user is not an admin.
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

        try {
            $controller->get();
        } catch (\Throwable $e) {
        }

        $this->assertEquals('/?page=login', $controller->redirectLocation);
    }

    /**
     * Tests CSRF token validation.
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Requête invalide. Réessaye.', $_SESSION['error']);
    }

    /**
     * Tests the creation of a user with admin status.
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Compte créé avec succès pour admin@example.com', $_SESSION['success']);

        $user = $this->model->getByEmail('admin@example.com');
        $this->assertNotNull($user);
        $this->assertEquals(1, (int)$user['admin_status']);
    }
}