<?php

use PHPUnit\Framework\TestCase;
use modules\models\Monitoring\MonitorPreferenceModel;

class MonitorPreferenceModelTest extends TestCase
{
    private PDO $pdo;
    private MonitorPreferenceModel $prefModel;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Tables
        $this->pdo->exec("CREATE TABLE user_parameter_chart_pref (
            id_user INTEGER,
            parameter_id TEXT,
            chart_type TEXT,
            updated_at TEXT
        )");

        $this->pdo->exec("CREATE TABLE user_parameter_order (
            id_user INTEGER,
            parameter_id TEXT,
            display_order INTEGER,
            is_hidden INTEGER DEFAULT 0,
            grid_x INTEGER DEFAULT 0,
            grid_y INTEGER DEFAULT 0,
            grid_w INTEGER DEFAULT 4,
            grid_h INTEGER DEFAULT 3,
            updated_at TEXT
        )");

        $this->pdo->exec("CREATE TABLE parameter_reference (
            parameter_id TEXT PRIMARY KEY,
            display_name TEXT,
            category TEXT
        )");

        $this->prefModel = new MonitorPreferenceModel($this->pdo);
    }

    public function testAllParameters()
    {
        $this->pdo->exec("INSERT INTO parameter_reference (parameter_id, display_name) VALUES ('p1', 'Param 1')");
        $params = $this->prefModel->getAllParameters();
        $this->assertCount(1, $params);
    }


    public function testResetUserLayoutSimple()
    {
        // This relies on ensureLayoutColumns (SHOW COLUMNS) -> delete is simple, but ensure might be called first?
        // resetUserLayoutSimple does NOT call ensureLayoutColumns. It just DELETEs.
        // So this might pass on SQLite!
        
        $this->pdo->exec("INSERT INTO user_parameter_order (id_user, parameter_id) VALUES (1, 'p1')");
        $this->prefModel->resetUserLayoutSimple(1);
        
        $stmt = $this->pdo->query("SELECT count(*) FROM user_parameter_order WHERE id_user=1");
        $this->assertEquals(0, $stmt->fetchColumn());
    }
}
