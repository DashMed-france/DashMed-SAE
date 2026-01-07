<?php

namespace controllers\pages\static;

use modules\controllers\pages\static\AboutController;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../app/controllers/pages/static/AboutController.php';

class AboutControllerTest extends TestCase
{
    public function testGetMethodExists(): void
    {
        $controller = new AboutController();
        $this->assertTrue(method_exists($controller, 'get'));
    }

    public function testGetMethodReturnsVoid(): void
    {
        $controller = new AboutController();
        ob_start();
        $controller->get();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, "La méthode get doit afficher le contenu de la page À Propos.");
    }
}
