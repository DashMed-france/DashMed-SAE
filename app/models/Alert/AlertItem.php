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
        // Helpers pour conversion sécurisée (PHPStan level 9)
        $toFloat = static fn(mixed $v): float => is_numeric($v) ? (float) $v : 0.0;
        $toFloatOrNull = static fn(mixed $v): ?float => is_numeric($v) ? (float) $v : null;
        $toString = static fn(mixed $v): string => is_string($v) || is_numeric($v) ? (string) $v : '';

        $value = $toFloat($row['value'] ?? 0);
        $minThreshold = $toFloatOrNull($row['normal_min'] ?? null);
        $maxThreshold = $toFloatOrNull($row['normal_max'] ?? null);
        $criticalMin = $toFloatOrNull($row['critical_min'] ?? null);
        $criticalMax = $toFloatOrNull($row['critical_max'] ?? null);

        $isBelowMin = $minThreshold !== null && $value < $minThreshold;
        $isAboveMax = $maxThreshold !== null && $value > $maxThreshold;

        $isCriticalLow = $criticalMin !== null && $value < $criticalMin;
        $isCriticalHigh = $criticalMax !== null && $value > $criticalMax;
        $isCritical = $isCriticalLow || $isCriticalHigh;

        return new self(
            parameterId: $toString($row['parameter_id'] ?? ''),
            displayName: $toString($row['display_name'] ?? ''),
            unit: $toString($row['unit'] ?? ''),
            value: $value,
            minThreshold: $minThreshold,
            maxThreshold: $maxThreshold,
            criticalMin: $criticalMin,
            criticalMax: $criticalMax,
            timestamp: $toString($row['timestamp'] ?? '') !== '' ? $toString($row['timestamp'] ?? '') : date('Y-m-d H:i:s'),
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
