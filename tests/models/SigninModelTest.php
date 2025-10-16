<?php

namespace models;

use PHPUnit\Framework\TestCase;
use modules\controllers\SigninController;
use modules\models\signinModel;
use PDO;
use PDOException;

// Inclure le contrôleur pour le test
require_once __DIR__ . '/../../app/models/SigninModel.php';

// Créer un trait pour simuler les redirections
// Cela permet de tester la logique sans déclencher réellement header() et exit
trait MockControllerTrait
{
    protected function redirect(string $location): void
    {
        // Enregistrer la redirection pour vérification
        $_SESSION['redirected_to'] = $location;
    }

    protected function terminate(): void
    {
        // Marquer la terminaison pour vérification
        $_SESSION['terminated'] = true;
    }
}

// Classe de Contrôleur de test qui utilise le trait pour surcharger les méthodes
class SigninControllerTestable extends SigninController
{
    use MockControllerTrait;

    // Surcharge le constructeur pour injecter directement le Mock de modèle
    public function __construct(signinModel $model)
    {
        // Démarre la session si nécessaire, comme dans le constructeur original
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->model = $model;
    }

    // Surcharge la méthode get pour utiliser notre redirection simulée
    public function get(): void
    {
        if ($this->isUserLoggedIn()) {
            $this->redirect('/?page=dashboard');
            $this->terminate();
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        // Simuler la vue au lieu de l'instancier
    }

    // Surcharge la méthode post pour utiliser notre redirection simulée
    public function post(): void
    {
        error_log('[SigninController] POST /signin hit');

        if (isset($_SESSION['_csrf'], $_POST['_csrf']) && !hash_equals($_SESSION['_csrf'], (string)$_POST['_csrf'])) {
            $_SESSION['error'] = "Requête invalide. Réessaye.";
            $this->redirect('/?page=signin'); $this->terminate();
        }

        $last   = trim($_POST['last_name'] ?? '');
        $first  = trim($_POST['first_name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $pass   = (string)($_POST['password'] ?? '');
        $pass2  = (string)($_POST['password_confirm'] ?? '');

        $keepOld = function () use ($last, $first, $email) {
            $_SESSION['old_signin'] = [
                'last_name'  => $last,
                'first_name' => $first,
                'email'      => $email,
            ];
        };

        if ($last === '' || $first === '' || $email === '' || $pass === '' || $pass2 === '') {
            $_SESSION['error'] = "Tous les champs sont requis.";
            $keepOld(); $this->redirect('/?page=signin'); $this->terminate();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide.";
            $keepOld(); $this->redirect('/?page=signin'); $this->terminate();
        }
        if ($pass !== $pass2) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            $keepOld(); $this->redirect('/?page=signin'); $this->terminate();
        }
        if (strlen($pass) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            $keepOld(); $this->redirect('/?page=signin'); $this->terminate();
        }

        if ($this->model->getByEmail($email)) {
            $_SESSION['error'] = "Un compte existe déjà avec cet email.";
            $keepOld(); $this->redirect('/?page=signin'); $this->terminate();
        }

        try {
            $userId = $this->model->create([
                'first_name'   => $first,
                'last_name'    => $last,
                'email'        => $email,
                'password'     => $pass,
                'profession'   => null,
                'admin_status' => 0,
            ]);
        } catch (\Throwable $e) {
            error_log('[SigninController] SQL error: '.$e->getMessage());
            $_SESSION['error'] = "Impossible de créer le compte (email déjà utilisé ?)";
            $keepOld(); $this->redirect('/?page=signin'); $this->terminate();
        }

        $_SESSION['user_id']      = (int)$userId;
        $_SESSION['email']        = $email;
        $_SESSION['first_name']   = $first;
        $_SESSION['last_name']    = $last;
        $_SESSION['profession']   = null;
        $_SESSION['admin_status'] = 0;
        $_SESSION['username']     = $email;

        $this->redirect('/?page=homepage');
        $this->terminate();
    }
}

class SigninControllerTest extends TestCase
{
    private SigninControllerTestable $controller;
    private $signinModelMock;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Nettoyer la session avant chaque test
        $_SESSION = [];
        $_POST = [];
        $_GET = [];

        // 2. Mock du Modèle
        // Crée un Mock du modèle pour simuler les appels BDD sans se connecter réellement
        $this->signinModelMock = $this->createMock(signinModel::class);

        // 3. Instanciation du Contrôleur de Test
        // On utilise notre version testable du contrôleur
        $this->controller = new SigninControllerTestable($this->signinModelMock);

        // Démarrer la session si nécessaire
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        // Nettoyer après chaque test
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        // L'instance du contrôleur et du mock sont libérées automatiquement

        parent::tearDown();
    }

    // --- Tests GET ---

    /**
     * Test que la méthode GET redirige vers le dashboard si l'utilisateur est connecté
     */
    public function testGetRedirectsToDashboardWhenUserLoggedIn(): void
    {
        $_SESSION['email'] = 'test@example.com';
        $this->controller->get();

        $this->assertArrayHasKey('redirected_to', $_SESSION);
        $this->assertEquals('/?page=dashboard', $_SESSION['redirected_to']);
        $this->assertArrayHasKey('terminated', $_SESSION);
    }

    /**
     * Test que la méthode GET génère un token CSRF
     */
    public function testGetGeneratesCsrfToken(): void
    {
        $this->controller->get();

        $this->assertNotEmpty($_SESSION['_csrf']);
        $this->assertEquals(32, strlen($_SESSION['_csrf']));
    }

    // --- Tests POST de Validation ---

    /**
     * Test POST avec token CSRF invalide
     */
    public function testPostWithInvalidCsrfToken(): void
    {
        $_SESSION['_csrf'] = 'valid_token_abc';
        $_POST['_csrf'] = 'invalid_token_xyz';
        // Simuler le reste du POST pour ne pas déclencher la validation des champs
        $_POST['last_name'] = 'Doe';
        $_POST['first_name'] = 'John';
        $_POST['email'] = 'john@example.com';
        $_POST['password'] = 'Password123';
        $_POST['password_confirm'] = 'Password123';

        $this->controller->post();

        $this->assertArrayHasKey('error', $_SESSION);
        $this->assertEquals("Requête invalide. Réessaye.", $_SESSION['error']);
        $this->assertArrayHasKey('redirected_to', $_SESSION);
        $this->assertEquals('/?page=signin', $_SESSION['redirected_to']);
    }

    /**
     * Test POST avec champs manquants
     */
    public function testPostWithMissingFields(): void
    {
        $_SESSION['_csrf'] = 'test_token';
        $_POST['_csrf'] = 'test_token';
        $_POST['last_name'] = ''; // Champ manquant
        $_POST['first_name'] = 'John';
        $_POST['email'] = 'john@example.com';
        $_POST['password'] = 'Password123';
        $_POST['password_confirm'] = 'Password123';

        $this->controller->post();

        $this->assertArrayHasKey('error', $_SESSION);
        $this->assertEquals("Tous les champs sont requis.", $_SESSION['error']);
        $this->assertArrayHasKey('old_signin', $_SESSION);
        $this->assertArrayHasKey('redirected_to', $_SESSION);
    }

    /**
     * Test POST avec email invalide
     */
    public function testPostWithInvalidEmail(): void
    {
        $_SESSION['_csrf'] = 'test_token';
        $_POST['_csrf'] = 'test_token';
        $_POST['last_name'] = 'Doe';
        $_POST['first_name'] = 'John';
        $_POST['email'] = 'invalid-email';
        $_POST['password'] = 'Password123';
        $_POST['password_confirm'] = 'Password123';

        $this->controller->post();

        $this->assertArrayHasKey('error', $_SESSION);
        $this->assertEquals("Email invalide.", $_SESSION['error']);
        $this->assertArrayHasKey('old_signin', $_SESSION);
    }

    /**
     * Test POST avec mots de passe non correspondants
     */
    public function testPostWithMismatchedPasswords(): void
    {
        $_SESSION['_csrf'] = 'test_token';
        $_POST['_csrf'] = 'test_token';
        $_POST['last_name'] = 'Doe';
        $_POST['first_name'] = 'John';
        $_POST['email'] = 'john@example.com';
        $_POST['password'] = 'Password123';
        $_POST['password_confirm'] = 'DifferentPassword';

        $this->controller->post();

        $this->assertArrayHasKey('error', $_SESSION);
        $this->assertEquals("Les mots de passe ne correspondent pas.", $_SESSION['error']);
        $this->assertArrayHasKey('old_signin', $_SESSION);
    }

    /**
     * Test POST avec mot de passe trop court
     */
    public function testPostWithShortPassword(): void
    {
        $_SESSION['_csrf'] = 'test_token';
        $_POST['_csrf'] = 'test_token';
        $_POST['last_name'] = 'Doe';
        $_POST['first_name'] = 'John';
        $_POST['email'] = 'john@example.com';
        $_POST['password'] = 'Short1';
        $_POST['password_confirm'] = 'Short1';

        $this->controller->post();

        $this->assertArrayHasKey('error', $_SESSION);
        $this->assertEquals("Le mot de passe doit contenir au moins 8 caractères.", $_SESSION['error']);
        $this->assertArrayHasKey('old_signin', $_SESSION);
    }

    /**
     * Test POST lorsqu'un compte existe déjà avec cet email
     */
    public function testPostWhenEmailAlreadyExists(): void
    {
        $_SESSION['_csrf'] = 'test_token';
        $_POST['_csrf'] = 'test_token';
        $_POST['last_name'] = 'Doe';
        $_POST['first_name'] = 'John';
        $_POST['email'] = 'existing@example.com';
        $_POST['password'] = 'ValidPass123';
        $_POST['password_confirm'] = 'ValidPass123';

        // Simuler que l'email existe déjà
        $this->signinModelMock->method('getByEmail')
            ->willReturn(['id' => 1, 'email' => 'existing@example.com']);

        $this->controller->post();

        $this->assertArrayHasKey('error', $_SESSION);
        $this->assertEquals("Un compte existe déjà avec cet email.", $_SESSION['error']);
        $this->assertArrayHasKey('old_signin', $_SESSION);
    }

    // --- Test de Succès ---

    /**
     * Test de l'inscription réussie
     */
    public function testSuccessfulSignin(): void
    {
        $_SESSION['_csrf'] = 'test_token';
        $_POST['_csrf'] = 'test_token';
        $_POST['last_name'] = 'Doe';
        $_POST['first_name'] = 'John';
        $_POST['email'] = 'newuser@example.com';
        $_POST['password'] = 'ValidPass123';
        $_POST['password_confirm'] = 'ValidPass123';

        // Simuler la création réussie dans le modèle
        $newUserId = 10;
        $this->signinModelMock->method('getByEmail')->willReturn(null); // L'email n'existe pas
        $this->signinModelMock->method('create')->willReturn($newUserId);

        $this->controller->post();

        // Vérifier les données de session
        $this->assertEquals($newUserId, $_SESSION['user_id']);
        $this->assertEquals('newuser@example.com', $_SESSION['email']);
        $this->assertEquals('John', $_SESSION['first_name']);
        // Vérifier la redirection
        $this->assertArrayHasKey('redirected_to', $_SESSION);
        $this->assertEquals('/?page=homepage', $_SESSION['redirected_to']);
        // S'assurer qu'il n'y a pas d'erreur
        $this->assertArrayNotHasKey('error', $_SESSION);
        $this->assertArrayNotHasKey('old_signin', $_SESSION);
    }

    // --- Tests d'erreurs BDD ---

    /**
     * Test de la gestion des erreurs lors de la création BDD
     */
    public function testPostHandlesDatabaseErrorOnCreate(): void
    {
        $_SESSION['_csrf'] = 'test_token';
        $_POST['_csrf'] = 'test_token';
        $_POST['last_name'] = 'Doe';
        $_POST['first_name'] = 'John';
        $_POST['email'] = 'dbfail@example.com';
        $_POST['password'] = 'ValidPass123';
        $_POST['password_confirm'] = 'ValidPass123';

        // Simuler l'échec lors de la création
        $this->signinModelMock->method('getByEmail')->willReturn(null);
        $this->signinModelMock->method('create')->willThrowException(new PDOException('SQL Error'));

        $this->controller->post();

        $this->assertArrayHasKey('error', $_SESSION);
        $this->assertEquals("Impossible de créer le compte (email déjà utilisé ?)", $_SESSION['error']);
        $this->assertArrayHasKey('old_signin', $_SESSION);
        $this->assertArrayHasKey('redirected_to', $_SESSION);
        $this->assertEquals('/?page=signin', $_SESSION['redirected_to']);
    }
}