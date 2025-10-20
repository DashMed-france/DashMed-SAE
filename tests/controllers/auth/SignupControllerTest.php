<?php

namespace controllers;

use PHPUnit\Framework\TestCase;
use modules\controllers\SignupController;
use modules\models\userModel;

/**
 * Class SigninControllerTest
 *
 * Tests unitaires pour le contrôleur SigninController.
 * Vérifie la logique de création d'utilisateur via la méthode POST.
 *
 * @coversDefaultClass \modules\controllers\SignupController
 */
class SigninControllerTest extends TestCase
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
                admin_status INTEGER
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
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = new class($this->model) extends SigninController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void {
                throw new class extends \Exception {};
            }
        };

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=homepage', $controller->redirectLocation);
        $this->assertEquals('jean.dupont@example.com', $_SESSION['email']);
        $this->assertEquals('Jean', $_SESSION['first_name']);
        $this->assertEquals('Dupont', $_SESSION['last_name']);
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

        $controller = new class($this->model) extends SigninController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void {
                throw new class extends \Exception {};
            }
        };

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=signin', $controller->redirectLocation);
        $this->assertEquals('Un compte existe déjà avec cet email.', $_SESSION['error']);
        $this->assertEquals('jean.dupont@example.com', $_SESSION['old_signin']['email']);
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

        $controller = new class($this->model) extends SigninController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void {
                throw new class extends \Exception {};
            }
        };

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l’exception de terminate()
        }

        $this->assertEquals('/?page=signin', $controller->redirectLocation);
        $this->assertEquals('Le mot de passe doit contenir au moins 8 caractères.', $_SESSION['error']);
        $this->assertEquals('alice.martin@example.com', $_SESSION['old_signin']['email']);
        $this->assertEquals('Alice', $_SESSION['old_signin']['first_name']);
        $this->assertEquals('Martin', $_SESSION['old_signin']['last_name']);
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

        $controller = new class($this->model) extends SigninController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void {
                throw new class extends \Exception {};
            }
        };

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l’exception de terminate()
        }

        $this->assertEquals('/?page=signin', $controller->redirectLocation);
        $this->assertEquals('Les mots de passe ne correspondent pas.', $_SESSION['error']);
        $this->assertEquals('lucie.durand@example.com', $_SESSION['old_signin']['email']);
        $this->assertEquals('Lucie', $_SESSION['old_signin']['first_name']);
        $this->assertEquals('Durand', $_SESSION['old_signin']['last_name']);
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

        $controller = new class($this->model) extends SigninController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void {
                throw new class extends \Exception {};
            }
        };

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l’exception de terminate()
        }

        $this->assertEquals('/?page=signin', $controller->redirectLocation);
        $this->assertEquals('Email invalide.', $_SESSION['error']);
        $this->assertEquals('invalid-email-format', $_POST['email']);
        $this->assertArrayNotHasKey('old_signin', $_SESSION, 'old_signin ne devrait pas être défini pour une erreur de format email');
        $this->assertArrayNotHasKey('old_signin', $_SESSION);
    }
}
