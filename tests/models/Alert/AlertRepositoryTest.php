<?php

namespace Tests\Models\Alert;

use PHPUnit\Framework\TestCase;
use modules\models\Alert\AlertRepository;
use PDO;

/**
 * Class AlertRepositoryTest | Tests du Dépôt d'Alertes
 *
 * Tests for retrieving and verifying alerts.
 * Tests pour la récupération et la vérification des alertes.
 *
 * @package Tests\Models\Alert
 * @author DashMed Team
 */
class AlertRepositoryTest extends TestCase
{
    private PDO $pdo;
    private AlertRepository $alertRepo;

    /**
     * Setup.
     * Configuration.
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("CREATE TABLE patient_data (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_patient INTEGER,
            parameter_id INTEGER,
            value REAL,
            timestamp TEXT,
            archived INTEGER DEFAULT 0
        )");

        $this->pdo->exec("CREATE TABLE parameter_reference (
            parameter_id INTEGER PRIMARY KEY,
            display_name TEXT,
            unit TEXT,
            normal_min REAL,
            normal_max REAL,
            critical_min REAL,
            critical_max REAL
        )");

        $this->alertRepo = new AlertRepository($this->pdo);

        $this->pdo->exec(
            "INSERT INTO parameter_reference (
                                 parameter_id,
                                 display_name, 
                                 normal_min, 
                                 normal_max,
                                 critical_min,
                                 critical_max
                                 ) 
            VALUES (1, 'BPM', 60, 100, 40, 140)"
        );

        $this->pdo->exec(
            "INSERT INTO parameter_reference (
                                 parameter_id, 
                                 display_name, 
                                 normal_min, 
                                 normal_max
                                 ) 
            VALUES (2, 'Temp', 36, 37.5)"
        );
    }

    /**
     * Test detecting out of threshold alerts.
     * Test de détection des alertes hors seuil.
     */
    public function testGetOutOfThresholdAlerts()
    {
        $this->pdo->exec("INSERT INTO patient_data (id_patient, parameter_id, value, timestamp) 
            VALUES (1, 1, 80, '2023-01-01 12:00:00')");

        $alerts = $this->alertRepo->getOutOfThresholdAlerts(1);
        $this->assertEmpty($alerts);

        $this->pdo->exec("INSERT INTO patient_data (id_patient, parameter_id, value, timestamp) 
            VALUES (1, 1, 120, '2023-01-01 13:00:00')");

        $alerts = $this->alertRepo->getOutOfThresholdAlerts(1);
        $this->assertCount(1, $alerts);
        $this->assertEquals(120, $alerts[0]->value);
        $this->assertEquals('BPM', $alerts[0]->displayName);
    }

    /**
     * Test checking if patient has alerts.
     * Test pour vérifier si le patient a des alertes.
     */
    public function testHasAlerts()
    {
        $this->assertFalse($this->alertRepo->hasAlerts(1));

        $this->pdo->exec("INSERT INTO patient_data (id_patient, parameter_id, value, timestamp) 
            VALUES (1, 1, 150, '2023-01-01 13:00:00')");

        $this->assertTrue($this->alertRepo->hasAlerts(1));
    }

    /**
     * Test that archived data is ignored for alerts.
     * Test que les données archivées sont ignorées pour les alertes.
     */
    public function testArchivedDataIgnored()
    {
        $this->pdo->exec("INSERT INTO patient_data (id_patient, parameter_id, value, timestamp, archived) 
            VALUES (1, 1, 150, '2023-01-01 13:00:00', 1)");

        $this->assertFalse($this->alertRepo->hasAlerts(1));
    }
}
