<?php
declare(strict_types=1);

/**
 * Tests PHPUnit du contrôleur Dashboard
 * -------------------------------------
 * Ces tests vérifient le comportement de `dashboardController` sans dépendre
 * d’un environnement web réel (pas de serveur HTTP).
 *
 * Principes utilisés :
 *  - Démarrer/vider la session pour isoler chaque test.
 *  - Simuler l'affichage de la vue via un flag statique `dashboardView::$shown`.
 *  - Capturer la sortie standard (ob_start/ob_get_clean) lorsque nécessaire.
 *  - Appeler une méthode privée via Reflection pour tester `isUserLoggedIn`.
 *
 * Objectifs couverts :
 *  - Affichage de la vue quand l'utilisateur est connecté ou non.
 *  - Détection de l'état de connexion (variantes de valeurs de `$_SESSION['email']`).
 *  - Présence de la méthode publique `get()`.
 */

namespace controllers\pages;

use modules\controllers\pages\DashboardController;
use modules\controllers\pages\DossierpatientController;
use modules\views\pages\dashboardView;
use modules\views\pages\dossierpatientView;
use PHPUnit\Framework\TestCase;

// Chemin racine du projet utilisé pour les require des fichiers de test et d'app
const PROJECT_ROOT = __DIR__ . '/../../..';

// Active un mode "test" si non défini (permet au code applicatif d'adapter son comportement)
if (!defined('TESTING')) {
    define('TESTING', true);
}

// Charge une vue factice et le contrôleur réel pour isoler la couche à tester
require_once PROJECT_ROOT . '/tests/fake/dossierpatientView.php';
require_once PROJECT_ROOT . '/app/controllers/pages/DossierpatientController.php';

final class DossierpatientControllerTest extends TestCase
{
    /** @var DossierpatientController Instance du contrôleur sous test. */
    private DossierpatientController $controller;

    /**
     * Prépare un contexte propre avant chaque test.
     * - Démarre la session si nécessaire.
     * - Réinitialise la superglobale `$_SESSION`.
     * - Réinitialise le flag d'affichage de la vue.
     * - Instancie le contrôleur.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Démarre la session si absente pour manipuler $_SESSION
        if (session_status() === PHP_SESSION_NONE) {
            @session_start(); // '@' pour éviter du bruit dans la sortie des tests
        }

        // Réinitialise la session pour isoler les tests
        $_SESSION = [];

        // La vue factice expose un flag statique pour savoir si show() a été appelé
        dossierpatientView::$shown = false;

        // Crée une nouvelle instance du contrôleur pour chaque test
        $this->controller = new DossierpatientController();
    }

    /**
     * Nettoie l'état après chaque test.
     * - Vide la session pour éviter les fuites d'état entre tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Réinitialise la session
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Vérifie que `get()` affiche la vue lorsque l'utilisateur est connecté.
     *
     * Étapes :
     *  1) Simule un utilisateur connecté via `$_SESSION['email']`.
     *  2) Capture la sortie (au cas où le contrôleur écrit).
     *  3) Appelle `get()`.
     *  4) Vérifie que la vue a été affichée (`dashboardView::$shown === true`).
     *
     * @return void
     */
    public function testGet_WhenUserLoggedIn_ShowsView(): void
    {
        // Simule un utilisateur connecté
        $_SESSION['email'] = 'user@example.com';

        // Capture la sortie standard pendant l'exécution du contrôleur
        ob_start();
        $this->controller->get();
        $output = ob_get_clean(); // On ne l'utilise pas ici mais on garde la capture propre

        // La vue doit être affichée
        $this->assertTrue(dossierpatientView::$shown, 'La vue doit être affichée quand connecté.');
    }

    /**
     * Vérifie que `get()` affiche la vue même si l'utilisateur n'est pas connecté.
     * (Dans cette implémentation, aucun `exit` ni redirection ne bloque l'affichage.)
     *
     * @return void
     */
    public function testGet_WhenUserNotLoggedIn_StillShowsView(): void
    {
        // S'assure qu'aucun email n'est présent en session
        unset($_SESSION['email']);

        // Capture la sortie et exécute la méthode
        ob_start();
        $this->controller->get();
        $output = ob_get_clean();

        // La vue est tout de même affichée
        $this->assertTrue(
            dossierpatientView::$shown,
            'La vue est affichée même sans connexion (pas de exit dans le controller).'
        );
    }

