<?php

namespace Tests\Repositories;

use PHPUnit\Framework\TestCase;
use modules\models\repositories\ConsultationRepository;
use modules\models\entities\Consultation;
use PDO;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Class ConsultationRepositoryTest | Tests du Repository Consultation
 *
 * Tests for consultation management CRUD.
 * Tests pour la gestion CRUD des consultations.
 *
 * @package Tests\Repositories
 * @author DashMed Team
 */
class ConsultationRepositoryTest extends TestCase
{
    private PDO $pdo;
    private ConsultationRepository $consultationRepo;

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

        $this->pdo->exec("CREATE TABLE users (
            id_user INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT,
            last_name TEXT
        )");

        $this->pdo->exec("CREATE TABLE patients (
            id_patient INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT,
            last_name TEXT
        )");

        $this->consultationRepo = new ConsultationRepository($this->pdo);
    }

    public function testCreateConsultation()
    {
        $result = $this->consultationRepo->createConsultation(
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

    public function testGetConsultationsByPatientId()
    {
        $this->pdo->exec("INSERT INTO users (id_user, last_name) VALUES (5, 'Dr. Strange')");
        $this->pdo->exec("INSERT INTO patients (id_patient, first_name) VALUES (1, 'Marty')");

        $this->pdo->exec(
            "INSERT INTO consultations (
                                id_user, 
                                id_patient,
                                date,
                                title,
                                type,
                                note
                                )
            VALUES (5, 1, '2023-10-10 10:00:00', 'Magic check', 'Magic', 'Strange things')"
        );

        $consultations = $this->consultationRepo->getConsultationsByPatientId(1);

        $this->assertCount(1, $consultations);
        $this->assertInstanceOf(Consultation::class, $consultations[0]);
    }
}
