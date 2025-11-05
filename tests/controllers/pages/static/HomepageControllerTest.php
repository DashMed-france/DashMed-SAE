<?php

namespace controllers\pages\static;

use modules\controllers\pages\static\homepageController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests PHPUnit du contrôleur Homepage
 * ------------------------------------
 * Ces tests vérifient le comportement du contrôleur `homepageController`
 * de manière isolée, sans serveur ni vue réelle.
 *
 * Objectifs :
 *  - Vérifier la logique d'authentification via `isUserLoggedIn()`.
 *  - S’assurer que les méthodes publiques `get()` et `index()` existent.
 *  - Contrôler la cohérence du comportement selon la présence ou non
 *    d’un email en session.
 *
 * Méthodologie :
 *  - Chaque test démarre avec une session propre (setUp/tearDown).
 *  - Les méthodes privées sont testées via Reflection.
 *  - Aucun affichage réel n’est produit : on vérifie uniquement la logique.
 */
class homepageControllerTest extends TestCase
{
    /** @var homepageController Instance du contrôleur testé. */
    private homepageController $controller;

    /**
     * Prépare l'environnement avant chaque test :
     *  - Instancie le contrôleur.
     *  - Réinitialise la session pour éviter les fuites d’état.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new homepageController();
        $_SESSION = [];
    }

    /**
     * Nettoie la session après chaque test pour garantir l’isolation.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }

    /**
     * Vérifie que `isUserLoggedIn()` retourne true lorsque l'email est défini.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsTrueWhenEmailSet(): void
    {
        // Simule un utilisateur connecté
        $_SESSION['email'] = 'user@example.com';

        // Appelle la méthode privée à tester
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Doit retourner true car l'email est défini
        $this->assertTrue($result);
    }

    /**
     * Vérifie que `isUserLoggedIn()` retourne false si l'email n'est pas défini.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsFalseWhenEmailNotSet(): void
    {
        // Retire explicitement la clé email de la session
        unset($_SESSION['email']);

        // Appelle la méthode privée
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Doit être false en l’absence d’email
        $this->assertFalse($result);
    }

    /**
     * Vérifie que `isUserLoggedIn()` retourne false lorsque la session est vide.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsFalseWhenSessionEmpty(): void
    {
        // Vide complètement la session
        $_SESSION = [];

        // Appelle la méthode privée
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Doit être false car aucun email n’est présent
        $this->assertFalse($result);
    }

    /**
     * Vérifie que `isUserLoggedIn()` retourne false quand l’email vaut null.
     * Rappel : isset() retourne false pour null.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsFalseWhenEmailIsNull(): void
    {
        // Définit explicitement la clé email à null
        $_SESSION['email'] = null;

        // Appelle la méthode privée
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Doit retourner false
        $this->assertFalse($result);
    }

    /**
     * Vérifie le comportement d’`isset()` avec une chaîne vide.
     * Même vide, `isset($_SESSION['email'])` retourne true.
     *
     * @return void
     */
    public function testIsUserLoggedInBehaviorWithEmptyString(): void
    {
        // Définit une chaîne vide
        $_SESSION['email'] = '';

        // Appelle la méthode privée
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Doit retourner true (isset() considère la clé comme définie)
        $this->assertTrue($result, 'isset() retourne true pour une chaîne vide');
    }

    /**
     * Vérifie la présence des méthodes publiques `index()` et `get()`.
     *
     * @return void
     */
    public function testIndexMethodExists(): void
    {
        // La méthode index() doit exister
        $this->assertTrue(
            method_exists($this->controller, 'index'),
            'La méthode index() devrait exister'
        );

        // La méthode get() doit également exister
        $this->assertTrue(
            method_exists($this->controller, 'get'),
            'La méthode get() devrait exister'
        );
    }

    /**
     * Vérifie le comportement attendu lorsque l’utilisateur est connecté :
     * `get()` devrait rediriger vers le dashboard.
     *
     * (Ici, on se limite à vérifier la valeur logique d’`isUserLoggedIn()`.)
     *
     * @return void
     */
    public function testGetBehaviorWhenUserLoggedIn(): void
    {
        // Simule un utilisateur connecté
        $_SESSION['email'] = 'user@example.com';

        // Vérifie la logique d’authentification
        $isLoggedIn = $this->invokePrivateMethod('isUserLoggedIn');

        // Doit être true
        $this->assertTrue(
            $isLoggedIn,
            'Quand email est défini, get() devrait rediriger vers le dashboard'
        );
    }

    /**
     * Vérifie le comportement attendu lorsque l’utilisateur n’est pas connecté :
     * `get()` devrait afficher la vue d’accueil.
     *
     * @return void
     */
    public function testGetBehaviorWhenUserNotLoggedIn(): void
    {
        // Supprime toute trace d’email en session
        unset($_SESSION['email']);

        // Vérifie la logique de connexion
        $isLoggedIn = $this->invokePrivateMethod('isUserLoggedIn');

        // Doit être false
        $this->assertFalse(
            $isLoggedIn,
            'Quand email n\'est pas défini, get() devrait afficher la vue'
        );
    }

    /**
     * Test paramétré pour plusieurs valeurs possibles de `$_SESSION['email']`.
     * Permet de valider le comportement exact d’`isset()` pour chaque cas.
     *
     * @return void
     */
    public function testIsUserLoggedInWithVariousEmailValues(): void
    {
        // Table de tests : [valeur, résultat attendu, description]
        $testCases = [
            ['user@example.com', true, 'Email valide'],
            ['', true, 'Chaîne vide (isset retourne true)'],
            [null, false, 'Null'],
            ['0', true, 'String "0"'],
            [0, true, 'Integer 0'],
            [false, true, 'Boolean false (isset retourne true)'],
        ];

        // Boucle sur chaque cas pour vérifier le résultat attendu
        foreach ($testCases as [$value, $expected, $description]) {
            if ($value === null) {
                unset($_SESSION['email']); // Simule l’absence de clé
            } else {
                $_SESSION['email'] = $value;
            }

            // Appelle la méthode privée
            $result = $this->invokePrivateMethod('isUserLoggedIn');

            // Compare le résultat avec l’attendu
            $this->assertEquals(
                $expected,
                $result,
                "Test échoué pour: $description (valeur: " . var_export($value, true) . ")"
            );
        }
    }

    /**
     * Utilitaire interne : permet d’appeler une méthode privée ou protégée
     * via Reflection. Utile pour tester la logique interne sans la rendre publique.
     *
     * @param string $methodName Nom de la méthode à invoquer.
     * @param array  $args       Arguments éventuels à passer à la méthode.
     *
     * @return mixed Résultat de l’exécution de la méthode.
     */
    private function invokePrivateMethod(string $methodName, array $args = [])
    {
        // Récupère la classe du contrôleur
        $reflection = new ReflectionClass($this->controller);

        // Accède à la méthode souhaitée
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        // Exécute la méthode avec les arguments fournis
        return $method->invokeArgs($this->controller, $args);
    }
}