    /**
     * Vérifie que `isUserLoggedIn()` retourne `false` quand l'email n'est pas défini.
     *
     * @return void
     */
    public function testIsUserLoggedIn_ReturnsFalse_WhenEmailNotSet(): void
    {
        // Retire explicitement l'email de la session
        unset($_SESSION['email']);

        // Appelle la méthode privée via Reflection
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Doit être false en l'absence d'email
        $this->assertFalse($result, 'isUserLoggedIn devrait retourner false quand email n\'est pas défini');
    }

    /**
     * Vérifie que `isUserLoggedIn()` retourne `true` quand l'email est défini.
     *
     * @return void
     */
    public function testIsUserLoggedIn_ReturnsTrue_WhenEmailIsSet(): void
    {
        // Définit un email en session
        $_SESSION['email'] = 'user@example.com';

        // Appelle la méthode privée via Reflection
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Doit être true avec un email présent
        $this->assertTrue($result, 'isUserLoggedIn devrait retourner true quand email est défini');
    }

    /**
     * Montre un cas limite : email défini à chaîne vide.
     * Rappel : `isset($_SESSION['email'])` retourne true pour une chaîne vide.
     *
     * @return void
     */
    public function testIsUserLoggedIn_WithEmptyEmail(): void
    {
        // Définit l'email à une chaîne vide
        $_SESSION['email'] = '';

        // Appelle la méthode privée
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // isset() considère la chaîne vide comme "définie"
        $this->assertTrue($result, 'isset() retourne true même pour une chaîne vide');
    }

    /**
     * Montre un cas limite : email défini à null.
     * Rappel : `isset($_SESSION['email'])` retourne false pour `null`.
     *
     * @return void
     */
    public function testIsUserLoggedIn_WithNullEmail(): void
    {
        // Définit explicitement l'email à null
        $_SESSION['email'] = null;

        // Appelle la méthode privée
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // isset() est false pour null
        $this->assertFalse($result, 'isset() retourne false pour null');
    }

    /**
     * Paramétrise plusieurs cas pour `isUserLoggedIn()` afin de couvrir
     * différentes valeurs de `$_SESSION['email']` et leur effet sur `isset()`.
     *
     * @return void
     */
    public function testIsUserLoggedIn_WithVariousEmailValues(): void
    {
        // Jeux d'essai : [valeur, attendu, description]
        $testCases = [
            ['user@example.com', true, 'Email valide'],
            ['', true, 'Chaîne vide (isset retourne true)'],
            [null, false, 'Null'],
            ['0', true, 'String "0"'],
            [0, true, 'Integer 0'],
            [false, true, 'Boolean false (isset retourne true)'],
        ];

        // Itère sur les cas de test et vérifie le résultat
        foreach ($testCases as [$value, $expected, $description]) {
            // Met à jour la session selon la valeur à tester
            if ($value === null) {
                unset($_SESSION['email']); // null + isset() => false, mais ici on simule la non-définition
            } else {
                $_SESSION['email'] = $value;
            }

            // Appelle la méthode privée
            $result = $this->invokePrivateMethod('isUserLoggedIn');

            // Compare le résultat avec l'attendu
            $this->assertEquals(
                $expected,
                $result,
                "Test échoué pour: $description (valeur: " . var_export($value, true) . ")"
            );
        }
    }

    /**
     * Vérifie l'existence de la méthode publique `get()` sur le contrôleur.
     *
     * @return void
     */
    public function testGetMethodExists(): void
    {
        // S'assure que la méthode `get` est bien déclarée
        $this->assertTrue(
            method_exists($this->controller, 'get'),
            'La méthode get() devrait exister'
        );
    }

    /**
     * Utilitaire privé : invoque une méthode (privée/protégée) du contrôleur via Reflection.
     *
     * @param string $methodName Nom de la méthode à invoquer.
     * @param array $parameters Paramètres passés à la méthode (par défaut: []).
     *
     * @return mixed Valeur de retour de la méthode invoquée.
     */
    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        // Récupère la définition de classe du contrôleur
        $reflection = new \ReflectionClass($this->controller);

        // Récupère la méthode demandée et la rend accessible
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        // Exécute la méthode avec les paramètres fournis
        return $method->invokeArgs($this->controller, $parameters);
    }
}
