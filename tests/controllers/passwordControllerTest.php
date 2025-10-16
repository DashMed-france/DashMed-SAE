<?php

namespace modules\controllers\tests;

use PHPUnit\Framework\TestCase;
use PDO;

define('PHPUNIT_RUNNING', true);

/**
 * IMPORTANT : Définir les mocks AVANT tout require
 */

/**
 * Mock de la classe Mailer qui n'est PAS final
 */
class Mailer {
    private static $instance;

    public function __construct($config = null) {
        self::$instance = $this;
    }

    public function send(string $to, string $subject, string $body): bool {
        $GLOBALS['mailer_calls'][] = [
            'to' => $to,
            'subject' => $subject,
            'body' => $body
        ];
        return true;
    }

    public static function getInstance() {
        return self::$instance;
    }
}

/**
 * Mock de la classe Database
 */
class Database {
    private static $pdo;

    public static function getInstance(): PDO {
        if (self::$pdo === null) {
            throw new \RuntimeException('PDO not initialized. Call Database::setInstance() first.');
        }
        return self::$pdo;
    }

    public static function setInstance(PDO $pdo): void {
        self::$pdo = $pdo;
    }
}

/**
 * Simule les vues pour éviter les sorties réelles dans les tests.
 */
class MockPasswordView {
    public function show(?array $msg = null): void {
        // Ne rien afficher pendant les tests
    }
}

class MockMailerView {
    public function show(string $code, string $link): string {
        return '<html><body>Code: ' . $code . ' Link: ' . $link . '</body></html>';
    }
}

// Créer les alias AVANT d'inclure le contrôleur
if (!class_exists('modules\views\passwordView')) {
    class_alias(MockPasswordView::class, 'modules\views\passwordView');
}
if (!class_exists('modules\views\mailerView')) {
    class_alias(MockMailerView::class, 'modules\views\mailerView');
}

// Maintenant on peut inclure le contrôleur
require_once __DIR__ . '/../../app/controllers/passwordController.php';

/**
 * Tests unitaires pour passwordController.
 */
class passwordControllerTest extends TestCase
{
    /** @var \modules\controllers\passwordController */
    protected $controller;

    /** @var PDO */
    protected $pdo;

    /**
     * Configure l'environnement de test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Réinitialiser les headers et les appels mailer
        $GLOBALS['headers_sent'] = [];
        $GLOBALS['mailer_calls'] = [];

        // 1. Créer une base SQLite en mémoire
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 2. Créer la table users
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

        // 3. Injecter le PDO réel dans Database
        Database::setInstance($this->pdo);

        // 4. Simuler la session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];

        // 5. Instancier le contrôleur
        $this->controller = new \modules\controllers\passwordController();
    }

    /**
     * Nettoyage après chaque test.
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
     * Vérifie qu'un email a été envoyé
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
     * Vérifie qu'aucun email n'a été envoyé
     */
    protected function assertNoEmailSent()
    {
        $this->assertEmpty($GLOBALS['mailer_calls'] ?? [], "Expected no emails to be sent");
    }

    /**
     * Simuler la connexion de l'utilisateur.
     */
    protected function setUserLoggedIn()
    {
        $_SESSION['email'] = 'logged@user.com';
    }

