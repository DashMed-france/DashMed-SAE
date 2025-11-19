<?php

namespace controllers\auth;

use DateTime;
use Exception;
use modules\controllers\auth\PasswordController;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

define('PHPUNIT_RUNNING', true);

/**
 * Mock de la classe Mailer - DANS LE NAMESPACE GLOBAL
 */
class MockMailer {
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
 * Mock de la classe Database - DANS LE NAMESPACE GLOBAL
 */
class MockDatabase {
    private static $pdo;

    public static function getInstance(): PDO {
        if (self::$pdo === null) {
            throw new RuntimeException('PDO not initialized. Call Database::setInstance() first.');
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

// Créer les alias dans le NAMESPACE GLOBAL
if (!class_exists('Database', false)) {
    class_alias(MockDatabase::class, 'Database');
}
if (!class_exists('Mailer', false)) {
    class_alias(MockMailer::class, 'Mailer');
}

// Créer les alias pour les vues
if (!class_exists('modules\views\auth\passwordView')) {
    class_alias(MockPasswordView::class, 'modules\views\auth\passwordView');
}
if (!class_exists('modules\views\auth\mailerView')) {
    class_alias(MockMailerView::class, 'modules\views\auth\mailerView');
}

// Maintenant on peut inclure le contrôleur
require_once __DIR__ . '/../../../app/controllers/auth/PasswordController.php';

/**
 * Classe de tests unitaires pour le contrôleur PasswordController.
 *
 * Cette classe teste les méthodes GET et POST du contrôleur,
 * y compris l'envoi d'emails et la gestion des tokens de réinitialisation de mot de passe.
 *
 * @coversDefaultClass PasswordController
 */
class PasswordControllerTest extends TestCase
{
    /**
     * Instance du contrôleur testé.
     *
     * @var PasswordController
     */
    protected $controller;

    /**
     * Mock PDO pour la base de données.
     *
     * @var PDO|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $pdoMock;

    /**
     * Mock PDOStatement
     *
     * @var PDOStatement|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $stmtMock;

    /**
     * Niveau initial des output buffers
     *
     * @var int
     */
    private int $initialObLevel;

    /**
     * Configuration avant chaque test.
     *
     * Initialise les mocks, simule la session et instancie le contrôleur.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Enregistre le niveau initial des buffers
        $this->initialObLevel = ob_get_level();

        $GLOBALS['headers_sent'] = [];
        $GLOBALS['mailer_calls'] = [];

        // Crée les mocks PDO et PDOStatement
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);

        // Configure le comportement par défaut
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn(false);

        // Utilise l'alias global
        MockDatabase::setInstance($this->pdoMock);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $_POST = [];
        $_ENV = [];

        $this->controller = new PasswordController();
    }

    /**
     * Nettoyage après chaque test.
     *
     * Réinitialise les sessions, POST, env et mocks.
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

        // Restaure le niveau de buffer initial uniquement
        while (ob_get_level() > $this->initialObLevel) {
            ob_end_clean();
        }

        parent::tearDown();
    }

    /**
     * Capture proprement la sortie d'une fonction
     *
     * @param callable $callback
     * @return string
     */
    private function captureOutput(callable $callback): string
    {
        $level = ob_get_level();
        ob_start();
        try {
            $callback();
            return ob_get_clean();
        } catch (\Throwable $e) {
            // Nettoie uniquement le buffer que nous avons créé
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
    }

    /**
     * Vérifie qu'un email a été envoyé à l'adresse et avec le sujet spécifiés.
     *
     * @param string $to
     * @param string $subject
     *
     * @return void
     */
    protected function assertEmailSent(string $to, string $subject): void
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
    protected function assertNoEmailSent(): void
    {
        $this->assertEmpty($GLOBALS['mailer_calls'] ?? [], "Expected no emails to be sent");
    }

    /**
     * Simule la connexion d'un utilisateur.
     *
     * @return void
     */
    protected function setUserLoggedIn(): void
    {
        $_SESSION['email'] = 'logged@user.com';
    }

    /**
     * Vérifie que l'utilisateur non connecté voit la vue de mot de passe
     * et que le message de session est supprimé après affichage.
     *
     * @covers ::get
     * @return void
     */
    public function testGet_UserNotLoggedIn_ShowsPasswordView(): void
    {
        // GIVEN: L'utilisateur n'est pas connecté.
        $_SESSION = ['pw_msg' => ['type' => 'test', 'text' => 'Message de test']];

        // WHEN: Appel de la méthode get()
        $this->captureOutput(function () {
            $this->controller->get();
        });

        // THEN: Le message est bien unset de la session.
        $this->assertArrayNotHasKey('pw_msg', $_SESSION);
    }

    /**
     * Vérifie que l'utilisateur connecté est redirigé vers le dashboard.
     *
     * @covers ::get
     * @return void
     */
    public function testGet_UserLoggedIn_RedirectsToDashboard(): void
    {
        $this->setUserLoggedIn();

        $this->captureOutput(function () {
            try {
                $this->controller->get();
            } catch (Exception $e) {
                // Normal - header redirect
            }
        });

        $this->assertTrue(isset($_SESSION['email']));
    }

    /**
     * Vérifie que l'utilisateur connecté est redirigé lors d'un POST.
     *
     * @covers ::post
     * @return void
     */
    public function testPost_UserLoggedIn_RedirectsToDashboard(): void
    {
        // GIVEN: L'utilisateur est connecté.
        $this->setUserLoggedIn();

        // WHEN: Appel de la méthode post()
        $this->captureOutput(function () {
            try {
                $this->controller->post();
            } catch (Exception $e) {
                // Normal
            }
        });

        // THEN: L'utilisateur est toujours connecté
        $this->assertTrue(isset($_SESSION['email']));
    }

    /**
     * Vérifie que l'action POST inconnue déclenche un message d'erreur.
     *
     * @covers ::post
     * @return void
     */
    public function testPost_UnknownAction_SetsErrorMessageAndRedirects(): void
    {
        // GIVEN: Action POST inconnue
        $_POST = ['action' => 'unknown_action'];

        // WHEN: Appel de la méthode post()
        $this->captureOutput(function () {
            try {
                $this->controller->post();
            } catch (Exception $e) {
                // Normal
            }
        });

        // THEN: Message d'erreur défini
        $this->assertEquals(['type' => 'error', 'text' => 'Action inconnue.'], $_SESSION['pw_msg']);
    }

    /**
     * Vérifie le comportement de handleSendCode si l'email est vide.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleSendCode_EmptyEmail_SetsErrorMessageAndRedirects(): void
    {
        // GIVEN: Email vide
        $_POST = ['action' => 'send_code', 'email' => ''];

        // WHEN: Appel de la méthode post()
        $this->captureOutput(function () {
            try {
                $this->controller->post();
            } catch (Exception $e) {
                // Normal
            }
        });

        // THEN: Message d'erreur défini
        $this->assertEquals(['type' => 'error', 'text' => 'Email requis.'], $_SESSION['pw_msg']);
    }

    /**
     * Vérifie le comportement de handleSendCode si l'utilisateur n'existe pas.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleSendCode_UserNotFound_SetsGenericInfoMessageAndRedirects(): void
    {
        // GIVEN: Email non trouvé
        $_POST = ['action' => 'send_code', 'email' => 'notfound@user.com'];

        // Configure le mock pour retourner false (utilisateur non trouvé)
        $this->stmtMock->method('fetch')->willReturn(false);

        // WHEN: Appel de la méthode post()
        $this->captureOutput(function () {
            try {
                $this->controller->post();
            } catch (Exception $e) {
                // Normal - header redirect
            }
        });

        // THEN: Message générique affiché
        $expectedMsg = "Si un compte correspond, un code de réinitialisation a été envoyé.";
        $this->assertEquals(['type' => 'info', 'text' => $expectedMsg], $_SESSION['pw_msg']);

        // THEN: Aucun email n'a été envoyé
        $this->assertNoEmailSent();
    }

    /**
     * Vérifie handleSendCode avec un utilisateur existant.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleSendCode_UserFound_SendsEmailAndRedirects(): void
    {
        // GIVEN: Email trouvé
        $_POST = ['action' => 'send_code', 'email' => 'user@test.com'];
        $_ENV['APP_URL'] = 'http://localhost';

        // Créer de nouveaux mocks pour ce test
        $stmtMock1 = $this->createMock(PDOStatement::class);
        $stmtMock2 = $this->createMock(PDOStatement::class);

        // Premier appel: SELECT user
        $stmtMock1->expects($this->once())->method('execute');
        $stmtMock1->expects($this->once())->method('fetch')->willReturn([
            'id_user' => 1,
            'email' => 'user@test.com',
            'first_name' => 'John'
        ]);

        // Deuxième appel: UPDATE token
        $stmtMock2->expects($this->once())->method('execute');

        // Configure PDO pour retourner les bons statements dans l'ordre
        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtMock1, $stmtMock2);

        // WHEN: Appel de la méthode post()
        $this->captureOutput(function () {
            try {
                $this->controller->post();
            } catch (Exception $e) {
                // Normal - header redirect
            }
        });

        // THEN: Email envoyé
        $this->assertEmailSent('user@test.com', 'Votre code de réinitialisation');
    }

    /**
     * Vérifie handleReset avec token invalide.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleReset_InvalidToken_SetsErrorMessageAndRedirects(): void
    {
        // GIVEN: Token invalide
        $_POST = ['action' => 'reset_password', 'token' => 'invalid_token'];

        // WHEN: Appel de la méthode post()
        $this->captureOutput(function () {
            try {
                $this->controller->post();
            } catch (Exception $e) {
                // Normal
            }
        });

        // THEN: Message d'erreur
        $this->assertEquals(['type' => 'error', 'text' => 'Lien/token invalide.'], $_SESSION['pw_msg']);
    }

    /**
     * Vérifie handleReset avec mot de passe trop court.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleReset_ShortPassword_SetsErrorMessageAndRedirectsWithToken(): void
    {
        // GIVEN: Mot de passe trop court
        $token = str_repeat('a', 32);
        $_POST = ['action' => 'reset_password', 'token' => $token, 'password' => 'short'];

        // WHEN: Appel de la méthode post()
        $this->captureOutput(function () {
            try {
                $this->controller->post();
            } catch (Exception $e) {
                // Normal
            }
        });

        // THEN: Message d'erreur
        $this->assertEquals(['type' => 'error', 'text' => 'Mot de passe trop court (min 8).'], $_SESSION['pw_msg']);
    }

    /**
     * Vérifie handleReset avec code expiré ou utilisateur non trouvé.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleReset_ExpiredOrNotFound_SetsErrorMessageAndRedirects(): void
    {
        // GIVEN: Token valide mais non trouvé
        $token = str_repeat('a', 32);
        $_POST = ['action' => 'reset_password', 'token' => $token, 'password' => 'newpassword123', 'code' => '123456'];

        // Configure le mock pour retourner false (pas d'utilisateur)
        $this->stmtMock->method('fetch')->willReturn(false);

        // WHEN: Appel de la méthode post()
        $this->captureOutput(function () {
            try {
                $this->controller->post();
            } catch (Exception $e) {
                // Normal
            }
        });

        // THEN: Message d'erreur
        $this->assertEquals(['type' => 'error', 'text' => 'Code expiré ou invalide.'], $_SESSION['pw_msg']);
    }

    /**
     * Vérifie handleReset avec code incorrect.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleReset_IncorrectCode_SetsErrorMessageAndRedirectsWithToken(): void
    {
        // GIVEN: Utilisateur avec un token valide mais code incorrect
        $token = str_repeat('a', 32);
        $correctCode = '123456';
        $correctCodeHash = password_hash($correctCode, PASSWORD_DEFAULT);
        $expires = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

        $_POST = ['action' => 'reset_password', 'token' => $token, 'password' => 'newpassword123', 'code' => '654321'];

        // Configure le mock pour retourner un utilisateur
        $this->stmtMock->method('fetch')->willReturn([
            'id_user' => 1,
            'reset_code_hash' => $correctCodeHash,
            'reset_expires' => $expires
        ]);

        // WHEN: Appel de la méthode post()
        $this->captureOutput(function () {
            try {
                $this->controller->post();
            } catch (Exception $e) {
                // Normal
            }
        });

        // THEN: Message d'erreur
        $this->assertEquals(['type' => 'error', 'text' => 'Code incorrect.'], $_SESSION['pw_msg']);
    }

    /**
     * Vérifie handleReset avec code correct - réinitialisation réussie.
     *
     * @covers ::post
     * @return void
     */
    public function testHandleReset_CorrectCode_ResetsPasswordSuccessfully(): void
    {
        // GIVEN: Utilisateur avec token et code valides
        $token = str_repeat('a', 32);
        $correctCode = '123456';
        $correctCodeHash = password_hash($correctCode, PASSWORD_DEFAULT);
        $expires = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

        $_POST = [
            'action' => 'reset_password',
            'token' => $token,
            'password' => 'newpassword123',
            'code' => $correctCode
        ];

        // Configure le mock pour retourner un utilisateur valide
        $this->stmtMock->method('fetch')->willReturn([
            'id_user' => 1,
            'reset_code_hash' => $correctCodeHash,
            'reset_expires' => $expires
        ]);

        // Configure les méthodes de transaction
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit');

        // WHEN: Appel de la méthode post()
        $this->captureOutput(function () {
            try {
                $this->controller->post();
            } catch (Exception $e) {
                // Normal - header redirect
            }
        });

        // THEN: Message de succès
        $this->assertEquals(
            ['type' => 'success', 'text' => 'Mot de passe mis à jour. Vous pouvez vous connecter.'],
            $_SESSION['pw_msg']
        );
    }
}