<?php

namespace controllers\pages\static;

use modules\controllers\pages\static\AboutController;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../app/controllers/pages/static/AboutController.php';

/**
 * Class AboutControllerTest | Tests Contrôleur À Propos
 *
 * Unit tests for AboutController.
 * Tests unitaires pour AboutController.
 *
 * @package Tests\Controllers\Pages\Static
 * @author DashMed Team
 */
class AboutControllerTest extends TestCase
{
    /**
     * Test GET method exists.
     * Teste que la méthode GET existe.
     */
    public function testGetMethodExists(): void
    {
        $controller = new AboutController();
        $this->assertTrue(method_exists($controller, 'get'));
    }

    /**
     * Test GET displays content.
     * Teste que la méthode GET affiche le contenu.
     */
    public function testGetMethodReturnsVoid(): void
    {
        $controller = new AboutController();
        ob_start();
        $controller->get();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, "La méthode get doit afficher le contenu de la page À Propos.");
    }
}
