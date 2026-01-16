<?php

namespace Tests\Models\Monitoring;

use PHPUnit\Framework\TestCase;
use modules\models\Monitoring\MonitorModel;
use PDO;

/**
 * Class MonitorModelTest | Tests du Modèle Monitoring
 *
 * Tests for raw monitoring data retrieval.
 * Tests pour la récupération des données de monitoring brutes.
 *
 * @package Tests\Models\Monitoring
 * @author DashMed Team
 */
class MonitorModelTest extends TestCase
{
    private PDO $pdo;
    private MonitorModel $monitorModel;

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
            alert_flag INTEGER DEFAULT 0,
            archived INTEGER DEFAULT 0
        )");

        $this->pdo->exec("CREATE TABLE parameter_reference (
            parameter_id INTEGER PRIMARY KEY,
            display_name TEXT,
            category TEXT,
            unit TEXT,
            description TEXT,
            normal_min REAL,
            normal_max REAL,
            critical_min REAL,
            critical_max REAL,
            display_min REAL,
            display_max REAL,
            default_chart TEXT
        )");

        $this->pdo->exec("CREATE TABLE parameter_chart_allowed (
            parameter_id INTEGER,
            chart_type TEXT
        )");

        $this->pdo->exec("CREATE TABLE chart_types (
            chart_type TEXT PRIMARY KEY,
            label TEXT
        )");

        $this->monitorModel = new MonitorModel($this->pdo, 'patient_data');


        $this->pdo->exec(
            "INSERT INTO parameter_reference (parameter_id, display_name, category, default_chart) 
            VALUES (1, 'BPM', 'Vitals', 'line')"
        );

        $this->pdo->exec("INSERT INTO parameter_chart_allowed (parameter_id, chart_type) VALUES (1, 'line')");
        $this->pdo->exec("INSERT INTO parameter_chart_allowed (parameter_id, chart_type) VALUES (1, 'bar')");
    }

    /**
     * Test retrieval of raw history data.
     * Test de récupération de l'historique brut.
     */
    public function testGetRawHistory()
    {
        $this->pdo->exec(
            "INSERT INTO patient_data (id_patient, parameter_id, value, timestamp)
            VALUES (1, 1, 80, '2023-01-01 10:00:00')"
        );
        $this->pdo->exec(
            "INSERT INTO patient_data (id_patient, parameter_id, value, timestamp) 
            VALUES (1, 1, 85, '2023-01-01 10:05:00')"
        );

        $history = $this->monitorModel->getRawHistory(1);
        $this->assertCount(2, $history);
        $this->assertEquals(85, $history[0]['value']);
    }

    /**
     * Test retrieval of available chart types.
     * Test de récupération des types de graphiques disponibles.
     */
    public function testGetAllChartTypes()
    {
        $this->pdo->exec("INSERT INTO chart_types (chart_type, label) VALUES ('line', 'Ligne')");
        $this->pdo->exec("INSERT INTO chart_types (chart_type, label) VALUES ('bar', 'Barre')");

        $types = $this->monitorModel->getAllChartTypes();
        $this->assertCount(2, $types);
        $this->assertArrayHasKey('line', $types);
        $this->assertEquals('Ligne', $types['line']);
    }
}
