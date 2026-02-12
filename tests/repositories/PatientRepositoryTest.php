<?php

namespace Tests\Repositories;

use PHPUnit\Framework\TestCase;
use modules\models\repositories\PatientRepository;
use PDO;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Class PatientRepositoryTest | Tests du Repository Patient
 *
 * Tests for patient management.
 * Tests pour la gestion des patients.
 *
 * @package Tests\Repositories
 * @author DashMed Team
 */
class PatientRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PatientRepository $patientRepo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("CREATE TABLE patients (
            id_patient INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            profession TEXT,
            admin_status INTEGER DEFAULT 0,
            birth_date TEXT,
            gender TEXT,
            description TEXT,
            updated_at TEXT,
            room_id INTEGER,
            medical_history TEXT,
            admission_cause TEXT,
            social_security_number TEXT
        )");

        $this->pdo->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT,
            last_name TEXT,
            id_profession INTEGER,
            email TEXT,
            password TEXT,
            admin_status INTEGER
        )");

        $this->pdo->exec("CREATE TABLE professions (
            id_profession INTEGER PRIMARY KEY AUTOINCREMENT,
            label_profession TEXT
        )");

        $this->pdo->exec("CREATE TABLE consultations (
            id_consultation INTEGER PRIMARY KEY AUTOINCREMENT,
            id_doctor INTEGER,
            id_patient INTEGER,
            date_time TEXT,
            type TEXT,
            note TEXT,
            title TEXT
        )");

        $this->patientRepo = new PatientRepository($this->pdo);
    }

    public function testCreatePatient(): void
    {
        $data = [
            'first_name' => 'Paul',
            'last_name' => 'Bismuth',
            'email' => 'paul@example.com',
            'password' => 'ecoute',
            'profession' => 'Agent',
            'admin_status' => 0
        ];

        try {
            $id = $this->patientRepo->create($data);

            if (is_bool($id)) {
                $this->assertTrue($id);
                $stmt = $this->pdo->query("SELECT id_patient FROM patients WHERE email='paul@example.com'");
                $id = $stmt->fetchColumn();
            }

            $this->assertGreaterThan(0, $id);

            $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE id_patient = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->assertEquals('Paul', $row['first_name']);
            $this->assertEquals('paul@example.com', $row['email']);
        } catch (\Throwable $e) {
            $this->fail("Create failed: " . $e->getMessage());
        }
    }

    public function testFindById()
    {
        $this->pdo->exec("INSERT INTO patients (first_name, last_name, email, password, description) 
            VALUES ('Jean', 'Valjean', 'jv@mis.com', 'num24601', 'Cause admission')");
        $id = $this->pdo->lastInsertId();

        $patient = $this->patientRepo->findById($id);

        $this->assertIsArray($patient);
        $this->assertEquals('Jean', $patient['first_name']);
    }
}
