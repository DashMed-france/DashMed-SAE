<?php

declare(strict_types=1);

namespace modules\models\repositories;

use PDO;
use Exception;

/**
 * Class RoomRepository
 *
 * Repository for managing rooms.
 *
 * @package DashMed\Modules\Models\Repositories
 */
class RoomRepository
{
    /** @var PDO Database connection */
    private PDO $pdo;

    /**
     * Constructor
     *
     * @param PDO $pdo Shared database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Gets all available rooms (rooms not occupied by an active patient).
     *
     * @return array<int, array{id_room: int, number: string, type: string}>
     */
    public function getAvailableRooms(): array
    {
        $sql = "SELECT r.id_room, r.number, r.type
                FROM rooms r
                LEFT JOIN patients p ON (p.room_id = r.id_room AND p.status = 'En réanimation')
                WHERE p.id_patient IS NULL
                ORDER BY r.id_room ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
