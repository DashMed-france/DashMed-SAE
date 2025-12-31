<?php

declare(strict_types=1);

namespace modules\services;

use modules\models\Alert\AlertItem;

/**
 * Service de transformation des alertes en messages UI.
 */
class AlertService
{
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_INFO = 'info';

    private const SEVERITY_CONFIG = [
        self::SEVERITY_ERROR => ['prefix' => '🚨 ALERTE CRITIQUE', 'icon' => 'ico-error'],
        self::SEVERITY_WARNING => ['prefix' => '⚠️ Attention', 'icon' => 'ico-warning'],
        self::SEVERITY_INFO => ['prefix' => 'ℹ️ Information', 'icon' => 'ico-info'],
    ];

    /**
     * @param AlertItem[] $alerts
     * @return array<int, array<string, mixed>>
     */
    public function buildAlertMessages(array $alerts): array
    {
        return array_map(fn(AlertItem $a) => $this->buildSingleMessage($a), $alerts);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSingleMessage(AlertItem $alert): array
    {
        $severity = $this->determineSeverity($alert);
        $config = self::SEVERITY_CONFIG[$severity] ?? self::SEVERITY_CONFIG[self::SEVERITY_INFO];

        return [
            'type' => $severity,
            'title' => $config['prefix'] . ' — ' . $this->esc($alert->displayName),
            'message' => $this->buildMessage($alert),
            'icon' => $config['icon'],
            'parameterId' => $alert->parameterId,
            'value' => $alert->value,
            'unit' => $alert->unit,
            'threshold' => $alert->isBelowMin ? $alert->minThreshold : $alert->maxThreshold,
            'direction' => $alert->isBelowMin ? 'low' : 'high',
            'timestamp' => $alert->timestamp,
        ];
    }

    private function determineSeverity(AlertItem $alert): string
    {
        if ($alert->isCritical) {
            return self::SEVERITY_ERROR;
        }
        return ($alert->isBelowMin || $alert->isAboveMax) ? self::SEVERITY_WARNING : self::SEVERITY_INFO;
    }

    private function buildMessage(AlertItem $alert): string
    {
        $val = $this->fmt($alert->value);
        $unit = $this->esc($alert->unit);

        if ($alert->isBelowMin && $alert->minThreshold !== null) {
            return "Valeur basse : {$val} {$unit} (seuil min : {$this->fmt($alert->minThreshold)} {$unit})";
        }
        if ($alert->isAboveMax && $alert->maxThreshold !== null) {
            return "Valeur haute : {$val} {$unit} (seuil max : {$this->fmt($alert->maxThreshold)} {$unit})";
        }
        return "Valeur actuelle : {$val} {$unit}";
    }

    private function fmt(float $v): string
    {
        return number_format($v, 1, ',', ' ');
    }

    private function esc(?string $t): string
    {
        return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
    }
}
