<?php

namespace modules\services;

// On garde l'import, mais assure-toi que le fichier AlertItem.php existe bien !
use modules\models\Alert\AlertItem;

/**
 * Service de transformation des alertes en messages d'interface utilisateur.
 */
class AlertService
{
    // Types de sévérité pour iziToast
    const SEVERITY_ERROR = 'error';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_INFO = 'info';

    /**
     * Transforme une liste d'alertes en messages pour le JS.
     */
    public function buildAlertMessages(array $alerts)
    {
        $messages = [];

        foreach ($alerts as $alert) {
            // Vérification de l'instance pour éviter les erreurs
            if ($alert instanceof AlertItem) {
                $messages[] = $this->buildSingleMessage($alert);
            }
        }

        return $messages;
    }

    /**
     * Construit un message UI pour une alerte unique.
     */
    private function buildSingleMessage($alert)
    {
        $severity = $this->determineSeverity($alert);
        $title = $this->buildTitle($alert, $severity);
        $message = $this->buildMessage($alert);
        $icon = $this->getIcon($severity);

        return [
            'type' => $severity,
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'parameterId' => $alert->parameterId,
            'value' => $alert->value,
            'unit' => $alert->unit,
            'threshold' => $alert->isBelowMin ? $alert->minThreshold : $alert->maxThreshold,
            'direction' => $alert->isBelowMin ? 'low' : 'high',
            'timestamp' => $alert->timestamp,
        ];
    }

    /**
     * Détermine la sévérité de l'alerte.
     */
    private function determineSeverity($alert)
    {
        if (isset($alert->isCritical) && $alert->isCritical) {
            return self::SEVERITY_ERROR;
        }

        if ($alert->isBelowMin || $alert->isAboveMax) {
            return self::SEVERITY_WARNING;
        }

        return self::SEVERITY_INFO;
    }

    /**
     * Construit le titre de la notification (Remplacement de match par switch).
     */
    private function buildTitle($alert, $severity)
    {
        switch ($severity) {
            case self::SEVERITY_ERROR:
                $prefix = '🚨 ALERTE CRITIQUE';
                break;
            case self::SEVERITY_WARNING:
                $prefix = '⚠️ Attention';
                break;
            default:
                $prefix = 'ℹ️ Information';
                break;
        }

        return $prefix . ' — ' . $this->escapeHtml($alert->displayName);
    }

    /**
     * Construit le corps du message.
     */
    private function buildMessage($alert)
    {
        $valueStr = number_format((float)$alert->value, 1, ',', ' ');
        $unitStr = $this->escapeHtml($alert->unit);

        if ($alert->isBelowMin && $alert->minThreshold !== null) {
            $thresholdStr = number_format((float)$alert->minThreshold, 1, ',', ' ');
            return "Valeur basse : " . $valueStr . " " . $unitStr . " (seuil min : " . $thresholdStr . " " . $unitStr . ")";
        }

        if ($alert->isAboveMax && $alert->maxThreshold !== null) {
            $thresholdStr = number_format((float)$alert->maxThreshold, 1, ',', ' ');
            return "Valeur haute : " . $valueStr . " " . $unitStr . " (seuil max : " . $thresholdStr . " " . $unitStr . ")";
        }

        return "Valeur actuelle : " . $valueStr . " " . $unitStr;
    }

    /**
     * Retourne l'icône appropriée (Remplacement de match par switch).
     */
    private function getIcon($severity)
    {
        switch ($severity) {
            case self::SEVERITY_ERROR:
                return 'ico-error';
            case self::SEVERITY_WARNING:
                return 'ico-warning';
            default:
                return 'ico-info';
        }
    }

    /**
     * Échappe les caractères HTML.
     */
    private function escapeHtml($text)
    {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}