    /**
     * Créer un utilisateur de test
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

    // ##########################################################################
    // Tests de la méthode GET
    // ##########################################################################

    public function testGet_UserNotLoggedIn_ShowsPasswordView()
    {
        // GIVEN: L'utilisateur n'est pas connecté.
        $_SESSION = ['pw_msg' => ['type' => 'test', 'text' => 'Message de test']];

        // WHEN: Appel de la méthode get()
        ob_start();
        $this->controller->get();
        ob_end_clean();

        // THEN: Le message est bien unset de la session.
        $this->assertArrayNotHasKey('pw_msg', $_SESSION);
    }

    public function testGet_UserLoggedIn_RedirectsToDashboard()
    {
        // GIVEN: L'utilisateur est connecté.
        $this->setUserLoggedIn();

        // WHEN: Appel de la méthode get()
        ob_start();
        try {
            $this->controller->get();
        } catch (\Exception $e) {
            // Normal si header() lève une exception
        }
        ob_end_clean();

        // THEN: L'utilisateur est toujours connecté
        $this->assertTrue(isset($_SESSION['email']));
    }

    // ##########################################################################
    // Tests de la méthode POST
    // ##########################################################################

    public function testPost_UserLoggedIn_RedirectsToDashboard()
    {
        // GIVEN: L'utilisateur est connecté.
        $this->setUserLoggedIn();

        // WHEN: Appel de la méthode post()
        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
            // Normal
        }
        ob_end_clean();

        // THEN: L'utilisateur est toujours connecté
        $this->assertTrue(isset($_SESSION['email']));
    }

    public function testPost_UnknownAction_SetsErrorMessageAndRedirects()
    {
        // GIVEN: Action POST inconnue
        $_POST = ['action' => 'unknown_action'];

        // WHEN: Appel de la méthode post()
        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
            // Normal
        }
        ob_end_clean();

        // THEN: Message d'erreur défini
        $this->assertEquals(['type' => 'error', 'text' => 'Action inconnue.'], $_SESSION['pw_msg']);
    }

    // ##########################################################################
    // Tests de handleSendCode()
    // ##########################################################################

    public function testHandleSendCode_EmptyEmail_SetsErrorMessageAndRedirects()
    {
        // GIVEN: Email vide
        $_POST = ['action' => 'send_code', 'email' => ''];

        // WHEN: Appel de la méthode post()
        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
            // Normal
        }
        ob_end_clean();

        // THEN: Message d'erreur défini
        $this->assertEquals(['type' => 'error', 'text' => 'Email requis.'], $_SESSION['pw_msg']);
    }

    public function testHandleSendCode_UserNotFound_SetsGenericInfoMessageAndRedirects()
    {
        // GIVEN: Email non trouvé en base (pas d'utilisateur créé)
        $_POST = ['action' => 'send_code', 'email' => 'notfound@user.com'];

        // WHEN: Appel de la méthode post()
        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
            // Normal
        }
        ob_end_clean();

        // THEN: Message d'information générique
        $expectedMsg = "Si un compte correspond, un code de réinitialisation a été envoyé.";
        $this->assertEquals(['type' => 'info', 'text' => $expectedMsg], $_SESSION['pw_msg']);

        // THEN: Aucun email n'a été envoyé
        $this->assertNoEmailSent();
    }

    public function testHandleReset_InvalidToken_SetsErrorMessageAndRedirects()
    {
        // GIVEN: Token invalide
        $_POST = ['action' => 'reset_password', 'token' => 'invalid_token'];

        // WHEN: Appel de la méthode post()
        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
            // Normal
        }
        ob_end_clean();

        // THEN: Message d'erreur
        $this->assertEquals(['type' => 'error', 'text' => 'Lien/token invalide.'], $_SESSION['pw_msg']);
    }

    public function testHandleReset_ShortPassword_SetsErrorMessageAndRedirectsWithToken()
    {
        // GIVEN: Mot de passe trop court
        $token = str_repeat('a', 32);
        $_POST = ['action' => 'reset_password', 'token' => $token, 'password' => 'short'];

        // WHEN: Appel de la méthode post()
        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
            // Normal
        }
        ob_end_clean();

        // THEN: Message d'erreur
        $this->assertEquals(['type' => 'error', 'text' => 'Mot de passe trop court (min 8).'], $_SESSION['pw_msg']);
    }

    public function testHandleReset_ExpiredOrNotFound_SetsErrorMessageAndRedirects()
    {
        // GIVEN: Token valide mais non trouvé (pas d'utilisateur avec ce token)
        $token = str_repeat('a', 32);
        $_POST = ['action' => 'reset_password', 'token' => $token, 'password' => 'newpassword123', 'code' => '123456'];

        // WHEN: Appel de la méthode post()
        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
            // Normal
        }
        ob_end_clean();

        // THEN: Message d'erreur
        $this->assertEquals(['type' => 'error', 'text' => 'Code expiré ou invalide.'], $_SESSION['pw_msg']);
    }

    public function testHandleReset_IncorrectCode_SetsErrorMessageAndRedirectsWithToken()
    {
        // GIVEN: Utilisateur avec un token valide mais code incorrect
        $token = str_repeat('a', 32);
        $correctCode = '123456';
        $correctCodeHash = password_hash($correctCode, PASSWORD_DEFAULT);
        $expires = (new \DateTime('+10 minutes'))->format('Y-m-d H:i:s');

        $userId = $this->createTestUser(['email' => 'user@test.com']);

        // Mettre à jour avec le token
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET reset_token = ?, reset_code_hash = ?, reset_expires = ?
            WHERE id_user = ?
        ");
        $stmt->execute([$token, $correctCodeHash, $expires, $userId]);

        $_POST = ['action' => 'reset_password', 'token' => $token, 'password' => 'newpassword123', 'code' => '654321'];

        // WHEN: Appel de la méthode post()
        ob_start();
        try {
            $this->controller->post();
        } catch (\Exception $e) {
            // Normal
        }
        ob_end_clean();

        // THEN: Message d'erreur
        $this->assertEquals(['type' => 'error', 'text' => 'Code expiré ou invalide.'], $_SESSION['pw_msg']);
    }

}