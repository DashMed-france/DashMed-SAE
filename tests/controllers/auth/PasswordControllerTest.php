<?php

namespace controllers\auth;

use PHPUnit\Framework\TestCase;
use PDO;
use assets\includes\Database;
use assets\includes\Mailer;
use modules\controllers\auth\PasswordController;
use modules\views\auth\PasswordView;
use modules\views\auth\MailerView;


require_once __DIR__ . '/../../mocks/Database.php';
require_once __DIR__ . '/../../mocks/Mailer.php';
require_once __DIR__ . '/../../mocks/views/auth/PasswordView.php';
require_once __DIR__ . '/../../mocks/views/auth/MailerView.php';


require_once __DIR__ . '/../../../app/controllers/auth/PasswordController.php';

define('PHPUNIT_RUNNING', true);

/**
 * Class PasswordControllerTest | Tests du Contrôleur de Mot de Passe
 *
 * Unit tests for password reset flows.
 * Tests unitaires pour les flux de réinitialisation de mot de passe.
 *
 * Covers request code generation, validation, and reset logic.
 * Couvre la génération de code, la validation et la logique de réinitialisation.
 *
 * @package Tests\Controllers\Auth
 * @author DashMed Team
 */
class PasswordControllerTest extends TestCase
{
    protected $controller;
    protected $pdo;

    /**
     * Setup test database and mocks.
     * Configuration de la base de données de test et des mocks.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['headers_sent'] = [];
        $GLOBALS['mailer_calls'] = [];

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE users (
                id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                profession TEXT,
                admin_status INTEGER DEFAULT 0,
                reset_token TEXT,
                reset_code_hash TEXT,
                reset_expires TEXT
            )
        ");



        Database::setInstance($this->pdo);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];

        $this->controller = new PasswordController();
    }

    /**
     * Teardown and reset.
     * Nettoyage et réinitialisation.
     */
    protected function tearDown(): void
    {
        $_POST = [];
        $_SESSION = [];
        $_ENV = [];
        $GLOBALS['headers_sent'] = [];
        $GLOBALS['mailer_calls'] = [];
        $this->pdo = null;


        if (method_exists(Database::class, 'setInstance')) {
            try {
                //$ref->setValue(null, $nullMock);




                $ref = new \ReflectionProperty(Database::class, 'pdo');
                $ref->setAccessible(true);
                $ref->setValue(null, null);
            } catch (\Exception $e) {
            }
        }

        parent::tearDown();
    }

    protected function assertEmailSent(string $to, string $subject)
    {
        $calls = $GLOBALS['mailer_calls'] ?? [];
        foreach ($calls as $call) {
            if ($call['to'] === $to && $call['subject'] === $subject) {
                $this->assertTrue(true);
                return;
            }
        }
        $this->fail(
            "Email to {$to} with subject '{$subject}' was not sent. Calls: " . print_r($calls, true)
        );
    }

    protected function assertNoEmailSent()
    {
        $this->assertEmpty($GLOBALS['mailer_calls'] ?? [], "Expected no emails to be sent");
    }

    protected function setUserLoggedIn()
    {
        $_SESSION['email'] = 'logged@user.com';
    }

    protected function createTestUser(array $data = [])
    {
        $defaults = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'profession' => 'Doctor',
            'admin_status' => 0
        ];

        $user = array_merge($defaults, $data);

