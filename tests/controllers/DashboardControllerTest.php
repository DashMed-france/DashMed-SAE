<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// ----- constantes chemin -----
const PROJECT_ROOT = __DIR__ . '/..' . '/..';

// Indique au contrôleur qu'on est en test (évite exit;)
if (!defined('TESTING')) {
    define('TESTING', true);
}

// Fake de la vue d'abord (pour ne pas charger la vraie)
require_once PROJECT_ROOT . '/tests/fake/dashboardView.php';

// Contrôleur réel
require_once PROJECT_ROOT . '/app/controllers/DashboardController.php';

use modules\controllers\dashboardController;
use modules\views\dashboardView;

final class DashboardControllerTest extends TestCase
{
    private dashboardController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Démarrer la session si elle n'est pas active
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Réinitialiser la session
        $_SESSION = [];

        // reset fake view flag
        dashboardView::$shown = false;

        $this->controller = new dashboardController();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testGet_WhenUserLoggedIn_ShowsView(): void
    {
        // Arrange
        $_SESSION['email'] = 'user@example.com';

        // Act
        ob_start();
        $this->controller->get();
        $output = ob_get_clean();

        // Assert
        $this->assertTrue(dashboardView::$shown, 'La vue doit être affichée quand connecté.');
    }

    public function testGet_WhenUserNotLoggedIn_StillShowsView(): void
    {
        // Arrange
        unset($_SESSION['email']);

        // Act
        ob_start();
        $this->controller->get();
        $output = ob_get_clean();

        // Assert
        // Le controller envoie un header mais n'a pas de exit(), donc la vue s'affiche quand même
        $this->assertTrue(dashboardView::$shown, 'La vue est affichée même sans connexion (pas de exit dans le controller).');
    }

    public function testIsUserLoggedIn_ReturnsFalse_WhenEmailNotSet(): void
    {
        // Arrange
        unset($_SESSION['email']);

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertFalse($result, 'isUserLoggedIn devrait retourner false quand email n\'est pas défini');
    }

    public function testIsUserLoggedIn_ReturnsTrue_WhenEmailIsSet(): void
    {
        // Arrange
        $_SESSION['email'] = 'user@example.com';

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertTrue($result, 'isUserLoggedIn devrait retourner true quand email est défini');
    }

    public function testIsUserLoggedIn_WithEmptyEmail(): void
    {
        // Arrange
        $_SESSION['email'] = '';

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        // isset() retourne true même pour une chaîne vide
        $this->assertTrue($result, 'isset() retourne true même pour une chaîne vide');
    }

    public function testIsUserLoggedIn_WithNullEmail(): void
    {
        // Arrange
        $_SESSION['email'] = null;

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        // isset() retourne false pour null
        $this->assertFalse($result, 'isset() retourne false pour null');
    }

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
                unset($_SESSION['email']); // Pour null, on unset
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

    public function testGetMethodExists(): void
    {
        // Assert
        $this->assertTrue(
            method_exists($this->controller, 'get'),
            'La méthode get() devrait exister'
        );
    }

    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->controller, $parameters);
    }
}