<?php

declare(strict_types=1);

namespace modules\services;

use modules\models\Alert\AlertItem;

/**
 * Class AlertService | Service d'Alerte
 *
 * Service for transforming alerts into UI messages.
 * Service de transformation des alertes en messages UI.
 *
 * Handles severity determination and message formatting.
 * Gère la détermination de la sévérité et le formatage des messages.
 *
 * @package DashMed\Modules\Services
 * @author DashMed Team
 * @license Proprietary
 */
class AlertService
{
    /** @var string Error severity | Sévérité Erreur */
    public const SEVERITY_ERROR = 'error';
    /** @var string Warning severity | Sévérité Avertissement */
    public const SEVERITY_WARNING = 'warning';
    /** @var string Info severity | Sévérité Information */
    public const SEVERITY_INFO = 'info';

    /**
     * @var array<string, array{prefix: string, icon: string}> Configuration for severity levels (prefix, icon).
     *            Configuration des niveaux de sévérité (préfixe, icône).
     */
    private const SEVERITY_CONFIG = [
        self::SEVERITY_ERROR => ['prefix' => '🚨 ALERTE CRITIQUE', 'icon' => 'ico-error'],
        self::SEVERITY_WARNING => ['prefix' => '⚠️ Attention', 'icon' => 'ico-warning'],
        self::SEVERITY_INFO => ['prefix' => 'ℹ️ Information', 'icon' => 'ico-info'],
    ];

    /**
     * Builds UI messages from a list of alert items.
     * Construit les messages UI à partir d'une liste d'alertes.
     *
     * @param AlertItem[] $alerts List of AlertItem objects | Liste d'objets AlertItem.
     * @return array<int, array<string, mixed>> List of formatted messages | Liste des messages formatés.
     */
    public function buildAlertMessages(array $alerts): array
    {
        return array_map(fn(AlertItem $a) => $this->buildSingleMessage($a), $alerts);
    }

    /**
     * Builds a single UI message from an alert item.
     * Construit un message UI unique pour une alerte donnée.
     *
     * @param AlertItem $alert The alert item | L'élément d'alerte.
     * @return array<string, mixed> The formatted message data | Les données du message formatées.
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
     * Détermine la sévérité d'une alerte.
     *
     * @param AlertItem $alert The alert item | L'élément d'alerte.
     * @return string The severity constant | La constante de sévérité.
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
     * Construit le message texte pour une alerte.
     *
     * @param AlertItem $alert The alert item | L'élément d'alerte.
     * @return string The formatted message string | La chaîne de message formatée.
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
     * Formate une valeur flottante.
     *
     * @param float $v Value to format | Valeur à formater.
     * @return string Formatted string | Chaîne formatée.
     */
    private function fmt(float $v): string
    {
        return number_format($v, 1, ',', ' ');
    }

    /**
     * Escapes a string for HTML output.
     * Échappe une chaîne pour l'affichage HTML.
     *
     * @param string|null $t Text to escape | Texte à échapper.
     * @return string Escaped text | Texte échappé.
     */
    private function esc(?string $t): string
    {
        return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
    }
}
