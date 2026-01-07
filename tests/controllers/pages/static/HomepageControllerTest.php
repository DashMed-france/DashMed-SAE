<?php

namespace controllers\pages\static;

use modules\controllers\pages\static\homepageController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * PHPUnit tests for the Homepage controller
 * ------------------------------------
 * These tests verify the behavior of the `homepageController`
 * in isolation, without a real server or real views.
 *
 * Objectives:
 * - Verify authentication logic via `isUserLoggedIn()`.
 * - Ensure that public methods `get()` and `index()` exist.
 * - Control behavior consistency based on the presence or absence
 * of an email in the session.
 *
 * Methodology:
 * - Each test starts with a clean session (setUp/tearDown).
 * - Private methods are tested via Reflection.
 * - No real output is produced: only the logic is verified.
 */
class homepageControllerTest extends TestCase
{
    /** @var homepageController Instance of the tested controller. */
    private homepageController $controller;

    /**
     * Prepares the environment before each test:
     * - Instantiates the controller.
     * - Resets the session to avoid state leakage.
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
     * Cleans up the session after each test to guarantee isolation.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }

    /**
     * Verifies that `isUserLoggedIn()` returns true when the email is set.
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
     * Verifies that `isUserLoggedIn()` returns false if the email is not set.
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
     * Verifies that `isUserLoggedIn()` returns false when the session is empty.
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
     * Verifies that `isUserLoggedIn()` returns false when the email is null.
     * Reminder: isset() returns false for null.
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
     * Verifies the behavior of `isset()` with an empty string.
     * Even if empty, `isset($_SESSION['email'])` returns true.
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
     * Verifies the presence of the public methods `index()` and `get()`.
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
     * Verifies the expected behavior when the user is logged in:
     * `get()` should redirect to the dashboard.
     *
     * (Here, we limit verification to the logical value of `isUserLoggedIn()`.)
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
     * Verifies the expected behavior when the user is not logged in:
     * `get()` should display the homepage view.
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
     * Parameterized test for several possible values of `$_SESSION['email']`.
     * Validates the exact behavior of `isset()` for each case.
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
     * Internal utility: allows calling a private or protected method via Reflection.
     * Useful for testing internal logic without making it public.
     *
     * @param string $methodName Name of the method to invoke.
     * @param array  $args       Possible arguments to pass to the method.
     *
     * @return mixed Result of the method execution.
     */
    private function invokePrivateMethod(string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->controller, $args);
    }
}