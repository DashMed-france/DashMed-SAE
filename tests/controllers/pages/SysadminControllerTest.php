<?php

namespace controllers\pages;

use modules\controllers\pages\SysadminController;
use modules\models\userModel;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le contrôleur SysadminController.
 *
 * @covers \modules\controllers\pages\SysadminController
 */
class SysadminControllerTest extends TestCase
{
    /**
     * Instance PDO pour la base de données SQLite en mémoire.
     *
     * @var \PDO
     */
    private \PDO $pdo;

    /**
     * Instance du modèle userModel.
     *
     * @var userModel
     */
    private userModel $model;

    /**
     * Configuration avant chaque test.
     * Crée une base SQLite en mémoire et initialise le modèle.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Créer la table users avec le bon schéma
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

        // Créer la table medical_specialties pour les tests
        $this->pdo->exec("
            CREATE TABLE medical_specialties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT
            )
        ");

        $this->model = new userModel($this->pdo);

        // Démarre la session si absente pour manipuler $_SESSION
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Réinitialise la session pour isoler les tests
        $_SESSION = [];
        $_POST = [];
    }

    /**
     * Crée un contrôleur de test qui évite l'appel à Database::getInstance().
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

                // Démarre la session si nécessaire
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    @session_start();
                }

                // Injecte directement les dépendances sans appeler parent::__construct()
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Compte créé avec succès pour jean.dupont@example.com', $_SESSION['success']);

        // Vérifier que l'utilisateur a été créé dans la base de données
        $user = $this->model->getByEmail('jean.dupont@example.com');
        $this->assertNotNull($user);
        $this->assertEquals('Jean', $user['first_name']);
        $this->assertEquals('Dupont', $user['last_name']);
    }

    /**
     * Teste l'échec de la création si l'email est déjà utilisé.
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
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Un compte existe déjà avec cet email.', $_SESSION['error']);
    }

    /**
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Le mot de passe doit contenir au moins 8 caractères.', $_SESSION['error']);
    }

    /**
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Les mots de passe ne correspondent pas.', $_SESSION['error']);
    }

    /**
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Email invalide.', $_SESSION['error']);
    }

    /**
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Tous les champs sont requis.', $_SESSION['error']);
    }

    /**
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

        try {
            $controller->get();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=login', $controller->redirectLocation);
    }

    /**
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

        try {
            $controller->get();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=login', $controller->redirectLocation);
    }

    /**
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Requête invalide. Réessaye.', $_SESSION['error']);
    }

    /**
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

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Compte créé avec succès pour admin@example.com', $_SESSION['success']);

        // Vérifier que l'utilisateur a été créé avec admin_status = 1
        $user = $this->model->getByEmail('admin@example.com');
        $this->assertNotNull($user);
        $this->assertEquals(1, (int)$user['admin_status']);
    }
}
