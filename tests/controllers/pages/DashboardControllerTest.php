<?php
declare(strict_types=1);

/**
 * PHPUnit tests for the Dashboard controller
 * -------------------------------------
 * These tests verify the behavior of `dashboardController` without depending
 * on a real web environment (no HTTP server).
 *
 * Principles used:
 * - Start/clear the session to isolate each test.
 * - Simulate view display via a static flag `dashboardView::$shown`.
 * - Capture standard output (ob_start/ob_get_clean) when necessary.
 * - Call private methods via Reflection to test `isUserLoggedIn`.
 *
 * Covered objectives:
 * - View display when the user is logged in or not.
 * - Connection state detection (variations of `$_SESSION['email']` values).
 * - Presence of the public `get()` method.
 */

namespace controllers\pages;

use modules\controllers\pages\DashboardController;
use modules\views\pages\dashboardView;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

const PROJECT_ROOT = __DIR__ . '/../../..';

if (!defined('TESTING')) {
    define('TESTING', true);
}

require_once PROJECT_ROOT . '/tests/fake/DashboardView.php';
require_once PROJECT_ROOT . '/app/controllers/pages/DashboardController.php';

final class DashboardControllerTest extends TestCase
{
    /** @var DashboardController Instance of the controller under test. */
    private dashboardController $controller;

    /**
     * Prepares a clean context before each test.
     * - Starts the session if necessary.
     * - Resets the `$_SESSION` superglobal.
     * - Resets the view display flag.
     * - Instantiates the controller.
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

        dashboardView::$shown = false;

        $this->controller = new DashboardController();
    }

    /**
     * Cleans up the state after each test.
     * - Clears the session to avoid state leakage between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Verifies that `get()` displays the view when the user is logged in.
     *
     * Steps:
     * 1) Simulate a logged-in user via `$_SESSION['email']`.
     * 2) Capture output (in case the controller writes).
     * 3) Call `get()`.
     * 4) Verify that the view was displayed (`dashboardView::$shown === true`).
     *
     * @return void
     */
    public function testGet_WhenUserLoggedIn_ShowsView(): void
    {
        $_SESSION['email'] = 'user@example.com';

        ob_start();
        $this->controller->get();
        $output = ob_get_clean();

        $this->assertTrue(dashboardView::$shown, 'La vue doit être affichée quand connecté.');
    }

    /**
     * Verifies that `get()` displays the view even if the user is not logged in.
     * (In this implementation, no `exit` or redirection blocks the display.)
     *
     * @return void
     */
    public function testGet_WhenUserNotLoggedIn_StillShowsView(): void
    {
        unset($_SESSION['email']);

        ob_start();
        $this->controller->get();
        $output = ob_get_clean();

        $this->assertTrue(
            dashboardView::$shown,
            'La vue est affichée même sans connexion (pas de exit dans le controller).'
        );
    }

    /**
     * Verifies that `isUserLoggedIn()` returns `false` when the email is not defined.
     *
     * @return void
     */
    public function testIsUserLoggedIn_ReturnsFalse_WhenEmailNotSet(): void
    {
        unset($_SESSION['email']);

        $result = $this->invokePrivateMethod('isUserLoggedIn');

        $this->assertFalse($result, 'isUserLoggedIn devrait retourner false quand email n\'est pas défini');
    }

    /**
     * Verifies that `isUserLoggedIn()` returns `true` when the email is defined.
     *
     * @return void
     */
    public function testIsUserLoggedIn_ReturnsTrue_WhenEmailIsSet(): void
    {
        $_SESSION['email'] = 'user@example.com';

        $result = $this->invokePrivateMethod('isUserLoggedIn');

        $this->assertTrue($result, 'isUserLoggedIn devrait retourner true quand email est défini');
    }

    /**
     * Demonstrates an edge case: email defined as an empty string.
     * Reminder: `isset($_SESSION['email'])` returns true for an empty string.
     *
     * @return void
     */
    public function testIsUserLoggedIn_WithEmptyEmail(): void
    {
        $_SESSION['email'] = '';

        $result = $this->invokePrivateMethod('isUserLoggedIn');

        $this->assertTrue($result, 'isset() retourne true même pour une chaîne vide');
    }

    /**
     * Demonstrates an edge case: email defined as null.
     * Reminder: `isset($_SESSION['email'])` returns false for `null`.
     *
     * @return void
     */
    public function testIsUserLoggedIn_WithNullEmail(): void
    {
        $_SESSION['email'] = null;

        $result = $this->invokePrivateMethod('isUserLoggedIn');

        $this->assertFalse($result, 'isset() retourne false pour null');
    }

    /**
     * Parameterizes multiple cases for `isUserLoggedIn()` to cover
     * various `$_SESSION['email']` values and their effect on `isset()`.
     *
     * @return void
     */
    public function testIsUserLoggedIn_WithVariousEmailValues(): void
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
     * Verifies the existence of the public `get()` method on the controller.
     *
     * @return void
     */
    public function testGetMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'get'),
            'La méthode get() devrait exister'
        );
    }

    /**
     * Private utility: invokes a (private/protected) method of the controller via Reflection.
     *
     * @param string $methodName Name of the method to invoke.
     * @param array $parameters Parameters passed to the method (default: []).
     *
     * @return mixed Return value of the invoked method.
     */
    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass($this->controller);

        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->controller, $parameters);
    }
}