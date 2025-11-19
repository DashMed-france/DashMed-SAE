<?php

namespace controllers\auth;

use modules\controllers\auth\LoginController;
use modules\models\userModel;
use modules\views\auth\LoginView;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;
use ReflectionClass;
use ReflectionMethod;
use const PHP_SESSION_NONE;

require_once __DIR__ . '/../../../app/controllers/auth/LoginController.php';
require_once __DIR__ . '/../../../app/models/UserModel.php';
require_once __DIR__ . '/../../../app/views/auth/LoginView.php';

/**
 * Tests PHPUnit du contrôleur Login
 * ---------------------------------
 * Ces tests valident le comportement du contrôleur `LoginController`
 * en conditions réelles de session et de requêtes HTTP simulées.
 *
 * Objectifs :
 *  - Vérifier l'affichage correct de la page de connexion.
 *  - S'assurer que le token CSRF est bien généré côté serveur.
 *  - Contrôler la logique interne d'authentification (`isUserLoggedIn()`).
 *  - Vérifier que la liste des utilisateurs est bien récupérée et passée à la vue.
 *
 * Méthodologie :
 *  - La session et les superglobales PHP (`$_POST`, `$_SERVER`) sont réinitialisées avant chaque test.
 *  - Les sorties HTML sont capturées via `ob_start()` pour éviter tout affichage réel.
 *  - Les méthodes privées sont testées via Reflection pour évaluer la logique interne.
 *  - Les dépendances (Database, PDO) sont mockées pour isoler les tests.
 */
class LoginControllerTest extends TestCase
{
    private $pdoMock;
    private $stmtMock;
    private int $initialObLevel;

