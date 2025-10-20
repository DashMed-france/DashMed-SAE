<?php

namespace models;

use PHPUnit\Framework\TestCase;
use modules\models\userModel;
use modules\controllers\SignupController;
use PDO;
use PDOException;

require_once __DIR__ . '/../../app/models/userModel.php';
require_once __DIR__ . '/../../app/controllers/SignupController.php';


/**
 * Class userModelTest
 *
 * Tests unitaires pour le modèle userModel.
 * Utilise une base SQLite en mémoire pour l'isolation et la fiabilité des tests.
 *
 * @coversDefaultClass \modules\models\userModel
 */
class userModelTest extends TestCase
{
    /**
     * PDO instance pour la base de données SQLite en mémoire.
     *
     * @var PDO|null
     */
    private ?PDO $pdo = null;

    /**
     * Instance du modèle à tester.
     *
     * @var userModel|null
     */
    private ?userModel $model = null;

    /**
     * Configure l'environnement avant chaque test.
     * Crée une base SQLite en mémoire et la table users.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec("
            CREATE TABLE users (
                id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                profession TEXT,
                admin_status INTEGER DEFAULT 0
            )
        ");

        $this->model = new userModel($this->pdo);
    }

    /**
     * Nettoie l'environnement après chaque test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo = null;
        $this->model = null;
    }

    /**
     * Teste que getByEmail retourne l'utilisateur lorsqu'il existe.
     *
     * @covers ::getByEmail
     * @uses \modules\models\userModel::create
     *
     * @return void
     */
    public function testGetByEmailReturnsUserWhenExists(): void
    {
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, profession, admin_status)
            VALUES ('Jean', 'Dupont', 'jean@example.com', 'hashedpass', 'Médecin', 0)
        ");

        $user = $this->model->getByEmail('jean@example.com');

