<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use modules\services\AlertService;
use modules\models\alert\AlertItem;

/**
 * Class AlertServiceTest | Tests du Service d'Alertes
 *
 * Tests for alert message building and severity determination.
 * Tests pour la construction des messages et la détermination de la sévérité.
 *
 * @package Tests\Services
 * @author DashMed Team
 */
class AlertServiceTest extends TestCase
{
    private AlertService $alertService;

    protected function setUp(): void
    {
        $this->alertService = new AlertService();
    }

    /**
     * Test building messages from empty alerts.
     * Test construction de messages à partir d'alertes vides.
     */
    public function testBuildAlertMessagesEmpty(): void
    {
        $messages = $this->alertService->buildAlertMessages([]);
        $this->assertEmpty($messages);
    }

    /**
     * Test warning alert message generation.
     * Test génération message d'alerte warning.
     */
    public function testBuildAlertMessagesWarningHigh(): void
    {
        $alert = new AlertItem(
            parameterId: 'bpm',
            displayName: 'Fréquence cardiaque',
            unit: 'bpm',
            value: 110.0,
            minThreshold: 60.0,
            maxThreshold: 100.0,
            criticalMin: 40.0,
            criticalMax: 140.0,
            timestamp: '2023-01-01 12:00:00',
            isBelowMin: false,
            isAboveMax: true,
            isCritical: false
        );

        $messages = $this->alertService->buildAlertMessages([$alert]);

        $this->assertCount(1, $messages);
        $this->assertEquals('warning', $messages[0]['type']);
        $this->assertStringContains('Fréquence cardiaque', $messages[0]['title']);
        $this->assertStringContains('seuil max', $messages[0]['message']);
        $this->assertEquals('high', $messages[0]['direction']);
    }

    /**
     * Test critical alert message generation.
     * Test génération message d'alerte critique.
     */
    public function testBuildAlertMessagesCritical(): void
    {
        $alert = new AlertItem(
            parameterId: 'spo2',
            displayName: 'SpO2',
            unit: '%',
            value: 85.0,
            minThreshold: 95.0,
            maxThreshold: 100.0,
            criticalMin: 90.0,
            criticalMax: null,
            timestamp: '2023-01-01 12:00:00',
            isBelowMin: true,
            isAboveMax: false,
            isCritical: true
        );

        $messages = $this->alertService->buildAlertMessages([$alert]);

        $this->assertCount(1, $messages);
        $this->assertEquals('error', $messages[0]['type']);
        $this->assertStringContains('CRITIQUE', $messages[0]['title']);
        $this->assertEquals('low', $messages[0]['direction']);
    }

    /**
     * Test low value warning.
     * Test avertissement valeur basse.
     */
    public function testBuildAlertMessagesWarningLow(): void
    {
        $alert = new AlertItem(
            parameterId: 'temp',
            displayName: 'Température',
            unit: '°C',
            value: 35.0,
            minThreshold: 36.0,
            maxThreshold: 38.0,
            criticalMin: 34.0,
            criticalMax: 40.0,
            timestamp: '2023-01-01 12:00:00',
            isBelowMin: true,
            isAboveMax: false,
            isCritical: false
        );

        $messages = $this->alertService->buildAlertMessages([$alert]);

        $this->assertCount(1, $messages);
        $this->assertEquals('warning', $messages[0]['type']);
        $this->assertStringContains('seuil min', $messages[0]['message']);
        $this->assertEquals('low', $messages[0]['direction']);
    }

    /**
     * Helper: Check if string contains substring.
     */
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '$haystack' contains '$needle'"
        );
    }
}
