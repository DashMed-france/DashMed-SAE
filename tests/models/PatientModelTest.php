<?php

use PHPUnit\Framework\TestCase;
use modules\models\PatientModel;

/**
 * Class PatientModelTest | Tests du Modèle Patient
 *
 * Tests for patient management (CRUD, searching linked doctors).
 * Tests pour la gestion des patients (CRUD, recherche de médecins liés).
 *
 * @package Tests\Models
 * @author DashMed Team
 */
class PatientModelTest extends TestCase
{
    private PDO $pdo;
    private PatientModel $patientModel;

    /**
     * Setup.
     * Configuration.
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create patients table
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
            room_id INTEGER
        )");

        // Setup for getDoctors related tables
        $this->pdo->exec("CREATE TABLE users (
            id_user INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT,
            last_name TEXT,
            id_profession INTEGER
        )");

        $this->pdo->exec("CREATE TABLE professions (
            id_profession INTEGER PRIMARY KEY AUTOINCREMENT,
            label_profession TEXT
        )");

        $this->pdo->exec("CREATE TABLE consultations (
            id_consultations INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user INTEGER,
            id_patient INTEGER,
            date TEXT
        )");

        $this->patientModel = new PatientModel($this->pdo, 'patients');
    }

    /**
     * Test patient creation.
     * Test de création patient.
     */
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

        $id = $this->patientModel->create($data);
        $this->assertGreaterThan(0, $id);

        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE id_patient = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('Paul', $row['first_name']);
        $this->assertEquals('paul@example.com', $row['email']);
        $this->assertTrue(password_verify('ecoute', $row['password']));
    }

    /**
     * Test finding patient by ID.
     * Test de recherche par ID.
     */
    public function testFindById()
    {
        $this->pdo->exec("INSERT INTO patients (first_name, last_name, email, password, description) 
            VALUES ('Jean', 'Valjean', 'jv@mis.com', 'num24601', 'Cause admission')");
        $id = $this->pdo->lastInsertId();

        $patient = $this->patientModel->findById($id);
        $this->assertIsArray($patient);
        $this->assertEquals('Jean', $patient['first_name']);
        $this->assertEquals('Cause admission', $patient['admission_cause']);
        // Verify mocked medical_history
        $this->assertArrayHasKey('medical_history', $patient);
    }

    /**
     * Test updating patient.
     * Test de mise à jour patient.
     */
    public function testUpdatePatient()
    {
        $this->pdo->exec("INSERT INTO patients (first_name, last_name, email, password, description) 
            VALUES ('Old', 'Name', 'old@e.com', 'pwd', 'Old Desc')");
        $id = $this->pdo->lastInsertId();

        $updateData = [
            'first_name' => 'New',
            'last_name' => 'Name',
            'birth_date' => '1990-01-01',
            'admission_cause' => 'New Desc'
        ];

        $result = $this->patientModel->update($id, $updateData);
        $this->assertTrue($result);

        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE id_patient = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('New', $row['first_name']);
        $this->assertEquals('New Desc', $row['description']);
        $this->assertEquals('1990-01-01', $row['birth_date']);
    }

    /**
     * Test getting doctors linked to a patient.
     * Test de récupération des médecins liés à un patient.
     */
    public function testGetDoctors()
    {
        // Insert Data
        $this->pdo->exec("INSERT INTO professions (id_profession, label_profession) VALUES (10, 'Cardio')");
        $this->pdo->exec("INSERT INTO users (id_user, first_name, last_name, id_profession) VALUES (1, 'Dr', 'House', 10)");
        $this->pdo->exec("INSERT INTO patients (id_patient, first_name, last_name, email, password) VALUES (5, 'Pat', 'Ient', 'p@i.com', 'p')");

        // Link via consultation
        $this->pdo->exec("INSERT INTO consultations (id_user, id_patient, date) VALUES (1, 5, '2023-01-01')");

        $doctors = $this->patientModel->getDoctors(5);
        $this->assertCount(1, $doctors);
        $this->assertEquals('House', $doctors[0]['last_name']);
        $this->assertEquals('Cardio', $doctors[0]['profession_name']);
    }

    /**
     * Test getting patient ID by room number.
     * Test de récupération de l'ID patient par numéro de chambre.
     */
    public function testGetPatientIdByRoom()
    {
        $this->pdo->exec("INSERT INTO patients (first_name, last_name, email, password, room_id) VALUES ('In', 'Room', 'r@r.com', 'p', 101)");

        $id = $this->patientModel->getPatientIdByRoom(101);
        $this->assertNotNull($id);

        $notFound = $this->patientModel->getPatientIdByRoom(999);
        $this->assertNull($notFound);
    }
}
