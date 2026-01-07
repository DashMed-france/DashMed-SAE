<?php

namespace controllers\pages;

use modules\controllers\pages\ProfileController;
use modules\views\pages\ProfileView;
use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionClass;

require_once __DIR__ . '/../../../app/controllers/pages/ProfileController.php';

/**
 * Unit test class for ProfileController.
 *
 * Tests the profile update and account deletion functionalities.
 *
 * @coversDefaultClass \modules\controllers\pages\ProfileController
 */
class ProfileControllerTest extends TestCase
{
    /**
     * PDO instance for the in-memory SQLite database.
     *
     * @var ?PDO
     */
    private ?PDO $pdo = null;

    /**
     * Instance of the ProfileController to be tested.
     *
     * @var ProfileController
     */
    private ProfileController $controller;

    /**
     * Prepares the test environment.
     *
     * Initializes the in-memory SQLite database, creates tables and test data,
     * configures the session, and instantiates the controller in test mode.
     *
     * @return void
     */
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

        $ref = new ReflectionClass(ProfileController::class);
        $pdoProp = $ref->getProperty('pdo');
        $pdoProp->setAccessible(true);
        $pdoProp->setValue($this->controller, $this->pdo);

        $testModeProp = $ref->getProperty('testMode');
        $testModeProp->setAccessible(true);
        $testModeProp->setValue($this->controller, true);
    }

    /**
     * Tests the user profile update.
     *
     * Verifies that the first_name, last_name, and profession_id fields
     * are correctly modified in the database after a POST request.
     *
     * @covers ::post
     * @return void
     */
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

    /**
     * Tests the user account deletion.
     *
     * Verifies that the user is properly removed from the database
     * after a POST request with the 'delete_account' action.
     *
     * @covers ::post
     * @return void
     */
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

    /**
     * Cleanup after each test.
     *
     * Resets the database and the session.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo = null;
        $_SESSION = [];
        $_POST = [];
    }
}