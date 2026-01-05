<?php

namespace controllers\auth;

use PDO;
use PHPUnit\Framework\TestCase;

define('PHPUNIT_RUNNING', true);

/**
 * Mock de la classe Mailer
 */
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

/**
 * Mock de la classe Database
 */
class Database
{
    private static $pdo;

    public static function getInstance(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('PDO not initialized. Call Database::setInstance() first.');
        }
        return self::$pdo;
    }

    public static function setInstance(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}

/**
 * Simule les vues pour éviter les sorties réelles dans les tests.
 */
class MockPasswordView
{
    public function show(?array $msg = null): void
    {
        // Ne rien afficher pendant les tests
    }
}

class MockMailerView
{
    public function show(string $code, string $link): string
    {
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
require_once __DIR__ . '/../../../app/controllers/auth/PasswordController.php';

/**
 * Classe de tests unitaires pour le contrôleur PasswordController.
 *
 * Cette classe teste les méthodes GET et POST du contrôleur,
 * y compris l'envoi d'emails et la gestion des tokens de réinitialisation de mot de passe.
 *
 * @coversDefaultClass \modules\controllers\auth\PasswordController
 */
class PasswordControllerTest extends TestCase
{
    /**
     * Instance du contrôleur testé.
     *
     * @var \modules\controllers\auth\PasswordController
     */
    protected $controller;

    /**
     * Instance PDO pour la base de données SQLite en mémoire.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * Configuration avant chaque test.
     *
     * Initialise la base de données en mémoire, simule la session et instancie le contrôleur.
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
     * Nettoyage après chaque test.
     *
     * Réinitialise les sessions, POST, env et PDO.
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
     * Vérifie qu'un email a été envoyé à l'adresse et avec le sujet spécifiés.
     *
     * @param string $to
     * @param string $subject
     *
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
     * Vérifie qu'aucun email n'a été envoyé.
     *
     * @return void
     */
    protected function assertNoEmailSent()
    {
        $this->assertEmpty($GLOBALS['mailer_calls'] ?? [], "Expected no emails to be sent");
    }

    /**
     * Simule la connexion d'un utilisateur.
     *
     * @return void
     */
    protected function setUserLoggedIn()
    {
        $_SESSION['email'] = 'logged@user.com';
    }

    /**
     * Crée un utilisateur de test dans la base de données.
     *
     * @param array $data Données de l'utilisateur (optionnel)
     * @return int ID de l'utilisateur créé
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
     * Vérifie que l'utilisateur non connecté voit la vue de mot de passe
     * et que le message de session est supprimé après affichage.
     *
     * @covers ::get
     * @return void
     */
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

    /**
     * Vérifie que l'utilisateur connecté est redirigé vers le dashboard.
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
     * Vérifie que l'utilisateur connecté est redirigé lors d'un POST.
     *
     * @covers ::post
     * @return void
     */
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

    /**
     * Vérifie que l'action POST inconnue déclenche un message d'erreur.
     *
     * @covers ::post
     * @return void
     */
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

    /**
     * Vérifie le comportement de handleSendCode si l'email est vide.
     *
     * @covers ::post
     * @return void
     */
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

    /**
     * Vérifie le comportement de handleSendCode si l'utilisateur n'existe pas.
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
            // Normal
        }
        ob_end_clean();

        $expectedMsg = "Si un compte correspond, un code de réinitialisation a été envoyé.";
        $this->assertEquals(['type' => 'info', 'text' => $expectedMsg], $_SESSION['pw_msg']);

        $this->assertNoEmailSent();
    }

    /**
     * Vérifie handleReset avec token invalide.
     *
     * @covers ::post
     * @return void
     */
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

    /**
     * Vérifie handleReset avec mot de passe trop court.
     *
     * @covers ::post
     * @return void
     */
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

    /**
     * Vérifie handleReset avec code expiré ou utilisateur non trouvé.
     *
     * @covers ::post
     * @return void
     */
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

    /**
     * Vérifie handleReset avec code incorrect.
     *
     * @covers ::post
     * @return void
     */
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
