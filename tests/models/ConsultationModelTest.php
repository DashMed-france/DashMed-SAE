<?php

namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use modules\models\ConsultationModel;
use modules\models\Consultation;
use PDO;

/**
 * Class ConsultationModelTest | Tests du Modèle Consultation
 *
 * Tests for consultation management CRUD.
 * Tests pour la gestion CRUD des consultations.
 *
 * @package Tests\Models
 * @author DashMed Team
 */
class ConsultationModelTest extends TestCase
{
    private PDO $pdo;
    private ConsultationModel $consultationModel;

    /**
     * Setup.
     * Configuration.
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


        $this->pdo->sqliteCreateFunction('NOW', function () {
            return date('Y-m-d H:i:s');
        });
        $this->pdo->sqliteCreateFunction('CURDATE', function () {
            return date('Y-m-d');
        });


        $this->pdo->exec("CREATE TABLE consultations (
            id_consultations INTEGER PRIMARY KEY AUTOINCREMENT,
            id_patient INTEGER,
            id_user INTEGER,
            date TEXT,
            type TEXT,
            note TEXT,
            title TEXT,
            updated_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        $this->pdo->exec("CREATE TABLE view_consultations (
            id_consultations INTEGER,
            id_user INTEGER,
            id_patient INTEGER,
            last_name TEXT,
            date TEXT,
            title TEXT,
            type TEXT,
            note TEXT
        )");

        $this->consultationModel = new ConsultationModel($this->pdo);
    }

    /**
     * Test creating a consultation.
     * Test de création d'une consultation.
     */
    public function testCreateConsultation()
    {
        $result = $this->consultationModel->createConsultation(
            1,
            2,
            '2023-10-10 10:00:00',
            'Checkup',
            'Tout va bien',
            'Consultation 1'
        );
        $this->assertTrue($result);


        $stmt = $this->pdo->prepare("SELECT * FROM consultations WHERE id_patient = 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('Consultation 1', $row['title']);
        $this->assertEquals('Checkup', $row['type']);
    }

    /**
     * Test retrieving consultations by patient.
     * Test de récupération des consultations par patient.
     */
    public function testGetConsultationsByPatientId()
    {
        $this->pdo->exec(
            "INSERT INTO view_consultations (
                                id_consultations, 
                                id_user, 
                                id_patient,
                                last_name,
                                date,
                                title,
                                type,
                                note
                                )
            VALUES (10, 5, 1, 'Dr. Strange', '2023-10-10 10:00:00', 'Magic check', 'Magic', 'Strange things')"
        );

        $consultations = $this->consultationModel->getConsultationsByPatientId(1);
        $this->assertCount(1, $consultations);
        $this->assertInstanceOf(Consultation::class, $consultations[0]);
        $this->assertEquals('Dr. Strange', $consultations[0]->getDoctor());
        $this->assertEquals('Magic check', $consultations[0]->getTitle());
    }

    /**
     * Test updating a consultation.
     * Test de mise à jour d'une consultation.
     */
    public function testUpdateConsultation()
    {
        $this->pdo->exec(
            "INSERT INTO consultations (id_consultations, id_patient, id_user, date, type, note, title)
            VALUES (1, 1, 2, '2023-01-01', 'Old', 'Note', 'Title')
            "
        );

        $result = $this->consultationModel->updateConsultation(
            1,
            3,
            '2023-02-02',
            'New',
            'New Note',
            'New Title'
        );
        $this->assertTrue($result);

        $stmt = $this->pdo->prepare("SELECT * FROM consultations WHERE id_consultations = 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('New Title', $row['title']);
        $this->assertEquals(3, $row['id_user']);
    }

    /**
     * Test deleting a consultation.
     * Test de suppression d'une consultation.
     */
    public function testDeleteConsultation()
    {
        $this->pdo->exec("INSERT INTO consultations (id_consultations, id_patient, id_user) VALUES (1, 1, 2)");
        $this->assertTrue($this->consultationModel->deleteConsultation(1));

        $stmt = $this->pdo->prepare("SELECT count(*) FROM consultations WHERE id_consultations = 1");
        $stmt->execute();
        $this->assertEquals(0, $stmt->fetchColumn());
    }
}
