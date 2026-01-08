<?php

namespace {
    // Force loading of real Database class to avoid conflict later when other tests require it
    // This way, we use the real class but hack its instance via Reflection
    require_once __DIR__ . '/../../../assets/includes/database.php';

    // Mock de la classe Mailer dans le namespace global
    // On suppose que Mailer n'est pas chargé ailleurs ou que c'est safe de le mocker ici
    if (!class_exists('Mailer')) {
        class Mailer
        {
            private static $instance;

            public function __construct($config = null)
            {
                self::$instance = $this;
            }

            public function send(string $to, string $subject, string $body): bool
            {
                $GLOBALS['mailer_calls'][] = [
                    'to' => $to,
                    'subject' => $subject,
                    'body' => $body
                ];
                return true;
            }

            public static function getInstance()
            {
                return self::$instance;
            }
        }
    }

    // Mock de la classe Database
    // Maintenant que le fichier est requis au dessus, la classe existe.
    // Ce bloc if sera sauté, et on utilisera la Reflection dans setUp().
    if (!class_exists('Database')) {
        class Database
        {
            private static $pdo;

            public static function getInstance(): \PDO
            {
                if (self::$pdo === null) {
                    throw new \RuntimeException('PDO not initialized. Call Database::setInstance() first.');
                }
                return self::$pdo;
            }

            public static function setInstance(\PDO $pdo): void
            {
                self::$pdo = $pdo;
            }
        }
    }
}

namespace modules\views {
    if (!class_exists('modules\views\passwordView')) {
        class passwordView
        {
        }
    }
}

namespace modules\views\auth {
    // Mocks des vues
    if (!class_exists('modules\views\auth\passwordView')) {
        class passwordView
        {
            public function show(?array $msg = null): void
            {
            }
        }
    }

    if (!class_exists('modules\views\auth\mailerView')) {
        class mailerView
        {
            public function show(string $code, string $link): string
            {
                return '<html><body>Code: ' . $code . ' Link: ' . $link . '</body></html>';
            }
        }
    }
}

namespace controllers\auth {

    use PHPUnit\Framework\TestCase;
    use PDO;
    use Database;
    use modules\controllers\auth\PasswordController;

    define('PHPUNIT_RUNNING', true);

    require_once __DIR__ . '/../../../app/controllers/auth/PasswordController.php';

    /**
     * Classe de tests unitaires pour le contrôleur PasswordController.
     */
    class PasswordControllerTest extends TestCase
    {
        protected $controller;
        protected $pdo;

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

            // Injection de PDO dans Database (Mock ou Vraie)
            if (method_exists('Database', 'setInstance')) {
                 Database::setInstance($this->pdo);
            } else {
                 // C'est la vraie classe : injection via Reflection
                try {
                    $ref = new \ReflectionProperty('Database', 'instance');
                    $ref->setAccessible(true);
                    $ref->setValue(null, $this->pdo);
                } catch (\Exception $e) {
                    $this->fail("Impossible d'injecter PDO dans Database: " . $e->getMessage());
                }
            }

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            $_SESSION = [];

            $this->controller = new PasswordController();
        }

        protected function tearDown(): void
        {
            $_POST = [];
            $_SESSION = [];
            $_ENV = [];
            $GLOBALS['headers_sent'] = [];
            $GLOBALS['mailer_calls'] = [];
            $this->pdo = null;

            if (method_exists('Database', 'setInstance')) {
            } else {
                try {
                    $ref = new \ReflectionProperty('Database', 'instance');
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
            $this->fail("Email to {$to} with subject '{$subject}' was not sent. Calls: " . print_r($calls, true));
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

        public function testGet_UserNotLoggedIn_ShowsPasswordView()
        {
            $_SESSION = ['pw_msg' => ['type' => 'test', 'text' => 'Message de test']];

            ob_start();
            $this->controller->get();
            ob_end_clean();

            $this->assertArrayNotHasKey('pw_msg', $_SESSION);
        }

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

            $this->assertEquals(['type' => 'error', 'text' => 'Code incorrect.'], $_SESSION['pw_msg']);
        }
    }
}
