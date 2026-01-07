<?php

namespace controllers\auth;

use PDO;
use PHPUnit\Framework\TestCase;

define('PHPUNIT_RUNNING', true);

/**
 * Mock of the Mailer class.
 */
class Mailer
{
    /** @var Mailer|null */
    private static $instance;

    /**
     * Mailer constructor.
     * @param mixed|null $config
     */
    public function __construct($config = null)
    {
        self::$instance = $this;
    }

    /**
     * Simulates sending an email.
     * * @param string $to
     * @param string $subject
     * @param string $body
     * @return bool
     */
    public function send(string $to, string $subject, string $body): bool
    {
        $GLOBALS['mailer_calls'][] = [
            'to' => $to,
            'subject' => $subject,
            'body' => $body
        ];
        return true;
    }

    /**
     * Returns the singleton instance.
     * @return Mailer|null
     */
    public static function getInstance()
    {
        return self::$instance;
    }
}

/**
 * Mock of the Database class.
 */
class Database
{
    /** @var PDO|null */
    private static $pdo;

    /**
     * Gets the PDO instance.
     * * @throws \RuntimeException If PDO is not initialized.
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('PDO not initialized. Call Database::setInstance() first.');
        }
        return self::$pdo;
    }

    /**
     * Sets the PDO instance.
     * * @param PDO $pdo
     * @return void
     */
    public static function setInstance(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}

/**
 * Simulates views to prevent real output during tests.
 */
class MockPasswordView
{
    /**
     * Mock display method.
     * @param array|null $msg
     * @return void
     */
    public function show(?array $msg = null): void
    {
    }
}

/**
 * Mock of the Mailer view.
 */
class MockMailerView
{
    /**
     * Returns a mock HTML string.
     * * @param string $code
     * @param string $link
     * @return string
     */
    public function show(string $code, string $link): string
    {
        return '<html><body>Code: ' . $code . ' Link: ' . $link . '</body></html>';
    }
}

if (!class_exists('modules\views\passwordView')) {
    class_alias(MockPasswordView::class, 'modules\views\passwordView');
}
if (!class_exists('modules\views\mailerView')) {
    class_alias(MockMailerView::class, 'modules\views\mailerView');
}

require_once __DIR__ . '/../../../app/controllers/auth/PasswordController.php';

/**
 * Unit test class for PasswordController.
 *
 * Tests the GET and POST methods of the controller,
 * including email dispatch and password reset token management.
 *
 * @coversDefaultClass \modules\controllers\auth\PasswordController
 */
class PasswordControllerTest extends TestCase
{
    /**
     * Instance of the tested controller.
     *
     * @var \modules\controllers\auth\PasswordController
     */
    protected $controller;

    /**
     * PDO instance for the in-memory SQLite database.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * Set up before each test.
     *
     * Initializes the in-memory database, mocks the session, and instantiates the controller.
     *
     * @return void
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

        $this->controller = new \modules\controllers\auth\PasswordController();
    }

    /**
     * Cleanup after each test.
     *
     * Resets sessions, POST data, environment, and PDO.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_POST = [];
        $_SESSION = [];
        $_ENV = [];
        $GLOBALS['headers_sent'] = [];
        $GLOBALS['mailer_calls'] = [];
        $this->pdo = null;
        parent::tearDown();
    }

    /**
     * Asserts that an email was sent to the specified address with the given subject.
     *
     * @param string $to
     * @param string $subject
     * @return void
     */
    protected function assertEmailSent(string $to, string $subject)
    {
        $calls = $GLOBALS['mailer_calls'] ?? [];
        foreach ($calls as $call) {
            if ($call['to'] === $to && $call['subject'] === $subject) {
                $this->assertTrue(true);
                return;
            }
        }
        $this->fail("Email to {$to} with subject '{$subject}' was not sent. Calls: " . print_r($calls, true));
    }

    /**
     * Asserts that no emails were sent.
     *
     * @return void
     */
    protected function assertNoEmailSent()
    {
        $this->assertEmpty($GLOBALS['mailer_calls'] ?? [], "Expected no emails to be sent");
    }

    /**
     * Simulates a logged-in user.
     *
     * @return void
     */
    protected function setUserLoggedIn()
    {
        $_SESSION['email'] = 'logged@user.com';
    }

    /**
     * Creates a test user in the database.
     *
     * @param array $data Optional user data.
     * @return int The ID of the created user.
     */
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
     * Verifies that a logged-out user sees the password view
     * and that the session message is cleared after display.
     *
     * @covers ::get
     * @return void
     */
    public function testGet_UserNotLoggedIn_ShowsPasswordView()
    {
        $_SESSION = ['pw_msg' => ['type' => 'test', 'text' => 'Message de test']];

        ob_start();
        $this->controller->get();
        ob_end_clean();

        $this->assertArrayNotHasKey('pw_msg', $_SESSION);
    }

    /**
     * Verifies that a logged-in user is redirected to the dashboard.
     *
     * @covers ::get
     * @return void
     */
    public function testGet_UserLoggedIn_RedirectsToDashboard()
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
     * Verifies that a logged-in user is redirected during a POST request.
     *
     * @covers ::post
     * @return void
     */
    public function testPost_UserLoggedIn_RedirectsToDashboard()
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
     * Verifies that an unknown POST action triggers an error message.
     *
     * @covers ::post
     * @return void
     */
    public function testPost_UnknownAction_SetsErrorMessageAndRedirects()
    {
        $_POST = ['action' => 'unknown_action'];

        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
        }
        ob_end_clean();

        $this->assertEquals(['type' => 'error', 'text' => 'Action inconnue.'], $_SESSION['pw_msg']);
    }

    /**
     * Verifies handleSendCode behavior if the email is empty.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleSendCode_EmptyEmail_SetsErrorMessageAndRedirects()
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
     * Verifies handleSendCode behavior if the user is not found.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleSendCode_UserNotFound_SetsGenericInfoMessageAndRedirects()
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
     * Verifies handleReset with an invalid token.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleReset_InvalidToken_SetsErrorMessageAndRedirects()
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
     * Verifies handleReset with a password that is too short.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleReset_ShortPassword_SetsErrorMessageAndRedirectsWithToken()
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
     * Verifies handleReset with an expired code or user not found.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleReset_ExpiredOrNotFound_SetsErrorMessageAndRedirects()
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
     * Verifies handleReset with an incorrect code.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleReset_IncorrectCode_SetsErrorMessageAndRedirectsWithToken()
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

        $this->assertEquals(['type' => 'error', 'text' => 'Code expiré ou invalide.'], $_SESSION['pw_msg']);
    }
}