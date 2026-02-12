<?php

declare(strict_types=1);

namespace modules\models\alert;

/**
 * DTO représentant une alerte de dépassement de seuil.
 * Immutable : toutes les propriétés sont en lecture seule.
 */
final class AlertItem
{
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
     */
    public static function fromRow(array $row): self
    {
        $value = self::toFloat($row['value'] ?? 0);
        $minThreshold = self::toFloatOrNull($row['normal_min'] ?? null);
        $maxThreshold = self::toFloatOrNull($row['normal_max'] ?? null);
        $criticalMin = self::toFloatOrNull($row['critical_min'] ?? null);
        $criticalMax = self::toFloatOrNull($row['critical_max'] ?? null);

        $isBelowMin = $minThreshold !== null && $value <= $minThreshold;
        $isAboveMax = $maxThreshold !== null && $value >= $maxThreshold;
        $isCritical = ($criticalMin !== null && $value <= $criticalMin)
            || ($criticalMax !== null && $value >= $criticalMax);

        $timestamp = self::toString($row['timestamp'] ?? '');

        return new self(
            parameterId: self::toString($row['parameter_id'] ?? ''),
            displayName: self::toString($row['display_name'] ?? ''),
            unit: self::toString($row['unit'] ?? ''),
            value: $value,
            minThreshold: $minThreshold,
            maxThreshold: $maxThreshold,
            criticalMin: $criticalMin,
            criticalMax: $criticalMax,
            timestamp: $timestamp !== '' ? $timestamp : date('Y-m-d H:i:s'),
            isBelowMin: $isBelowMin,
            isAboveMax: $isAboveMax,
            isCritical: $isCritical
        );
    }

    /**
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

    private static function toFloat(mixed $v): float
    {
        return is_numeric($v) ? (float) $v : 0.0;
    }

    private static function toFloatOrNull(mixed $v): ?float
    {
        return is_numeric($v) ? (float) $v : null;
    }

    private static function toString(mixed $v): string
    {
        return is_string($v) || is_numeric($v) ? (string) $v : '';
    }
}
