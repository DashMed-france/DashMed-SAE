<?php

namespace controllers\auth;

use modules\controllers\auth\SignupController;
use ReflectionClass;
use RuntimeException;

use function session_start;
use function session_status;

use const PHP_SESSION_ACTIVE;

require_once __DIR__ . '/../../../app/controllers/auth/SignupController.php';

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
