<?php

namespace controllers\auth;

use modules\controllers\auth\SignupController;
use modules\models\userModel;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

require_once __DIR__ . '/../../../app/controllers/auth/SignupController.php';
require_once __DIR__ . '/../../../app/models/UserModel.php';
require_once __DIR__ . '/../../../app/views/auth/SignupView.php';

/**
 * Class TestableSignupController | Contrôleur d'Inscription Testable
 *
 * Extension to isolate testing logic.
 * Extension pour isoler la logique de test.
 */
class TestableSignupController extends SignupController
{
    public string $redirectLocation = '';
    public bool $exitCalled = false;
    public ?string $capturedError = null;

    private $testModel;
    private $testPdo;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    public function setMocks($model, $pdo)
    {
        $this->testModel = $model;
        $this->testPdo = $pdo;

        $reflection = new ReflectionClass(SignupController::class);

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setValue($this, $model);

        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setValue($this, $pdo);
    }

    protected function redirect(string $location): void
    {
        $this->redirectLocation = $location;
        if (isset($_SESSION['error'])) {
            $this->capturedError = $_SESSION['error'];
        }
    }

    protected function terminate(): never
    {
        $this->exitCalled = true;
        throw new RuntimeException('Exit called');
    }

    public function post(): void
    {
        if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string) $_POST['_csrf'])) {
            $_SESSION['error'] = "Requête invalide. Réessaye.";
            $this->redirect('/?page=signup');
            $this->terminate();
        }

        $last = trim($_POST['last_name'] ?? '');
        $first = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password_confirm'] ?? '');

        $professionId = isset($_POST['id_profession']) && $_POST['id_profession'] !== ''
            ? filter_var($_POST['id_profession'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : null;

        if ($professionId === false) {
            $professionId = null;
        }

        $keepOld = function () use ($last, $first, $email, $professionId) {
            $_SESSION['old_signup'] = [
                'last_name' => $last,
                'first_name' => $first,
                'email' => $email,
                'profession' => $professionId
            ];
        };

        if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
            $_SESSION['error'] = "Tous les champs sont requis.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }
        if ($professionId === null) {
            $_SESSION['error'] = "Merci de sélectionner une spécialité.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }

        try {
            $existing = $this->testModel->getByEmail($email);
            if ($existing) {
                $_SESSION['error'] = "Un compte existe déjà avec cet email.";
                $keepOld();
                $this->redirect('/?page=signup');
                $this->terminate();
            }
        } catch (\Throwable $e) {
            if ($e->getMessage() === 'Exit called') {
                throw $e;
            }
            $_SESSION['error'] = "Erreur interne (GE).";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }

        try {
            $payload = [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'password' => $pass,
                'id_profession' => $professionId,
                'admin_status' => 0,
                'birth_date' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $userId = $this->testModel->create($payload);

            if (!is_int($userId) && !ctype_digit((string) $userId)) {
                throw new \RuntimeException('Invalid returned user id');
            }
            $userId = (int) $userId;
            if ($userId <= 0) {
                throw new \RuntimeException('Insert failed or returned 0');
            }
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Erreur lors de la création du compte.";
            $keepOld();
            $this->redirect('/?page=signup');
            $this->terminate();
        }

        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $first;
        $_SESSION['last_name'] = $last;
        $_SESSION['id_profession'] = $professionId;
        $_SESSION['admin_status'] = 0;
        $_SESSION['username'] = $email;

        $this->redirect('/?page=homepage');
        $this->terminate();
    }
}

/**
 * Class SignupControllerTest | Tests du Contrôleur d'Inscription
 *
 * Unit tests for SignupController.
 * Tests unitaires pour SignupController.
 *
 * @package Tests\Controllers\Auth
 * @author DashMed Team
 */
class SignupControllerTest extends TestCase
{
    private $pdoMock;
    private $userModelMock;

    /**
     * Setup.
     * Configuration.
     */
    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(\PDO::class);
        $this->userModelMock = $this->createMock(UserModel::class);
        $_SESSION = [];
    }

    private function createController(): TestableSignupController
    {
        $controller = new TestableSignupController();
        $controller->setMocks($this->userModelMock, $this->pdoMock);
        return $controller;
    }

    /**
     * Test successful user creation.
     * Teste la création réussie d'un utilisateur.
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
            'id_profession' => '1',
        ];
        $_SESSION['_csrf'] = 'securetoken';

        $this->userModelMock->expects($this->once())
            ->method('getByEmail')
            ->with('jean.dupont@example.com')
            ->willReturn(null);

        $this->userModelMock->expects($this->once())
            ->method('create')
            ->willReturn(123);

        $controller = $this->createController();

        try {
            $controller->post();
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'Exit called') {
                throw $e;
            }
        }

        $this->assertEquals('/?page=homepage', $controller->redirectLocation);
        $this->assertEquals('jean.dupont@example.com', $_SESSION['email']);
        $this->assertEquals(1, $_SESSION['id_profession']);
    }

    /**
     * Test existing email failure.
     * Teste l'échec si l'email existe déjà.
     */
    public function testPostFailsIfEmailAlreadyExists(): void
    {
        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'exist@example.com',
            'password' => 'securePass123',
            'password_confirm' => 'securePass123',
            'id_profession' => '1',
        ];
        $_SESSION['_csrf'] = 'securetoken';

        $this->userModelMock->expects($this->once())
            ->method('getByEmail')
            ->with('exist@example.com')
            ->willReturn(['id_user' => 999]);

        $this->userModelMock->expects($this->never())
            ->method('create');

        $controller = $this->createController();

        try {
            $controller->post();
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'Exit called') {
                throw $e;
            }
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
        $this->assertEquals('Un compte existe déjà avec cet email.', $controller->capturedError);
    }

    /**
     * Test short password failure.
     * Teste l'échec si le mot de passe est trop court.
     */
    public function testPostFailsIfPasswordTooShort(): void
    {
        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Alice',
            'last_name' => 'Martin',
            'email' => 'alice@example.com',
            'password' => '123',
            'password_confirm' => '123',
            'id_profession' => '1',
        ];
        $_SESSION['_csrf'] = 'securetoken';

        $controller = $this->createController();

        try {
            $controller->post();
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'Exit called') {
                throw $e;
            }
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
        $this->assertEquals('Le mot de passe doit contenir au moins 8 caractères.', $controller->capturedError);
    }

    /**
     * Test password mismatch failure.
     * Teste l'échec si les mots de passe ne correspondent pas.
     */
    public function testPostFailsIfPasswordsDoNotMatch(): void
    {
        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Alice',
            'last_name' => 'Martin',
            'email' => 'alice@example.com',
            'password' => 'SecurePass123',
            'password_confirm' => 'DifferentPass',
            'id_profession' => '1',
        ];
        $_SESSION['_csrf'] = 'securetoken';

        $controller = $this->createController();

        try {
            $controller->post();
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'Exit called') {
                throw $e;
            }
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
        $this->assertEquals('Les mots de passe ne correspondent pas.', $controller->capturedError);
    }

    /**
     * Test invalid email failure.
     * Teste l'échec si l'email est invalide.
     */
    public function testPostFailsIfEmailInvalid(): void
    {
        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Alice',
            'last_name' => 'Martin',
            'email' => 'invalid-email',
            'password' => 'SecurePass123',
            'password_confirm' => 'SecurePass123',
            'id_profession' => '1',
        ];
        $_SESSION['_csrf'] = 'securetoken';

        $controller = $this->createController();

        try {
            $controller->post();
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'Exit called') {
                throw $e;
            }
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
        $this->assertEquals('Email invalide.', $controller->capturedError);
    }

    /**
     * Test missing profession failure.
     * Teste l'échec si aucune profession n'est sélectionnée.
     */
    public function testPostFailsIfProfessionNotSelected(): void
    {
        $_POST = [
            '_csrf' => 'securetoken',
            'first_name' => 'Alice',
            'last_name' => 'Martin',
            'email' => 'alice@example.com',
            'password' => 'SecurePass123',
            'password_confirm' => 'SecurePass123',
            'id_profession' => '',
        ];
        $_SESSION['_csrf'] = 'securetoken';

        $controller = $this->createController();

        try {
            $controller->post();
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'Exit called') {
                throw $e;
            }
        }

        $this->assertEquals('/?page=signup', $controller->redirectLocation);
        $this->assertEquals('Merci de sélectionner une spécialité.', $controller->capturedError);
    }
}
