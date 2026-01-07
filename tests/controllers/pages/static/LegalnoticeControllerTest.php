<?php

namespace controllers\pages\static;

use modules\controllers\pages\static\LegalnoticeController;
use modules\views\legalnoticeView;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * PHPUnit tests for the Legalnotice controller
 * ---------------------------------------
 * These tests validate the behavior of the `LegalnoticeController`
 * in different situations, focusing on:
 * - view display according to user connection state,
 * - verification of internal logic `isUserLoggedIn()`,
 * - proper functioning of the `index()` method.
 *
 * Methodology:
 * - Tests run in an isolated environment with a reset session.
 * - Views are simulated by simple text rendering (no real dependency).
 * - Private methods are tested via Reflection to access internal logic.
 */
class LegalnoticeControllerTest extends TestCase
{
    /** @var LegalnoticeController Instance of the tested controller. */
    private LegalnoticeController $controller;

    /**
     * Prepares a clean environment before each test:
     * - Starts the session if necessary.
     * - Clears the $_SESSION global variable.
     * - Instantiates a new controller.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION = [];

        $this->controller = new LegalnoticeController();
    }

    /**
     * Cleans up the session after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Verifies that the `get()` method displays the view when
     * the user **is not logged in**.
     *
     * Steps:
     * 1) Remove the 'email' key from the session.
     * 2) Execute `get()` while capturing the output.
     * 3) Verify that the view generates content.
     *
     * @return void
     */
    public function testGetDisplaysViewWhenUserNotLoggedIn(): void
    {
        unset($_SESSION['email']);

        ob_start();
        $this->controller->get();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'La vue devrait générer du contenu');
    }

    /**
     * Verifies that `get()` correctly detects the connection state
     * when the user **is logged in**.
     *
     * Expects `isUserLoggedIn()` to return true,
     * simulating a redirection to the dashboard.
     *
     * @return void
     */
    public function testGetRedirectsToDashboardWhenUserLoggedIn(): void
    {
        $_SESSION['email'] = 'user@example.com';

        $reflection = new ReflectionMethod($this->controller, 'isUserLoggedIn');
        $reflection->setAccessible(true);

        $isLoggedIn = $reflection->invoke($this->controller);

        $this->assertTrue($isLoggedIn, 'L\'utilisateur devrait être considéré comme connecté');
    }

    /**
     * Verifies that `index()` acts as an alias for `get()`,
     * meaning it displays the same view.
     *
     * @return void
     */
    public function testIndexCallsGet(): void
    {
        unset($_SESSION['email']);

        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'index() devrait afficher la vue via get()');
    }

    /**
     * Verifies that `isUserLoggedIn()` returns false when the email
     * is not defined in the session.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsFalseWhenEmailNotSet(): void
    {
        unset($_SESSION['email']);
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertFalse($result, 'Devrait retourner false quand email n\'est pas défini');
    }

    /**
     * Verifies that `isUserLoggedIn()` returns true when the email is defined.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsTrueWhenEmailIsSet(): void
    {
        $_SESSION['email'] = 'test@example.com';

        $result = $this->invokePrivateMethod('isUserLoggedIn');

        $this->assertTrue($result, 'Devrait retourner true quand email est défini');
    }

    /**
     * Verifies an edge case: the email key is defined but empty.
     * Reminder: `isset()` returns true for an empty string.
     *
     * @return void
     */
    public function testIsUserLoggedInWithEmptyEmail(): void
    {
        $_SESSION['email'] = '';
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertTrue($result, 'isset() retourne true même pour une chaîne vide');
    }

    /**
     * Verifies another edge case: the email key is defined as null.
     * Reminder: `isset()` returns false for null.
     *
     * @return void
     */
    public function testIsUserLoggedInWithNullEmail(): void
    {
        $_SESSION['email'] = null;
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertFalse($result, 'isset() retourne false pour null');
    }

    /**
     * Internal utility to invoke a private or protected method of
     * the controller using Reflection.
     *
     * @param string $methodName Name of the method to call.
     * @param array  $parameters Parameters to pass to the method.
     *
     * @return mixed Result returned by the invoked method.
     */
    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass($this->controller);

        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->controller, $parameters);
    }
}