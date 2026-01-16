<?php

namespace controllers\pages;

use modules\controllers\pages\SysadminController;
use modules\models\UserModel;
use ReflectionClass;
use RuntimeException;

use function session_start;
use function session_status;

use const PHP_SESSION_ACTIVE;

require_once __DIR__ . '/../../../app/controllers/pages/SysadminController.php';
require_once __DIR__ . '/../../../app/models/UserModel.php';

/**
 * Class TestableSysadminController
 *
 * Testable extension of SysadminController.
 * Extension testable de SysadminController.
 */
class TestableSysadminController extends SysadminController
{
    public string $redirectLocation = '';
    private $testModel;
    private $testPdo;

    public function __construct(UserModel $model, \PDO $pdo)
    {
        $this->testModel = $model;
        $this->testPdo = $pdo;

        $ref = new ReflectionClass(SysadminController::class);

        if ($ref->hasProperty('model')) {
            $p = $ref->getProperty('model');
            $p->setAccessible(true);
            $p->setValue($this, $model);
        }

        if ($ref->hasProperty('pdo')) {
            $p = $ref->getProperty('pdo');
            $p->setAccessible(true);
            $p->setValue($this, $pdo);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    protected function redirect(string $location): void
    {
        $this->redirectLocation = $location;
    }

    protected function terminate(): void
    {
        throw new RuntimeException('Exit called');
    }

    protected function getAllSpecialties(): array
    {
        return [];
    }
}
