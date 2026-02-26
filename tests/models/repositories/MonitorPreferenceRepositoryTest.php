<?php

declare(strict_types=1);

namespace Tests\Models\Repositories;

use PHPUnit\Framework\TestCase;
use modules\models\repositories\MonitorPreferenceRepository;
use PDO;

class MonitorPreferenceRepositoryTest extends TestCase
{
    private PDO $pdo;
    private MonitorPreferenceRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Required tables for preference management mock setup
        $this->pdo->exec("CREATE TABLE user_parameter_chart_pref (
            id_user INTEGER,
            parameter_id TEXT,
            chart_type TEXT NOT NULL,
            modal_chart_type TEXT,
            updated_at TEXT
        )");

        $this->pdo->exec("CREATE TABLE parameter_reference (
            parameter_id TEXT PRIMARY KEY,
            display_name TEXT,
            default_chart TEXT NOT NULL
        )");

        // Insert a dummy parameter map to mimic parameter references
        $this->pdo->exec("INSERT INTO parameter_reference (parameter_id, display_name, default_chart) VALUES ('p1', 'Heart Rate', 'line')");

        $this->repository = new MonitorPreferenceRepository($this->pdo);
        
        // Ensure columns exist is mostly handled manually in tests via schema mapping unless SQLite supports dynamically 
        // We bypass the ensure columns for sqlite tests if it checks SHOW COLUMNS directly, handled by simple structure.
    }

    public function testSaveUserChartPreferenceCardOnly()
    {
        $this->repository->saveUserChartPreference(1, 'p1', 'bar', false);

        $stmt = $this->pdo->query("SELECT * FROM user_parameter_chart_pref WHERE id_user = 1 AND parameter_id = 'p1'");
        $pref = $stmt->fetch();

        $this->assertNotEmpty($pref, 'Preference should have been inserted');
        $this->assertEquals('bar', $pref['chart_type'], 'Card chart type should be updated');
        $this->assertNull($pref['modal_chart_type']);
    }

    public function testSaveUserChartPreferenceModalOnly()
    {
        // When inserting only for the modal, the standard chart_type (NOT NULL in DB) should default down to the parameter's default_chart
        $this->repository->saveUserChartPreference(1, 'p1', 'value', true);

        $stmt = $this->pdo->query("SELECT * FROM user_parameter_chart_pref WHERE id_user = 1 AND parameter_id = 'p1'");
        $pref = $stmt->fetch();

        $this->assertNotEmpty($pref, 'Preference should have been inserted');
        $this->assertEquals('line', $pref['chart_type'], 'Card chart type should fallback to parameter default');
        $this->assertEquals('value', $pref['modal_chart_type'], 'Modal chart type should reflect designated preference');
    }

    public function testUpdateExistingPreference()
    {
        // Insert standard preference first
        $this->repository->saveUserChartPreference(1, 'p1', 'bar', false);

        // Update modal preference next
        $this->repository->saveUserChartPreference(1, 'p1', 'doughnut', true);

        $stmt = $this->pdo->query("SELECT * FROM user_parameter_chart_pref WHERE id_user = 1 AND parameter_id = 'p1'");
        $pref = $stmt->fetch();

        $this->assertEquals('bar', $pref['chart_type']);
        $this->assertEquals('doughnut', $pref['modal_chart_type']);
    }
}
