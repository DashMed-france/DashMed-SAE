<?php

namespace Tests\Repositories;

use PHPUnit\Framework\TestCase;
use modules\models\repositories\UserRepository;
use modules\models\entities\User;
use PDO;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Class UserRepositoryTest | Tests du Repository Utilisateur
 *
 * Tests for user management operations (CRUD, Auth).
 * Tests pour les opérations de gestion des utilisateurs (CRUD, Auth).
 *
 * @package Tests\Repositories
 * @author DashMed Team
 */
class UserRepositoryTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

        $this->pdo->exec("CREATE TABLE professions (
            id_profession INTEGER PRIMARY KEY AUTOINCREMENT,
            label_profession TEXT NOT NULL
        )");

        $this->userRepo = new UserRepository($this->pdo);
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

        $id = $this->userRepo->create($data);
        $this->assertGreaterThan(0, $id);

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id_user = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('John', $user['first_name']);
        $this->assertEquals('john.doe@example.com', $user['email']);
    }

    public function testGetByEmail()
    {
        $this->pdo->exec("INSERT INTO professions (id_profession, label_profession) VALUES (1, 'Doctor')");

        $this->userRepo->create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@example.com',
            'password' => 'pass',
            'admin_status' => 1,
            'id_profession' => 1
        ]);

        $user = $this->userRepo->getByEmail('ALICE@example.com');
        $this->assertNotNull($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Alice', $user->getFirstName());
    }

    public function testVerifyCredentialsResponse()
    {
        $this->userRepo->create([
            'first_name' => 'Bob',
            'last_name' => 'Marley',
            'email' => 'bob@example.com',
            'password' => 'guitar',
            'admin_status' => 0
        ]);

        $user = $this->userRepo->verifyCredentials('bob@example.com', 'guitar');
        $this->assertNotNull($user);
        $this->assertInstanceOf(User::class, $user);

        $this->assertNull($this->userRepo->verifyCredentials('bob@example.com', 'wrong'));
    }
}