        $this->assertIsArray($user);
        $this->assertEquals('Jean', $user['first_name']);
        $this->assertEquals('Dupont', $user['last_name']);
        $this->assertEquals('jean@example.com', $user['email']);
        $this->assertEquals('Médecin', $user['profession']);
        $this->assertEquals(0, $user['admin_status']);
    }

    /**
     * Teste que getByEmail retourne null si l'utilisateur n'existe pas.
     *
     * @covers ::getByEmail
     *
     * @return void
     */
    public function testGetByEmailReturnsNullWhenUserDoesNotExist(): void
    {
        $user = $this->model->getByEmail('nonexistent@example.com');

        $this->assertNull($user);
    }

    /**
     * Teste getByEmail avec des caractères spéciaux dans l'email.
     *
     * @covers ::getByEmail
     *
     * @return void
     */
    public function testGetByEmailWithSpecialCharacters(): void
    {
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, admin_status)
            VALUES ('Paul', 'Léger', 'paul+test@example.com', 'hashedpass', 0)
        ");

        $user = $this->model->getByEmail('paul+test@example.com');

        $this->assertIsArray($user);
        $this->assertEquals('paul+test@example.com', $user['email']);
    }

    /**
     * Teste que create insère correctement un nouvel utilisateur.
     *
     * @covers ::create
     * @uses ::getByEmail
     *
     * @return void
     */
    public function testCreateInsertsNewUserSuccessfully(): void
    {
        $data = [
            'first_name' => 'Sophie',
            'last_name' => 'Bernard',
            'email' => 'sophie@example.com',
            'password' => 'SecurePass123',
            'profession' => 'Infirmière',
            'admin_status' => 0
        ];

        $userId = $this->model->create($data);

        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);

        // Vérifier que l'utilisateur existe vraiment
        $user = $this->model->getByEmail('sophie@example.com');
        $this->assertIsArray($user);
        $this->assertEquals('Sophie', $user['first_name']);
        $this->assertEquals('Bernard', $user['last_name']);
        $this->assertEquals('Infirmière', $user['profession']);
    }

    /**
     * Teste que create hash correctement le mot de passe.
     *
     * @covers ::create
     * @uses ::getByEmail
     *
     * @return void
     */
    public function testCreateHashesPasswordCorrectly(): void
    {
        $plainPassword = 'MyPassword123';
        $data = [
            'first_name' => 'Luc',
            'last_name' => 'Moreau',
            'email' => 'luc@example.com',
            'password' => $plainPassword,
            'admin_status' => 0
        ];

        $this->model->create($data);

        $user = $this->model->getByEmail('luc@example.com');

        $this->assertNotEquals($plainPassword, $user['password']);

        $this->assertTrue(password_verify($plainPassword, $user['password']));
    }

    /**
     * Teste la création d'un utilisateur avec profession nulle.
     *
     * @covers ::create
     * @uses ::getByEmail
     *
     * @return void
     */
    public function testCreateWithNullProfession(): void
    {
        $data = [
            'first_name' => 'Emma',
            'last_name' => 'Petit',
            'email' => 'emma@example.com',
            'password' => 'Password123'
        ];

        $userId = $this->model->create($data);

        $user = $this->model->getByEmail('emma@example.com');
        $this->assertNull($user['profession']);
        $this->assertEquals(0, $user['admin_status']);
    }

    /**
     * Teste la création d'un utilisateur avec statut admin.
     *
     * @covers ::create
     * @uses ::getByEmail
     *
     * @return void
     */
    public function testCreateWithAdminStatus(): void
    {
        $data = [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => 'AdminPass123',
            'admin_status' => 1
        ];

        $this->model->create($data);

        $user = $this->model->getByEmail('admin@example.com');
        $this->assertEquals(1, $user['admin_status']);
    }

    /**
     * Teste que create lance une exception en cas de duplication d'email.
     *
     * @covers ::create
     *
     * @return void
     */
    public function testCreateThrowsExceptionOnDuplicateEmail(): void
    {
        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'duplicate@example.com',
            'password' => 'Pass123',
            'admin_status' => 0
        ];

        $this->model->create($data);

        $this->expectException(PDOException::class);
        $this->model->create($data);
    }

    /**
     * Teste que create retourne le bon ID utilisateur.
     *
     * @covers ::create
     *
     * @return void
     */
    public function testCreateReturnsCorrectUserId(): void
    {
        $data1 = [
            'first_name' => 'User',
            'last_name' => 'One',
            'email' => 'user1@example.com',
            'password' => 'Pass123',
            'admin_status' => 0
        ];

        $data2 = [
            'first_name' => 'User',
            'last_name' => 'Two',
            'email' => 'user2@example.com',
            'password' => 'Pass123',
            'admin_status' => 0
        ];

        $userId1 = $this->model->create($data1);
        $userId2 = $this->model->create($data2);

        $this->assertNotEquals($userId1, $userId2);
        $this->assertGreaterThan($userId1, $userId2);
    }

    /**
     * Teste l'insertion et la récupération de plusieurs utilisateurs.
     *
     * @covers ::create
     * @uses ::getByEmail
     *
     * @return void
     */
    public function testMultipleUsersInDatabase(): void
    {
        $users = [
            ['first_name' => 'Alice', 'last_name' => 'A', 'email' => 'alice@example.com', 'password' => 'Pass1', 'admin_status' => 0],
            ['first_name' => 'Bob', 'last_name' => 'B', 'email' => 'bob@example.com', 'password' => 'Pass2', 'admin_status' => 0],
            ['first_name' => 'Charlie', 'last_name' => 'C', 'email' => 'charlie@example.com', 'password' => 'Pass3', 'admin_status' => 1]
        ];

        foreach ($users as $userData) {
            $this->model->create($userData);
        }

        $alice = $this->model->getByEmail('alice@example.com');
        $bob = $this->model->getByEmail('bob@example.com');
        $charlie = $this->model->getByEmail('charlie@example.com');

        $this->assertEquals('Alice', $alice['first_name']);
        $this->assertEquals('Bob', $bob['first_name']);
        $this->assertEquals('Charlie', $charlie['first_name']);
        $this->assertEquals(1, $charlie['admin_status']);
    }

    /**
     * Teste le workflow complet de création d'un utilisateur.
     *
     * @covers ::create
     * @uses ::getByEmail
     *
     * @return void
     */
    public function testCompleteUserCreationWorkflow(): void
    {
        $password = 'SecurePassword123';
        $data = [
            'first_name' => 'Workflow',
            'last_name' => 'Test',
            'email' => 'workflow@example.com',
            'password' => $password,
            'profession' => 'Pharmacien',
            'admin_status' => 1
        ];

        $existingUser = $this->model->getByEmail('workflow@example.com');
        $this->assertNull($existingUser);

        $userId = $this->model->create($data);
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);

        $user = $this->model->getByEmail('workflow@example.com');

        $this->assertIsArray($user);
        $this->assertEquals($userId, $user['id_user']);
        $this->assertEquals('Workflow', $user['first_name']);
        $this->assertEquals('Test', $user['last_name']);
        $this->assertEquals('workflow@example.com', $user['email']);
        $this->assertEquals('Pharmacien', $user['profession']);
        $this->assertEquals(1, $user['admin_status']);

        $this->assertNotEquals($password, $user['password']);
        $this->assertTrue(password_verify($password, $user['password']));
    }

    /**
     * Teste le modèle avec un nom de table personnalisé.
     *
     * @covers ::create
     * @covers ::getByEmail
     *
     * @return void
     */
    public function testModelWorksWithCustomTableName(): void
    {
        $this->pdo->exec("
            CREATE TABLE custom_users (
                id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                profession TEXT,
                admin_status INTEGER DEFAULT 0
            )
        ");

        $customModel = new userModel($this->pdo, 'custom_users');

        $data = [
            'first_name' => 'Custom',
            'last_name' => 'User',
            'email' => 'custom@example.com',
            'password' => 'Pass123',
            'admin_status' => 0
        ];

        $userId = $customModel->create($data);
        $user = $customModel->getByEmail('custom@example.com');

        $this->assertIsArray($user);
        $this->assertEquals('Custom', $user['first_name']);
        $this->assertEquals($userId, $user['id_user']);
    }
}