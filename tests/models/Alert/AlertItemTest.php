<?php

namespace Tests\Models\Alert;

use PHPUnit\Framework\TestCase;
use modules\models\Alert\AlertItem;

/**
 * Class AlertItemTest | Tests du DTO AlertItem
 *
 * Tests for AlertItem data transfer object.
 * Tests pour l'objet de transfert de données AlertItem.
 *
 * @package Tests\Models\Alert
 * @author DashMed Team
 */
class AlertItemTest extends TestCase
{
    /**
     * Test constructor initialization.
     * Test initialisation via constructeur.
     */
    public function testConstructor(): void
    {
        $alert = new AlertItem(
            parameterId: 'bpm',
            displayName: 'BPM',
            unit: 'bpm',
            value: 80.0,
            minThreshold: 60.0,
            maxThreshold: 100.0,
            criticalMin: 40.0,
            criticalMax: 140.0,
            timestamp: '2023-01-01 12:00:00',
            isBelowMin: false,
            isAboveMax: false,
            isCritical: false
        );

        $this->assertEquals('bpm', $alert->parameterId);
        $this->assertEquals('BPM', $alert->displayName);
        $this->assertEquals(80.0, $alert->value);
        $this->assertFalse($alert->isBelowMin);
        $this->assertFalse($alert->isAboveMax);
        $this->assertFalse($alert->isCritical);
    }

    /**
     * Test fromRow with normal value.
     * Test fromRow avec valeur normale.
     */
    public function testFromRowNormalValue(): void
    {
        $row = [
            'parameter_id' => 1,
            'display_name' => 'Heart Rate',
            'unit' => 'bpm',
            'value' => 75,
            'normal_min' => 60,
            'normal_max' => 100,
            'critical_min' => 40,
            'critical_max' => 140,
            'timestamp' => '2023-01-01 10:00:00'
        ];

        $alert = AlertItem::fromRow($row);

        $this->assertEquals('1', $alert->parameterId);
        $this->assertEquals('Heart Rate', $alert->displayName);
        $this->assertEquals(75.0, $alert->value);
        $this->assertFalse($alert->isBelowMin);
        $this->assertFalse($alert->isAboveMax);
        $this->assertFalse($alert->isCritical);
    }

    /**
     * Test fromRow detects below min.
     * Test fromRow détecte valeur sous le minimum.
     */
    public function testFromRowBelowMin(): void
    {
        $row = [
            'parameter_id' => 1,
            'display_name' => 'BPM',
            'unit' => 'bpm',
            'value' => 55,
            'normal_min' => 60,
            'normal_max' => 100,
            'timestamp' => '2023-01-01'
        ];

        $alert = AlertItem::fromRow($row);

        $this->assertTrue($alert->isBelowMin);
        $this->assertFalse($alert->isAboveMax);
    }

    /**
     * Test fromRow detects above max.
     * Test fromRow détecte valeur au dessus du maximum.
     */
    public function testFromRowAboveMax(): void
    {
        $row = [
            'parameter_id' => 1,
            'display_name' => 'BPM',
            'unit' => 'bpm',
            'value' => 115,
            'normal_min' => 60,
            'normal_max' => 100,
            'timestamp' => '2023-01-01'
        ];

        $alert = AlertItem::fromRow($row);

        $this->assertFalse($alert->isBelowMin);
        $this->assertTrue($alert->isAboveMax);
    }

    /**
     * Test fromRow detects critical.
     * Test fromRow détecte valeur critique.
     */
    public function testFromRowCritical(): void
    {
        $row = [
            'parameter_id' => 1,
            'display_name' => 'BPM',
            'unit' => 'bpm',
            'value' => 35,
            'normal_min' => 60,
            'normal_max' => 100,
            'critical_min' => 40,
            'critical_max' => 140,
            'timestamp' => '2023-01-01'
        ];

        $alert = AlertItem::fromRow($row);

        $this->assertTrue($alert->isCritical);
    }

    /**
     * Test toArray conversion.
     * Test conversion en tableau.
     */
    public function testToArray(): void
    {
        $alert = new AlertItem(
            parameterId: 'temp',
            displayName: 'Température',
            unit: '°C',
            value: 38.5,
            minThreshold: 36.0,
            maxThreshold: 38.0,
            criticalMin: 34.0,
            criticalMax: 40.0,
            timestamp: '2023-01-01 12:00:00',
            isBelowMin: false,
            isAboveMax: true,
            isCritical: false
        );

        $array = $alert->toArray();

        $this->assertEquals('temp', $array['parameterId']);
        $this->assertEquals('Température', $array['displayName']);
        $this->assertEquals(38.5, $array['value']);
        $this->assertTrue($array['isAboveMax']);
        $this->assertFalse($array['isCritical']);
    }

    /**
     * Test fromRow with missing fields uses defaults.
     * Test fromRow utilise les défauts pour champs manquants.
     */
    public function testFromRowMissingFields(): void
    {
        $row = [
            'value' => 50
        ];

        $alert = AlertItem::fromRow($row);

        $this->assertEquals('', $alert->parameterId);
        $this->assertEquals('', $alert->displayName);
        $this->assertEquals(50.0, $alert->value);
        $this->assertNull($alert->minThreshold);
        $this->assertNull($alert->maxThreshold);
    }
}
