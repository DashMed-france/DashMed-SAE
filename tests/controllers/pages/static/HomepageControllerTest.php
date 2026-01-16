<?php

namespace controllers\pages\static;

use modules\controllers\pages\static\homepageController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class homepageControllerTest | Tests du Contrôleur d'Accueil
 *
 * Unit tests for homepageController.
 * Tests unitaires pour homepageController.
 *
 * Checks authentication logic and public methods existence.
 * Vérifie la logique d'authentification et l'existence des méthodes publiques.
 *
 * @package Tests\Controllers\Pages\Static
 * @author DashMed Team
 */
class HomepageControllerTest extends TestCase
{
    /** @var homepageController Controller instance under test. | Instance du contrôleur testé. */
    private homepageController $controller;

    /**
     * Setup.
     * Configuration.
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
     * Teardown.
     * Nettoyage.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }

    /**
     * Test isUserLoggedIn returns true when email is set.
     * Vérifie que `isUserLoggedIn()` retourne true lorsque l'email est défini.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsTrueWhenEmailSet(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertTrue($result);
    }

    /**
     * Test isUserLoggedIn returns false when email is not set.
     * Vérifie que `isUserLoggedIn()` retourne false si l'email n'est pas défini.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsFalseWhenEmailNotSet(): void
    {
        unset($_SESSION['email']);
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertFalse($result);
    }

    /**
     * Test isUserLoggedIn returns false when session is empty.
     * Vérifie que `isUserLoggedIn()` retourne false lorsque la session est vide.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsFalseWhenSessionEmpty(): void
    {
        $_SESSION = [];
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertFalse($result);
    }

    /**
     * Test isUserLoggedIn returns false when email is null.
     * Vérifie que `isUserLoggedIn()` retourne false quand l’email vaut null.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsFalseWhenEmailIsNull(): void
    {
        $_SESSION['email'] = null;
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertFalse($result);
    }

    /**
     * Test behavior with empty string.
     * Vérifie le comportement d’`isset()` avec une chaîne vide (retourne true).
     *
     * @return void
     */
    public function testIsUserLoggedInBehaviorWithEmptyString(): void
    {
        $_SESSION['email'] = '';
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertTrue($result, 'isset() retourne true pour une chaîne vide');
    }

    /**
     * Test index and get methods exist.
     * Vérifie la présence des méthodes publiques `index()` et `get()`.
     *
     * @return void
     */
    public function testIndexMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'index'),
            'La méthode index() devrait exister'
        );

        $this->assertTrue(
            method_exists($this->controller, 'get'),
            'La méthode get() devrait exister'
        );
    }

    /**
     * Test get behavior when logged in.
     * Vérifie le comportement attendu lorsque l’utilisateur est connecté.
     *
     * @return void
     */
    public function testGetBehaviorWhenUserLoggedIn(): void
    {
        $_SESSION['email'] = 'user@example.com';
        $isLoggedIn = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertTrue(
            $isLoggedIn,
            'Quand email est défini, get() devrait rediriger vers le dashboard'
        );
    }

    /**
     * Test get behavior when not logged in.
     * Vérifie le comportement attendu lorsque l’utilisateur n’est pas connecté.
     *
     * @return void
     */
    public function testGetBehaviorWhenUserNotLoggedIn(): void
    {
        unset($_SESSION['email']);
        $isLoggedIn = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertFalse(
            $isLoggedIn,
            'Quand email n\'est pas défini, get() devrait afficher la vue'
        );
    }

    /**
     * Parameterized test for isUserLoggedIn.
     * Test paramétré pour plusieurs valeurs possibles de `$_SESSION['email']`.
     *
     * @return void
     */
    public function testIsUserLoggedInWithVariousEmailValues(): void
    {
        $testCases = [
            ['user@example.com', true, 'Email valide'],
            ['', true, 'Chaîne vide (isset retourne true)'],
            [null, false, 'Null'],
            ['0', true, 'String "0"'],
            [0, true, 'Integer 0'],
            [false, true, 'Boolean false (isset retourne true)'],
        ];

        foreach ($testCases as [$value, $expected, $description]) {
            if ($value === null) {
                unset($_SESSION['email']);
            } else {
                $_SESSION['email'] = $value;
            }

            $result = $this->invokePrivateMethod('isUserLoggedIn');

            $this->assertEquals(
                $expected,
                $result,
                "Test échoué pour: $description (valeur: " . var_export($value, true) . ")"
            );
        }
    }

    /**
     * Internal helper to invoke private methods via Reflection.
     * Utilitaire interne : permet d’appeler une méthode privée ou protégée via Reflection.
     *
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    private function invokePrivateMethod(string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->controller, $args);
    }
}
