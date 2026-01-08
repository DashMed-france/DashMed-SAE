<?php

use PHPUnit\Framework\TestCase;
use modules\models\Alert\AlertRepository;

class AlertRepositoryTest extends TestCase
{
    private PDO $pdo;
    private AlertRepository $alertRepo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Schema
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

        // Seed parameter reference
        // ID 1: Heart Rate (60-100)
        $this->pdo->exec("INSERT INTO parameter_reference (parameter_id, display_name, normal_min, normal_max, critical_min, critical_max) 
            VALUES (1, 'BPM', 60, 100, 40, 140)");
        
        // ID 2: Temp (36-37.5)
        $this->pdo->exec("INSERT INTO parameter_reference (parameter_id, display_name, normal_min, normal_max) 
            VALUES (2, 'Temp', 36, 37.5)");
    }

    public function testGetOutOfThresholdAlerts()
    {
        // Insert normal data
        $this->pdo->exec("INSERT INTO patient_data (id_patient, parameter_id, value, timestamp) 
            VALUES (1, 1, 80, '2023-01-01 12:00:00')");
        
        $alerts = $this->alertRepo->getOutOfThresholdAlerts(1);
        $this->assertEmpty($alerts);

        // Insert abnormal data (High BPM)
        $this->pdo->exec("INSERT INTO patient_data (id_patient, parameter_id, value, timestamp) 
            VALUES (1, 1, 120, '2023-01-01 13:00:00')");

        $alerts = $this->alertRepo->getOutOfThresholdAlerts(1);
        $this->assertCount(1, $alerts);
        $this->assertEquals(120, $alerts[0]->value);
        $this->assertEquals('BPM', $alerts[0]->displayName);
    }

    public function testHasAlerts()
    {
        $this->assertFalse($this->alertRepo->hasAlerts(1));

        // Insert critical data
        $this->pdo->exec("INSERT INTO patient_data (id_patient, parameter_id, value, timestamp) 
            VALUES (1, 1, 150, '2023-01-01 13:00:00')");

        $this->assertTrue($this->alertRepo->hasAlerts(1));
    }

    public function testArchivedDataIgnored()
    {
        // Insert abnormal but archived data
        $this->pdo->exec("INSERT INTO patient_data (id_patient, parameter_id, value, timestamp, archived) 
            VALUES (1, 1, 150, '2023-01-01 13:00:00', 1)");

        $this->assertFalse($this->alertRepo->hasAlerts(1));
    }
}
