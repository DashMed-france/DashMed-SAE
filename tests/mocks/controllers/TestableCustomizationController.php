<?php

namespace controllers\pages;

use modules\controllers\pages\CustomizationController;
use ReflectionClass;

require_once __DIR__ . '/../../../assets/includes/Database.php';
require_once __DIR__ . '/../../../app/services/UserLayoutService.php';
require_once __DIR__ . '/../../../app/controllers/pages/CustomizationController.php';

/**
 * TestableCustomizationController | Contrôleur de Personnalisation Testable
 *
 * Version testable du contrôleur.
 * Surcharge get et post pour capturer les redirections et éviter les exit.
 */
final class TestableCustomizationController extends CustomizationController
{
    public string $redirectUrl = '';
    public bool $exitCalled = false;
    public string $renderedOutput = '';

    public $testLayoutService;

    private $testPdo;

    public function __construct($pdo, $prefModel)
    {
        parent::__construct($pdo);
        $this->testPdo = $pdo;

        $this->testLayoutService = new \modules\services\UserLayoutService($prefModel);

        $ref = new ReflectionClass(CustomizationController::class);
        $p = $ref->getProperty('layoutService');
        $p->setAccessible(true);
        $p->setValue($this, $this->testLayoutService);
    }

    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            $this->redirectUrl = '/?page=signup';
            $this->exitCalled = true;
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0 && isset($_SESSION['email'])) {
            $stmt = $this->testPdo->prepare("SELECT id FROM users WHERE email = :e");
            $stmt->execute([':e' => $_SESSION['email']]);
            $fetchedId = $stmt->fetchColumn();
            if ($fetchedId) {
                $_SESSION['user_id'] = $fetchedId;
                $userId = (int) $fetchedId;
            }
        }

        if ($userId <= 0) {
            $this->redirectUrl = '/?page=signup';
            $this->exitCalled = true;
            return;
        }

        $data = $this->testLayoutService->buildWidgetsForCustomization($userId);

        ob_start();
        $view = new \modules\views\pages\CustomizationView();
        $view->show($data['widgets'], $data['hidden']);
        $this->renderedOutput = ob_get_clean();
    }
}
