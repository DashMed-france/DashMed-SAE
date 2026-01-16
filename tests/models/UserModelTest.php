<?php

namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use modules\models\UserModel;
use PDO;

/**
 * Class UserModelTest | Tests du Modèle Utilisateur
 *
 * Tests for user management operations (CRUD, Auth).
 * Tests pour les opérations de gestion des utilisateurs (CRUD, Auth).
 *
 * @package Tests\Models
 * @author DashMed Team
 */
class UserModelTest extends TestCase
{
    private PDO $pdo;
    private UserModel $userModel;

    /**
     * Setup in-memory DB and seed tables.
     * Configuration de la base en mémoire et remplissage des tables.
     */
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

        $this->userModel = new UserModel($this->pdo, 'users');
    }

    /**
     * Test user creation.
     * Test de création d'utilisateur.
     */
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


        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id_user = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('John', $user['first_name']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertTrue(password_verify('secret123', $user['password']));
    }

    /**
     * Test retrieval by email.
     * Test de récupération par email.
     */
    public function testGetByEmail()
    {

        $this->pdo->exec("INSERT INTO professions (id_profession, label_profession) VALUES (1, 'Doctor')");


        $this->userModel->create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@example.com',
            'password' => 'pass',
            'admin_status' => 1,
            'id_profession' => 1
        ]);


        $user = $this->userModel->getByEmail('ALICE@example.com');
        $this->assertNotNull($user);
        $this->assertEquals('Alice', $user['first_name']);


        $this->assertArrayHasKey('profession_label', $user);
        $this->assertEquals('Doctor', $user['profession_label']);
    }

    /**
     * Test getByEmail with unknown email.
     * Test getByEmail avec un email inconnu.
     */
    public function testGetByEmailReturnsNullForUnknownUser()
    {
        $user = $this->userModel->getByEmail('nonexistent@example.com');
        $this->assertNull($user);
    }

    /**
     * Test credential verification.
     * Test de vérification des identifiants.
     */
    public function testVerifyCredentialsResponse()
    {
        $this->userModel->create([
            'first_name' => 'Bob',
            'last_name' => 'Marley',
            'email' => 'bob@example.com',
            'password' => 'guitar',
            'admin_status' => 0
        ]);


        $user = $this->userModel->verifyCredentials('bob@example.com', 'guitar');
        $this->assertNotNull($user);
        $this->assertEquals('Bob', $user['first_name']);
        $this->assertArrayNotHasKey('password', $user);


        $this->assertNull($this->userModel->verifyCredentials('bob@example.com', 'wrong'));


        $this->assertNull($this->userModel->verifyCredentials('nobody@example.com', 'guitar'));
    }

    /**
     * Test retrieval by ID.
     * Test de récupération par ID.
     */
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

    /**
     * Test retrieving all doctors.
     * Test de récupération de tous les médecins.
     */
    public function testGetAllDoctors()
    {
        $this->userModel->create(
            ['first_name' => 'A', 'last_name' => 'Zeta', 'email' => 'a@a.com', 'password' => 'p']
        );
        $this->userModel->create(
            ['first_name' => 'B', 'last_name' => 'Alpha', 'email' => 'b@b.com', 'password' => 'p']
        );

        $doctors = $this->userModel->getAllDoctors();
        $this->assertCount(2, $doctors);

        $this->assertEquals('Alpha', $doctors[0]['last_name']);
        $this->assertEquals('Zeta', $doctors[1]['last_name']);
    }
}
