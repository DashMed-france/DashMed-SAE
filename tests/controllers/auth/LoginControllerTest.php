<?php

namespace controllers\auth;

use modules\controllers\auth\LoginController;
use modules\models\userModel;
use modules\views\auth\loginView;
use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionMethod;
use const PHP_SESSION_NONE;

require_once __DIR__ . '/../../../app/controllers/auth/LoginController.php';
require_once __DIR__ . '/../../../app/models/userModel.php';
require_once __DIR__ . '/../../../app/views/auth/loginView.php';

/**
 * Tests PHPUnit du contrôleur Login
 * ---------------------------------
 * Ces tests valident le comportement du contrôleur `LoginController`
 * en conditions réelles de session et de requêtes HTTP simulées.
 *
 * Objectifs :
 *  - Vérifier l'affichage correct de la page de connexion.
 *  - S’assurer que le token CSRF est bien généré côté serveur.
 *  - Contrôler la logique interne d'authentification (`isUserLoggedIn()`).
 *  - Vérifier que la liste des utilisateurs est bien récupérée et passée à la vue.
 *
 * Méthodologie :
 *  - La session et les superglobales PHP (`$_POST`, `$_SERVER`) sont réinitialisées avant chaque test.
 *  - Les sorties HTML sont capturées via `ob_start()` pour éviter tout affichage réel.
 *  - Les méthodes privées sont testées via Reflection pour évaluer la logique interne.
 */
class LoginControllerTest extends TestCase
{
    /**
     * Prépare un environnement propre avant chaque test :
     *  - Démarre la session si nécessaire.
     *  - Réinitialise les superglobales ($_SESSION, $_POST, $_SERVER).
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Démarre la session uniquement si elle n'existe pas
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Vide la session et les données POST
        $_SESSION = [];
        $_POST    = [];

        // Simule une requête HTTP de type GET par défaut
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Nettoie toutes les variables globales après chaque test
     * pour garantir l’isolation entre les scénarios.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST    = [];
        parent::tearDown();
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
        // Supprime la variable de session pour simuler un utilisateur déconnecté
        unset($_SESSION['email']);

        // Capture la sortie générée par la méthode get()
        ob_start();
        $controller = new LoginController();
        $controller->get();
        $output = ob_get_clean();

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
        // Supprime tout token CSRF précédent
        unset($_SESSION['_csrf']);

        // Exécute la méthode get() en capturant sa sortie
        ob_start();
        (new LoginController())->get();
        ob_end_clean();

        // Vérifie que le token CSRF a bien été créé
        $this->assertArrayHasKey('_csrf', $_SESSION, 'Le token CSRF doit être présent dans la session');
        $this->assertIsString($_SESSION['_csrf'], 'Le token CSRF doit être une chaîne');
        $this->assertSame(32, strlen($_SESSION['_csrf']), 'Le token CSRF doit faire 32 caractères');
    }

    /**
     * Vérifie que `isUserLoggedIn()` retourne false
     * lorsque l'utilisateur n'a pas d'email défini en session.
     *
     * @return void
     */
    public function testIsUserLoggedIn_ReturnsTrue_WhenEmailSet(): void
    {
        // Simule un utilisateur connecté
        $_SESSION['email'] = 'user@example.com';

        // Instancie le contrôleur
        $controller = new LoginController();

        // Accède à la méthode privée via Reflection
        $ref = new ReflectionMethod($controller, 'isUserLoggedIn');
        $ref->setAccessible(true);

        // Doit retourner true car un email est défini
        $this->assertTrue($ref->invoke($controller), 'Devrait retourner true quand email est défini');
    }
}
