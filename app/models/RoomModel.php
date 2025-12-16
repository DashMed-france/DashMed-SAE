<?php

namespace modules\models;

use Database;
use PDO;

class RoomModel
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Récupère toutes les chambres disponibles (basé sur la table patients).
     *
     * @return array Liste des chambres (room_id).
     */
    public function getAllRooms(): array
    {
        $stmt = $this->pdo->query("
            SELECT DISTINCT
                room_id
            FROM patients
            WHERE room_id IS NOT NULL
            ORDER BY room_id
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
