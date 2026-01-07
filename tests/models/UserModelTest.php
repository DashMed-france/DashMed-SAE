<?php

use PHPUnit\Framework\TestCase;
use modules\models\UserModel;

class UserModelTest extends TestCase
{
    private PDO $pdo;
    private UserModel $userModel;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create users table
        $this->pdo->exec("CREATE TABLE users (
            id_user INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            admin_status INTEGER DEFAULT 0,
            id_profession INTEGER,
            birth_date TEXT,
            created_at TEXT
        )");

        // Create professions table
        $this->pdo->exec("CREATE TABLE professions (
            id_profession INTEGER PRIMARY KEY AUTOINCREMENT,
            label_profession TEXT NOT NULL
        )");

        $this->userModel = new UserModel($this->pdo, 'users');
    }

    public function testCreateUser(): void
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'secret123',
            'admin_status' => 0,
            'id_profession' => 1
        ];

        $id = $this->userModel->create($data);
        $this->assertGreaterThan(0, $id);

        // Verify in DB
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id_user = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('John', $user['first_name']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertTrue(password_verify('secret123', $user['password']));
    }

    public function testGetByEmail()
    {
        // Insert a profession
        $this->pdo->exec("INSERT INTO professions (id_profession, label_profession) VALUES (1, 'Doctor')");

        // Create user
        $this->userModel->create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@example.com',
            'password' => 'pass',
            'admin_status' => 1,
            'id_profession' => 1
        ]);

        // Test retrieval with case insensitivity
        $user = $this->userModel->getByEmail('ALICE@example.com');
        $this->assertNotNull($user);
        $this->assertEquals('Alice', $user['first_name']);
        
        // Check if profession label is joined
        $this->assertArrayHasKey('profession_label', $user);
        $this->assertEquals('Doctor', $user['profession_label']);
    }

    public function testGetByEmailReturnsNullForUnknownUser()
    {
        $user = $this->userModel->getByEmail('nonexistent@example.com');
        $this->assertNull($user);
    }

    public function testVerifyCredentialsResponse()
    {
        $this->userModel->create([
            'first_name' => 'Bob',
            'last_name' => 'Marley',
            'email' => 'bob@example.com',
            'password' => 'guitar',
            'admin_status' => 0
        ]);

        // Success
        $user = $this->userModel->verifyCredentials('bob@example.com', 'guitar');
        $this->assertNotNull($user);
        $this->assertEquals('Bob', $user['first_name']);
        $this->assertArrayNotHasKey('password', $user); // Password should be unset

        // Wrong password
        $this->assertNull($this->userModel->verifyCredentials('bob@example.com', 'wrong'));

        // Wrong email
        $this->assertNull($this->userModel->verifyCredentials('nobody@example.com', 'guitar'));
    }

    public function testGetById()
    {
        $id = $this->userModel->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'secret',
            'admin_status' => 0
        ]);

        $user = $this->userModel->getById($id);
        $this->assertNotNull($user);
        $this->assertEquals('Jane', $user['first_name']);

        $this->assertNull($this->userModel->getById(999));
    }

    public function testGetAllDoctors()
    {
        $this->userModel->create(['first_name' => 'A', 'last_name' => 'Zeta', 'email' => 'a@a.com', 'password' => 'p']);
        $this->userModel->create(['first_name' => 'B', 'last_name' => 'Alpha', 'email' => 'b@b.com', 'password' => 'p']);

        $doctors = $this->userModel->getAllDoctors();
        $this->assertCount(2, $doctors);
        // Ordered by last_name ASC
        $this->assertEquals('Alpha', $doctors[0]['last_name']);
        $this->assertEquals('Zeta', $doctors[1]['last_name']);
    }
}
