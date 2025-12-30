<?php

declare(strict_types=1);

namespace modules\models\Alert;

/**
 * DTO représentant une alerte de dépassement de seuil.
 * Immutable : toutes les propriétés sont en lecture seule.
 */
final class AlertItem
{
    /**
     * @param string $parameterId   Identifiant du paramètre (ex: 'SpO2', 'FC')
     * @param string $displayName   Nom affiché (ex: 'Saturation en O₂')
     * @param string $unit          Unité de mesure (ex: '%', 'bpm')
     * @param float  $value         Valeur mesurée
     * @param float|null $minThreshold Seuil minimum (null = pas de seuil)
     * @param float|null $maxThreshold Seuil maximum (null = pas de seuil)
     * @param float|null $criticalMin  Seuil critique minimum
     * @param float|null $criticalMax  Seuil critique maximum
     * @param string $timestamp     Horodatage de la mesure
     * @param bool   $isBelowMin    Vrai si la valeur est sous le seuil min
     * @param bool   $isAboveMax    Vrai si la valeur est au-dessus du seuil max
     * @param bool   $isCritical    Vrai si la valeur dépasse un seuil critique
     */
    public function __construct(
        public readonly string $parameterId,
        public readonly string $displayName,
        public readonly string $unit,
        public readonly float $value,
        public readonly ?float $minThreshold,
        public readonly ?float $maxThreshold,
        public readonly ?float $criticalMin,
        public readonly ?float $criticalMax,
        public readonly string $timestamp,
        public readonly bool $isBelowMin,
        public readonly bool $isAboveMax,
        public readonly bool $isCritical
    ) {
    }

    /**
     * Crée une instance AlertItem depuis un tableau associatif (row SQL).
     *
     * @param array<string, mixed> $row Données brutes de la requête SQL
     * @return self
     */
    public static function fromRow(array $row): self
    {
        $value = (float) ($row['value'] ?? 0);
        $minThreshold = isset($row['normal_min']) ? (float) $row['normal_min'] : null;
        $maxThreshold = isset($row['normal_max']) ? (float) $row['normal_max'] : null;
        $criticalMin = isset($row['critical_min']) ? (float) $row['critical_min'] : null;
        $criticalMax = isset($row['critical_max']) ? (float) $row['critical_max'] : null;

        $isBelowMin = $minThreshold !== null && $value < $minThreshold;
        $isAboveMax = $maxThreshold !== null && $value > $maxThreshold;

        $isCriticalLow = $criticalMin !== null && $value < $criticalMin;
        $isCriticalHigh = $criticalMax !== null && $value > $criticalMax;
        $isCritical = $isCriticalLow || $isCriticalHigh;

        return new self(
            parameterId: (string) ($row['parameter_id'] ?? ''),
            displayName: (string) ($row['display_name'] ?? ''),
            unit: (string) ($row['unit'] ?? ''),
            value: $value,
            minThreshold: $minThreshold,
            maxThreshold: $maxThreshold,
            criticalMin: $criticalMin,
            criticalMax: $criticalMax,
            timestamp: (string) ($row['timestamp'] ?? date('Y-m-d H:i:s')),
            isBelowMin: $isBelowMin,
            isAboveMax: $isAboveMax,
            isCritical: $isCritical
        );
    }

    /**
     * Convertit l'alerte en tableau pour sérialisation JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'parameterId' => $this->parameterId,
            'displayName' => $this->displayName,
            'unit' => $this->unit,
            'value' => $this->value,
            'minThreshold' => $this->minThreshold,
            'maxThreshold' => $this->maxThreshold,
            'criticalMin' => $this->criticalMin,
            'criticalMax' => $this->criticalMax,
            'timestamp' => $this->timestamp,
            'isBelowMin' => $this->isBelowMin,
            'isAboveMax' => $this->isAboveMax,
            'isCritical' => $this->isCritical,
        ];
    }
}
