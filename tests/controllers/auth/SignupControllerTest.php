<?php

namespace controllers\auth;

use Exception;
use modules\controllers\auth\SignupController;
use modules\models\userModel;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Throwable;

/**
 * Class SignupControllerTest
 *
 * Tests unitaires pour le contrôleur SignupController.
 * Vérifie la logique de création d'utilisateur via la méthode POST.
 *
 * @coversDefaultClass \modules\controllers\auth\SignupController
 */
class SignupControllerTest extends TestCase
{
    /**
     * Instance PDO pour la base de données SQLite en mémoire.
     *
     * @var PDO
     */
    private PDO $pdo;

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
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create professions table
        $this->pdo->exec("
            CREATE TABLE professions (
                id_profession INTEGER PRIMARY KEY AUTOINCREMENT,
                label_profession TEXT NOT NULL
            )
        ");

        // Insert test professions
        $this->pdo->exec("
            INSERT INTO professions (id_profession, label_profession) VALUES
            (1, 'Médecin'),
            (2, 'Infirmier'),
            (3, 'Pharmacien')
        ");

        // Create users table with correct schema
        $this->pdo->exec("
            CREATE TABLE users (
                id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT,
                last_name TEXT,
                email TEXT UNIQUE,
                password TEXT,
                profession_id INTEGER,
                admin_status INTEGER,
                birth_date TEXT,
                created_at TEXT,
                FOREIGN KEY (profession_id) REFERENCES professions(id_profession)
            )
        ");

        $this->model = new userModel($this->pdo);

        // Nettoyage de la session
        $_SESSION = [];
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
            'profession_id' => '1',
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $testPdo = $this->pdo;
        $testModel = $this->model;

        $controller = new class($testModel, $testPdo) extends SignupController {
            public string $redirectLocation = '';
            private PDO $testPdo;

            public function __construct(userModel $model, PDO $pdo)
            {
                $this->testPdo = $pdo;
                
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    @session_start();
                }

                $reflection = new ReflectionClass(parent::class);

                $modelProperty = $reflection->getProperty('model');
                $modelProperty->setAccessible(true);
                $modelProperty->setValue($this, $model);

                $pdoProperty = $reflection->getProperty('pdo');
                $pdoProperty->setAccessible(true);
                $pdoProperty->setValue($this, $pdo);
            }

            protected function redirect(string $location): void {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void {
                throw new class extends Exception {};
            }
        };

        try {
            $controller->post();
        } catch (Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        // Successful signup should redirect to homepage, not signup page
        $this->assertEquals('/?page=homepage', $controller->redirectLocation);
        $this->assertEquals('jean.dupont@example.com', $_SESSION['email']);
        $this->assertEquals('Jean', $_SESSION['first_name']);
        $this->assertEquals('Dupont', $_SESSION['last_name']);
        $this->assertEquals(1, $_SESSION['profession_id']);
        $this->assertEquals(0, $_SESSION['admin_status']);
        
        // Verify user was actually created in database
        $user = $this->model->getByEmail('jean.dupont@example.com');
        $this->assertNotNull($user, 'User should exist in database after signup');
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
            INSERT INTO users (id_user, first_name, last_name, email, password, profession_id, admin_status)
            VALUES (1, 'Jean', 'Dupont', 'jean.dupont@example.com', 'hashedpass', 1, 0)
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
            'profession_id' => '1',
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $testPdo = $this->pdo;
        $testModel = $this->model;

        $controller = new class($testModel, $testPdo) extends SignupController {
            public string $redirectLocation = '';
            private PDO $testPdo;

            public function __construct(userModel $model, PDO $pdo)
            {
                $this->testPdo = $pdo;
                
                // Start session if needed
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    @session_start();
                }

                // Use reflection to inject dependencies without calling parent constructor
                $reflection = new ReflectionClass(parent::class);

                $modelProperty = $reflection->getProperty('model');
                $modelProperty->setAccessible(true);
                $modelProperty->setValue($this, $model);

                $pdoProperty = $reflection->getProperty('pdo');
                $pdoProperty->setAccessible(true);
                $pdoProperty->setValue($this, $pdo);
            }

            protected function redirect(string $location): void {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void {
                throw new class extends Exception {};
            }
        };

        try {
            $controller->post();
        } catch (Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
        $this->assertEquals('Un compte existe déjà avec cet email.', $_SESSION['error']);
        $this->assertEquals('jean.dupont@example.com', $_SESSION['old_signup']['email']);
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
            'profession_id' => '1',
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = new class($this->model) extends SignupController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void {
                throw new class extends Exception {};
            }
        };

        try {
            $controller->post();
        } catch (Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
        $this->assertEquals('Le mot de passe doit contenir au moins 8 caractères.', $_SESSION['error']);
        $this->assertEquals('alice.martin@example.com', $_SESSION['old_signup']['email']);
        $this->assertEquals('Alice', $_SESSION['old_signup']['first_name']);
        $this->assertEquals('Martin', $_SESSION['old_signup']['last_name']);
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
            'profession_id' => '1',
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = new class($this->model) extends SignupController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void {
                throw new class extends Exception {};
            }
        };

        try {
            $controller->post();
        } catch (Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
        $this->assertEquals('Les mots de passe ne correspondent pas.', $_SESSION['error']);
        $this->assertEquals('lucie.durand@example.com', $_SESSION['old_signup']['email']);
        $this->assertEquals('Lucie', $_SESSION['old_signup']['first_name']);
        $this->assertEquals('Durand', $_SESSION['old_signup']['last_name']);
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
            'profession_id' => '1',
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = new class($this->model) extends SignupController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void {
                throw new class extends Exception {};
            }
        };

        try {
            $controller->post();
        } catch (Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
        $this->assertEquals('Email invalide.', $_SESSION['error']);
        $this->assertEquals('invalid-email-format', $_POST['email']);
    }

    /**
     * Teste l'échec de création si la profession n'est pas sélectionnée.
     *
     * @covers ::post
     *
     * @return void
     */
    public function testPostFailsIfProfessionNotSelected(): void
    {
        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Sophie',
            'last_name' => 'Bernard',
            'email' => 'sophie.bernard@example.com',
            'password' => 'SecurePass123',
            'password_confirm' => 'SecurePass123',
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = new class($this->model) extends SignupController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void {
                throw new class extends Exception {};
            }
        };

        try {
            $controller->post();
        } catch (Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
        $this->assertEquals('Merci de sélectionner une spécialité.', $_SESSION['error']);
        $this->assertEquals('sophie.bernard@example.com', $_SESSION['old_signup']['email']);
    }
}
