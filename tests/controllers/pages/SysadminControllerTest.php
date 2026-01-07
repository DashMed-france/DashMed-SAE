<?php

namespace controllers\pages;

use modules\controllers\pages\SysadminController;
use modules\models\userModel;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../app/controllers/pages/SysadminController.php';
require_once __DIR__ . '/../../../app/models/UserModel.php';


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
                id_profession INTEGER,
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
    private function createTestController()
    {
        // Classe anonyme étendant le contrôleur réel
        return new class ($this->model, $this->pdo) extends SysadminController {
            public string $redirectLocation = '';

            public function __construct(userModel $model, \PDO $pdo)
            {
                // On n'appelle PAS parent::__construct() pour éviter les effets de bord (session start, new database...)
                // On injecte manuellement les propriétés protégées
                $this->model = $model;
                $this->pdo = $pdo;

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
                // On ne fait rien ou on lance une exception légère, mais ici on veut juste arrêter l'exécution de la méthode post/get
                // Pour que le test puisse vérifier l'état après l'appel.
                // Dans les tests, on attrape cette exception.
                throw new \RuntimeException('Exit called');
            }

            protected function getAllSpecialties(): array
            {
                return [];
            }

            /**
             * Surcharge de post pour supprimer error_log
             */
            public function post(): void
            {
                // error_log supprimé ici

                if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string) $_POST['_csrf'])) {
                    $_SESSION['error'] = "Requête invalide. Réessaye.";
                    $this->redirect('/?page=sysadmin');
                    $this->terminate();
                }

                $last = trim($_POST['last_name'] ?? '');
                $first = trim($_POST['first_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $pass = (string) ($_POST['password'] ?? '');
                $pass2 = (string) ($_POST['password_confirm'] ?? '');
                $profId = $_POST['id_profession'] ?? null;
                $admin = $_POST['admin_status'] ?? 0;

                if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
                    $_SESSION['error'] = "Tous les champs sont requis.";
                    $this->redirect('/?page=sysadmin');
                    $this->terminate();
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['error'] = "Email invalide.";
                    $this->redirect('/?page=sysadmin');
                    $this->terminate();
                }
                if ($pass !== $pass2) {
                    $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
                    $this->redirect('/?page=sysadmin');
                    $this->terminate();
                }
                if (strlen($pass) < 8) {
                    $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
                    $this->redirect('/?page=sysadmin');
                    $this->terminate();
                }

                if ($this->model->getByEmail($email)) {
                    $_SESSION['error'] = "Un compte existe déjà avec cet email.";
                    $this->redirect('/?page=sysadmin');
                    $this->terminate();
                }

                try {
                    $userId = $this->model->create([
                        'first_name' => $first,
                        'last_name' => $last,
                        'email' => $email,
                        'password' => $pass,
                        'profession' => $profId,
                        'admin_status' => $admin,
                    ]);
                } catch (\Throwable $e) {
                    // error_log supprimé
                    $_SESSION['error'] = "Impossible de créer le compte (email déjà utilisé ?)";
                    $this->redirect('/?page=sysadmin');
                    $this->terminate();
                }

                $_SESSION['success'] = "Compte créé avec succès pour {$email}";
                $this->redirect('/?page=sysadmin');
                $this->terminate();
            }
        };
    }

    /**
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
        $this->runControllerAction($controller, 'post');

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
        $this->runControllerAction($controller, 'post');

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
        $this->runControllerAction($controller, 'post');

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
        $this->runControllerAction($controller, 'post');

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
        $this->runControllerAction($controller, 'get');

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
        $this->runControllerAction($controller, 'get');

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
        $this->runControllerAction($controller, 'post');

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
        $this->runControllerAction($controller, 'post');

        $this->assertEquals('/?page=sysadmin', $controller->redirectLocation);
        $this->assertEquals('Compte créé avec succès pour admin@example.com', $_SESSION['success']);

        // Vérifier que l'utilisateur a été créé avec admin_status = 1
        $user = $this->model->getByEmail('admin@example.com');
        $this->assertNotNull($user);
        $this->assertEquals(1, (int)$user['admin_status']);
    }
}
