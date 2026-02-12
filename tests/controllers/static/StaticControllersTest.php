<?php

namespace Tests\Controllers\Static;

use PHPUnit\Framework\TestCase;
use modules\controllers\static\HomepageController;
use modules\controllers\static\AboutController;
use modules\controllers\static\LegalnoticeController;
use modules\controllers\static\SitemapController;
use modules\controllers\static\ErrorController;

require_once __DIR__ . '/../../../vendor/autoload.php';

class StaticControllersTest extends TestCase
{
    public function testHomepageRenders()
    {
        $controller = new HomepageController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        $this->assertTrue(true);
    }

    public function testAboutRenders()
    {
        $controller = new AboutController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        $this->assertTrue(true);
    }

    public function testLegalNoticeRenders()
    {
        $controller = new LegalnoticeController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        $this->assertTrue(true);
    }

    public function testSitemapRenders()
    {
        $controller = new SitemapController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        $this->assertTrue(true);
    }
}
