<?php

declare(strict_types=1);

namespace modules\models\interfaces;

/**
 * Interface EntityInterface
 *
 * Contract for all entity classes, ensuring consistent data conversion.
 *
 * @package DashMed\Modules\Models\Interfaces
 * @author DashMed Team
 * @license Proprietary
 */
interface EntityInterface
{
    /**
     * Converts the entity to an associative array.
     *
     * Useful for JSON serialization and API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
