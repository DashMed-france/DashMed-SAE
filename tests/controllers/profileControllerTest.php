<?php
use PHPUnit\Framework\TestCase;
use modules\controllers\profileController;
use modules\views\profileView;

require_once __DIR__ . '/../../app/controllers/ProfileController.php';

/**
 * Test du contrôleur profileController.
 */
class profileControllerTest extends TestCase
{
    private ?PDO $pdo = null;
    private profileController $controller;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                profession_id INTEGER,
                FOREIGN KEY (profession_id) REFERENCES medical_specialties(id)
            );

            CREATE TABLE medical_specialties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL
            );
        ");

        $this->pdo->exec("INSERT INTO medical_specialties (name) VALUES ('Cardiologue'), ('Dermatologue')");
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, profession_id)
            VALUES ('AliceModif', 'DurandModif', 'alice@example.com', 1)
        ");

        $_SESSION = [
            'email' => 'alice@example.com',
            'csrf_profile' => 'test_csrf_token'
        ];

        $this->controller = $this->getMockBuilder(ProfileController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $ref = new ReflectionClass(profileController::class);
        $pdoProp = $ref->getProperty('pdo');
        $pdoProp->setAccessible(true);
        $pdoProp->setValue($this->controller, $this->pdo);

        $testModeProp = $ref->getProperty('testMode');
        $testModeProp->setAccessible(true);
        $testModeProp->setValue($this->controller, true);
    }

    public function testProfileUpdate(): void
    {
        $_POST = [
            'csrf' => 'test_csrf_token',
            'action' => 'update',
            'first_name' => 'AliceModif',
            'last_name' => 'DurandModif',
            'profession_id' => '1'
        ];

        $this->controller->post();

        $stmt = $this->pdo->prepare("SELECT first_name, last_name, profession_id FROM users WHERE email = ?");
        $stmt->execute(['alice@example.com']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('AliceModif', $user['first_name']);
        $this->assertEquals('DurandModif', $user['last_name']);
        $this->assertEquals(1, $user['profession_id']);
    }

    public function testDeleteAccount(): void
    {
        $_POST = [
            'csrf' => 'test_csrf_token',
            'action' => 'delete_account'
        ];

        $this->controller->post();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute(['alice@example.com']);
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count, 'Le compte utilisateur aurait dû être supprimé');
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $_SESSION = [];
        $_POST = [];
    }
}