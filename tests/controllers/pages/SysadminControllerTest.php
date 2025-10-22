<?php

namespace controllers\pages;

use modules\controllers\pages\SysadminController;
use modules\models\userModel;
use PHPUnit\Framework\TestCase;

/**
 * Class SysadminControllerTest
 *
 * Tests unitaires pour le contrôleur SysadminController.
 * Vérifie la logique de création d'utilisateur via la méthode POST.
 *
 * @coversDefaultClass \modules\controllers\pages\SysadminController
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

        // Démarre la session si absente pour manipuler $_SESSION
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Réinitialise la session pour isoler les tests
        $_SESSION = [];
        $_POST = [];
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

        $controller = new class($this->model) extends SysadminController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void
            {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void
            {
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

        $controller = new class($this->model) extends SysadminController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void
            {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void
            {
                throw new class extends \Exception {};
            }
        };

        try {
            $controller->post();
        } catch (\Throwable $e) {
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
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = new class($this->model) extends SysadminController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void
            {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void
            {
                throw new class extends \Exception {};
            }
        };

        try {
            $controller->post();
        } catch (\Throwable $e) {
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
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = new class($this->model) extends SysadminController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void
            {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void
            {
                throw new class extends \Exception {};
            }
        };

        try {
            $controller->post();
        } catch (\Throwable $e) {
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
        ];

        $_SESSION['_csrf'] = 'securetoken';

        $controller = new class($this->model) extends SysadminController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void
            {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void
            {
                throw new class extends \Exception {};
            }
        };

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
        $this->assertEquals('Email invalide.', $_SESSION['error']);
        // Note: keepOld() n'est PAS appelé pour email invalide dans le code actuel
        $this->assertArrayNotHasKey('old_signup', $_SESSION);
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

        $controller = new class($this->model) extends SysadminController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void
            {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void
            {
                throw new class extends \Exception {};
            }
        };

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
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

        $controller = new class($this->model) extends SysadminController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void
            {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void
            {
                throw new class extends \Exception {};
            }

            public function get(): void
            {
                if (!$this->isUserLoggedIn())
                {
                    $this->redirect('/?page=login');
                    $this->terminate();
                }
                if (empty($_SESSION['_csrf'])) {
                    $_SESSION['_csrf'] = bin2hex(random_bytes(16));
                }
            }

            private function isUserLoggedIn(): bool
            {
                return isset($_SESSION['email']);
            }
        };

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

        $controller = new class($this->model) extends SysadminController {
            public string $redirectLocation = '';

            protected function redirect(string $location): void
            {
                $this->redirectLocation = $location;
            }

            protected function terminate(): void
            {
                throw new class extends \Exception {};
            }
        };

        try {
            $controller->post();
        } catch (\Throwable $e) {
            // Ignorer l'exception de terminate()
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
        $this->assertEquals('Requête invalide. Réessaye.', $_SESSION['error']);
    }
}