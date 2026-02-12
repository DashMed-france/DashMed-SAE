<?php

declare(strict_types=1);

namespace modules\models\interfaces;

/**
 * Interface EntityInterface | Interface Entité
 *
 * Contract for all entity classes, ensuring consistent data conversion.
 * Contrat pour toutes les classes entités, garantissant une conversion de données cohérente.
 *
 * @package DashMed\Modules\Models\Interfaces
 * @author DashMed Team
 * @license Proprietary
 */
interface EntityInterface
{
    /**
     * Converts the entity to an associative array.
     * Convertit l'entité en tableau associatif.
     *
     * Useful for JSON serialization and API responses.
     * Utile pour la sérialisation JSON et les réponses API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
