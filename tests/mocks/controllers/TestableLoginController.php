<?php

namespace controllers\auth;

use modules\controllers\auth\LoginController;
use modules\models\UserModel;
use modules\views\auth\LoginView;
use ReflectionClass;

use function session_start;
use function session_status;

use const PHP_SESSION_ACTIVE;

require_once __DIR__ . '/../../../app/controllers/auth/LoginController.php';
require_once __DIR__ . '/../../../app/models/UserModel.php';
require_once __DIR__ . '/../../../app/views/auth/LoginView.php';

/**
 * Class TestableLoginController | Contrôleur de Connexion Testable
 *
 * Extension of LoginController for testing purposes.
 * Extension de LoginController à des fins de test.
 *
 * Allows dependency injection and redirection capture.
 * Permet l'injection de dépendances et la capture de redirection.
 */
class TestableLoginController extends LoginController
{
    public ?string $redirectUrl = null;
    public bool $exitCalled = false;
    public string $renderedOutput = '';
    public UserModel $testModel;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    public function setModel(UserModel $model): void
    {
        $this->testModel = $model;

        $reflection = new ReflectionClass(LoginController::class);
        $property = $reflection->getProperty('model');
        $property->setAccessible(true);
        $property->setValue($this, $model);
    }

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

        $reflection = new ReflectionClass(LoginController::class);
        $property = $reflection->getProperty('model');
        $property->setAccessible(true);
        $model = $property->getValue($this);

        $users = $model->listUsersForLogin();

        ob_start();
        (new LoginView())->show($users);
        $this->renderedOutput = ob_get_clean();
    }

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

    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
        $this->redirectUrl = '/?page=login';
        $this->exitCalled = true;
    }

    public function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