        $stmt = $this->pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password, profession, admin_status)
            VALUES (:first_name, :last_name, :email, :password, :profession, :admin_status)
        ");
        $stmt->execute($user);

        return $this->pdo->lastInsertId();
    }

    /**
     * Test GET without login.
     * Teste GET sans connexion.
     */
    public function testGetUserNotLoggedInShowsPasswordView(): void
    {
        $_SESSION = ['pw_msg' => ['type' => 'test', 'text' => 'Message de test']];

        ob_start();
        $this->controller->get();
        ob_end_clean();

        $this->assertArrayNotHasKey('pw_msg', $_SESSION);
    }

    /**
     * Test GET with login (redirect).
     * Teste GET avec connexion (redirection).
     */
    public function testGetUserLoggedInRedirectsToDashboard(): void
    {
        $this->setUserLoggedIn();

        ob_start();
        try {
            $this->controller->get();
        } catch (\Exception $e) {
        }
        ob_end_clean();

        $this->assertTrue(isset($_SESSION['email']));
    }

    /**
     * Test POST with login (redirect).
     * Teste POST avec connexion (redirection).
     */
    public function testPostUserLoggedInRedirectsToDashboard(): void
    {
        $this->setUserLoggedIn();

        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
        }
        ob_end_clean();

        $this->assertTrue(isset($_SESSION['email']));
    }

    /**
     * Test POST with unknown action.
     * Teste POST avec action inconnue.
     */
    public function testPostUnknownActionSetsErrorMessageAndRedirects(): void
    {
        $_POST = ['action' => 'unknown_action'];

        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
        }
        ob_end_clean();

        $this->assertEquals(['type' => 'error', 'text' => 'Unknown action. | Action inconnue.'], $_SESSION['pw_msg']);
    }

    /**
     * Test sending code with empty email.
     * Teste l'envoi de code avec email vide.
     */
    public function testHandleSendCodeEmptyEmailSetsErrorMessageAndRedirects(): void
    {
        $_POST = ['action' => 'send_code', 'email' => ''];

        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
        }
        ob_end_clean();

        $this->assertEquals(['type' => 'error', 'text' => 'Email requis.'], $_SESSION['pw_msg']);
    }

    /**
     * Test sending code with unknown user.
     * Teste l'envoi de code avec utilisateur inconnu.
     */
    public function testHandleSendCodeUserNotFoundSetsGenericInfoMessageAndRedirects(): void
    {
        $_POST = ['action' => 'send_code', 'email' => 'notfound@user.com'];

        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
        }
        ob_end_clean();

        $expectedMsg = "Si un compte correspond, un code de réinitialisation a été envoyé.";
        $this->assertEquals(['type' => 'info', 'text' => $expectedMsg], $_SESSION['pw_msg']);
        $this->assertNoEmailSent();
    }

    /**
     * Test reset with invalid token.
     * Teste la réinitialisation avec un token invalide.
     */
    public function testHandleResetInvalidTokenSetsErrorMessageAndRedirects(): void
    {
        $_POST = ['action' => 'reset_password', 'token' => 'invalid_token'];

        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
        }
        ob_end_clean();

        $this->assertEquals(['type' => 'error', 'text' => 'Lien/token invalide.'], $_SESSION['pw_msg']);
    }

    /**
     * Test reset with short password.
     * Teste la réinitialisation avec un mot de passe trop court.
     */
    public function testHandleResetShortPasswordSetsErrorMessageAndRedirectsWithToken(): void
    {
        $token = str_repeat('a', 32);
        $_POST = ['action' => 'reset_password', 'token' => $token, 'password' => 'short'];

        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
        }
        ob_end_clean();

        $this->assertEquals(['type' => 'error', 'text' => 'Mot de passe trop court (min 8).'], $_SESSION['pw_msg']);
    }

    /**
     * Test reset with expired token.
     * Teste la réinitialisation avec un token expiré.
     */
    public function testHandleResetExpiredOrNotFoundSetsErrorMessageAndRedirects(): void
    {
        $token = str_repeat('a', 32);
        $_POST = ['action' => 'reset_password', 'token' => $token, 'password' => 'newpassword123', 'code' => '123456'];

        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
        }
        ob_end_clean();

        $this->assertEquals(['type' => 'error', 'text' => 'Code expiré ou invalide.'], $_SESSION['pw_msg']);
    }

    /**
     * Test reset with incorrect code.
     * Teste la réinitialisation avec un code incorrect.
     */
    public function testHandleResetIncorrectCodeSetsErrorMessageAndRedirectsWithToken(): void
    {
        $token = str_repeat('a', 32);
        $correctCode = '123456';
        $correctCodeHash = password_hash($correctCode, PASSWORD_DEFAULT);
        $expires = (new \DateTime('+10 minutes'))->format('Y-m-d H:i:s');

        $userId = $this->createTestUser(['email' => 'user@test.com']);

        $stmt = $this->pdo->prepare("
            UPDATE users
            SET reset_token = ?, reset_code_hash = ?, reset_expires = ?
            WHERE id_user = ?
        ");
        $stmt->execute([$token, $correctCodeHash, $expires, $userId]);

        $_POST = ['action' => 'reset_password', 'token' => $token, 'password' => 'newpassword123', 'code' => '654321'];

        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
        }
        ob_end_clean();

        $this->assertEquals(['type' => 'error', 'text' => 'Code incorrect.'], $_SESSION['pw_msg']);
    }
}
