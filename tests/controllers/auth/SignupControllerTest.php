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

require_once __DIR__ . '/../../mocks/controllers/TestableSignupController.php';

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

        $this->assertEquals(
            '/?page=signup',
            $controller->redirectLocation
        );
        $this->assertEquals(
            'Le mot de passe doit contenir au moins 8 caractères.',
            $controller->capturedError
        );
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
