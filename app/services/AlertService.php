<?php

declare(strict_types=1);

namespace modules\services;

use modules\models\entities\AlertItem;

/**
 * Class AlertService
 *
 * Service for transforming alerts into UI messages.
 * Handles severity determination and message formatting.
 *
 * @package DashMed\Modules\Services
 * @author DashMed Team
 * @license Proprietary
 */
class AlertService
{
    /** @var string Error severity */
    public const SEVERITY_ERROR = 'error';
    /** @var string Warning severity */
    public const SEVERITY_WARNING = 'warning';
    /** @var string Info severity */
    public const SEVERITY_INFO = 'info';

    /**
     * @var array<string, array{prefix: string, icon: string}> Configuration for severity levels (prefix, icon).
     */
    private const SEVERITY_CONFIG = [
        self::SEVERITY_ERROR => ['prefix' => '🚨 ALERTE CRITIQUE', 'icon' => 'ico-error'],
        self::SEVERITY_WARNING => ['prefix' => '⚠️ Attention', 'icon' => 'ico-warning'],
        self::SEVERITY_INFO => ['prefix' => 'ℹ️ Information', 'icon' => 'ico-info'],
    ];

    /**
     * Builds UI messages from a list of alert items.
     *
     * @param AlertItem[] $alerts List of AlertItem objects
     * @return array<int, array<string, mixed>> List of formatted messages
     */
    public function buildAlertMessages(array $alerts): array
    {
        return array_map(fn(AlertItem $a) => $this->buildSingleMessage($a), $alerts);
    }

    /**
     * Builds a single UI message from an alert item.
     *
     * @param AlertItem $alert The alert item
     * @return array<string, mixed> The formatted message data
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

    /**
     * Determines the severity of an alert.
     *
     * @param AlertItem $alert The alert item
     * @return string The severity constant
     */
    private function determineSeverity(AlertItem $alert): string
    {
        if ($alert->isCritical) {
            return self::SEVERITY_ERROR;
        }
        return ($alert->isBelowMin || $alert->isAboveMax) ? self::SEVERITY_WARNING : self::SEVERITY_INFO;
    }

    /**
     * Builds the text message for an alert.
     *
     * @param AlertItem $alert The alert item
     * @return string The formatted message string
     */
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

    /**
     * Formats a float value.
     *
     * @param float $v Value to format
     * @return string Formatted string
     */
    private function fmt(float $v): string
    {
        return number_format($v, 1, ',', ' ');
    }

    /**
     * Escapes a string for HTML output.
     *
     * @param string|null $t Text to escape
     * @return string Escaped text
     */
    private function esc(?string $t): string
    {
        return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
    }
}