    /**
     * Prépare un environnement propre avant chaque test :
     *  - Démarre la session si nécessaire.
     *  - Réinitialise les superglobales ($_SESSION, $_POST, $_SERVER).
     *  - Crée les mocks pour la base de données.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Enregistre le niveau initial des buffers
        $this->initialObLevel = ob_get_level();

        // Démarre la session uniquement si elle n'existe pas
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Vide la session et les données POST
        $_SESSION = [];
        $_POST    = [];

        // Simule une requête HTTP de type GET par défaut
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Crée un mock pour PDOStatement
        $this->stmtMock = $this->createMock(PDOStatement::class);

        // Crée un mock pour PDO
        $this->pdoMock = $this->createMock(PDO::class);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
    }

    /**
     * Nettoie toutes les variables globales après chaque test
     * pour garantir l'isolation entre les scénarios.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST    = [];
        $_SERVER  = [];

        // Restaure le niveau de buffer initial uniquement
        while (ob_get_level() > $this->initialObLevel) {
            ob_end_clean();
        }

        parent::tearDown();
    }

    /**
     * Crée une instance réelle de LoginController en mockant Database::getInstance()
     *
     * @return LoginController
     */
    private function createControllerWithMockedDatabaseInstance(): LoginController
    {
        // Mock la classe Database si elle n'existe pas
        if (!class_exists('Database')) {
            eval('
                class Database {
                    private static $instance;
                    public static function getInstance() {
                        return self::$instance;
                    }
                    public static function setInstance($pdo) {
                        self::$instance = $pdo;
                    }
                }
            ');
        }

        // Configure le mock pour retourner notre PDO mocké
        \Database::setInstance($this->pdoMock);

        return new LoginController();
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
     * Vérifie que `get()` affiche la page de connexion
     * lorsque l'utilisateur **n'est pas connecté**.
     *
     * Étapes :
     *  1) Supprime la clé `email` de la session.
     *  2) Capture la sortie générée par le contrôleur.
     *  3) Vérifie que du contenu a bien été produit (vue affichée).
     *
     * @return void
     */
    public function testGet_ShowsLoginPage_WhenNotLoggedIn(): void
    {
        // Configure le mock pour retourner des utilisateurs
        $this->stmtMock->method('fetchAll')->willReturn([
            ['email' => 'test@example.com', 'first_name' => 'John', 'last_name' => 'Doe']
        ]);

        // Supprime la variable de session pour simuler un utilisateur déconnecté
        unset($_SESSION['email']);

        // Capture la sortie générée par la méthode get()
        $output = $this->captureOutput(function () {
            $controller = $this->createControllerWithMockedDatabaseInstance();
            $controller->get();
        });

        // Vérifie que la vue a généré du contenu HTML
        $this->assertNotEmpty($output, 'La vue devrait générer du contenu');
        $this->assertStringContainsString('Se connecter', $output, 'La page devrait contenir le titre "Se connecter"');
    }

    /**
     * Vérifie que `get()` génère bien un token CSRF unique
     * et le stocke dans la session.
     *
     * Étapes :
     *  1) Supprime toute clé CSRF existante.
     *  2) Appelle la méthode get().
     *  3) Vérifie la présence et la validité du token dans $_SESSION['_csrf'].
     *
     * @return void
     */
    public function testGet_GeneratesCsrfToken(): void
    {
        // Configure le mock pour retourner des utilisateurs
        $this->stmtMock->method('fetchAll')->willReturn([]);

        // Supprime tout token CSRF précédent
        unset($_SESSION['_csrf']);
        unset($_SESSION['email']); // S'assure que l'utilisateur n'est pas connecté

        // Exécute la méthode get() en capturant sa sortie
        $this->captureOutput(function () {
            $controller = $this->createControllerWithMockedDatabaseInstance();
            $controller->get();
        });

        // Vérifie que le token CSRF a bien été créé
        $this->assertArrayHasKey('_csrf', $_SESSION, 'Le token CSRF doit être présent dans la session');
        $this->assertIsString($_SESSION['_csrf'], 'Le token CSRF doit être une chaîne');
        $this->assertSame(32, strlen($_SESSION['_csrf']), 'Le token CSRF doit faire 32 caractères');
    }

    /**
     * Vérifie que `get()` ne régénère pas le token CSRF
     * s'il existe déjà dans la session.
     *
     * @return void
     */
    public function testGet_KeepsExistingCsrfToken(): void
    {
        // Configure le mock
        $this->stmtMock->method('fetchAll')->willReturn([]);

        // Définit un token CSRF existant
        $existingToken = 'existing_token_1234567890abcdef';
        $_SESSION['_csrf'] = $existingToken;
        unset($_SESSION['email']);

        // Exécute la méthode get()
        $this->captureOutput(function () {
            $controller = $this->createControllerWithMockedDatabaseInstance();
            $controller->get();
        });

        // Vérifie que le token n'a pas été modifié
        $this->assertSame($existingToken, $_SESSION['_csrf'], 'Le token CSRF existant doit être conservé');
    }

    /**
     * Vérifie que `isUserLoggedIn()` retourne true
     * lorsque l'utilisateur a un email défini en session.
     *
     * @return void
     */
    public function testIsUserLoggedIn_ReturnsTrue_WhenEmailSet(): void
    {
        // Simule un utilisateur connecté
        $_SESSION['email'] = 'user@example.com';

        // Instancie le contrôleur
        $controller = $this->createControllerWithMockedDatabaseInstance();

        // Accède à la méthode privée via Reflection
        $ref = new ReflectionMethod($controller, 'isUserLoggedIn');
        $ref->setAccessible(true);

        // Doit retourner true car un email est défini
        $this->assertTrue($ref->invoke($controller), 'Devrait retourner true quand email est défini');
    }

    /**
     * Vérifie que `isUserLoggedIn()` retourne false
     * lorsque l'utilisateur n'a pas d'email défini en session.
     *
     * @return void
     */
    public function testIsUserLoggedIn_ReturnsFalse_WhenEmailNotSet(): void
    {
        // S'assure qu'aucun email n'est défini
        unset($_SESSION['email']);

        // Instancie le contrôleur
        $controller = $this->createControllerWithMockedDatabaseInstance();

        // Accède à la méthode privée via Reflection
        $ref = new ReflectionMethod($controller, 'isUserLoggedIn');
        $ref->setAccessible(true);

        // Doit retourner false car aucun email n'est défini
        $this->assertFalse($ref->invoke($controller), 'Devrait retourner false quand email n\'est pas défini');
    }

    /**
     * Vérifie que la méthode `logout()` détruit complètement la session
     * et redirige vers la page de connexion.
     *
     * @return void
     */
    public function testLogout_DestroysSessionAndRedirects(): void
    {
        // Simule un utilisateur connecté avec plusieurs données en session
        $_SESSION['email'] = 'user@example.com';
        $_SESSION['user_id'] = 42;
        $_SESSION['admin_status'] = 1;

        // Exécute la méthode logout()
        $this->captureOutput(function () {
            $controller = $this->createControllerWithMockedDatabaseInstance();
            $controller->logout();
        });

        // Vérifie que la session a été vidée
        $this->assertEmpty($_SESSION, 'La session devrait être complètement vidée après logout');
    }

    /**
     * Vérifie que la liste des utilisateurs est récupérée
     * et contient les données attendues.
     *
     * @return void
     */
    public function testGet_PassesUsersToView(): void
    {
        // Configure le mock pour retourner des utilisateurs
        $this->stmtMock->method('fetchAll')->willReturn([
            ['email' => 'user1@example.com', 'first_name' => 'John', 'last_name' => 'Doe'],
            ['email' => 'user2@example.com', 'first_name' => 'Jane', 'last_name' => 'Smith']
        ]);

        // Supprime l'email pour simuler un utilisateur non connecté
        unset($_SESSION['email']);

        // Capture la sortie
        $output = $this->captureOutput(function () {
            $controller = $this->createControllerWithMockedDatabaseInstance();
            $controller->get();
        });

        // Vérifie que la structure HTML pour afficher les utilisateurs est présente
        $this->assertStringContainsString('user-list', $output, 'La vue devrait contenir la liste des utilisateurs');
        $this->assertStringContainsString('user-card', $output, 'La vue devrait contenir des cartes utilisateur');
    }
}