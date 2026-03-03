<?php

namespace Tests\Models\Repositories;

use PHPUnit\Framework\TestCase;
use modules\models\repositories\RoomRepository;
use PDO;

/**
 * Class RoomRepositoryTest | Tests du Repository Room
 *
 * Tests for room management.
 *
 * @package Tests\Models\Repositories
 * @author DashMed Team
 */
class RoomRepositoryTest extends TestCase
{
    private PDO $pdo;
    private RoomRepository $roomRepo;

    /**
     * Setup in-memory database and create table structure for tests.
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("CREATE TABLE rooms (
            id_room INTEGER PRIMARY KEY AUTOINCREMENT,
            number TEXT NOT NULL,
            type TEXT DEFAULT 'Standard'
        )");

        $this->pdo->exec("CREATE TABLE patients (
            id_patient INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            status TEXT DEFAULT 'En réanimation',
            room_id INTEGER,
            FOREIGN KEY (room_id) REFERENCES rooms(id_room)
        )");

        $this->roomRepo = new RoomRepository($this->pdo);
    }

    /**
     * Test retrieving available rooms correctly ignores occupied rooms.
     */
    public function testGetAvailableRooms(): void
    {
        // Add 3 rooms
        $this->pdo->exec("INSERT INTO rooms (id_room, number, type) VALUES (1, '1', 'Standard')");
        $this->pdo->exec("INSERT INTO rooms (id_room, number, type) VALUES (2, '102', 'Isolement')");
        $this->pdo->exec("INSERT INTO rooms (id_room, number, type) VALUES (3, '103', 'Standard')");

        // Occupy room 1 with an active patient
        $this->pdo->exec("INSERT INTO patients (id_patient, first_name, last_name, email, status, room_id) 
            VALUES (1, 'John', 'Doe', 'john@example.com', 'En réanimation', 1)");

        // Occupy room 2 but patient is discharged ('Sorti')
        $this->pdo->exec("INSERT INTO patients (id_patient, first_name, last_name, email, status, room_id) 
            VALUES (2, 'Jane', 'Doe', 'jane@example.com', 'Sorti', 2)");

        // Rooms available should be 2 and 3
        $availableRooms = $this->roomRepo->getAvailableRooms();
        
        $this->assertIsArray($availableRooms);
        $this->assertCount(2, $availableRooms);

        // Check if correct ids are returned
        $ids = array_column($availableRooms, 'id_room');
        $this->assertContains(2, $ids);
        $this->assertContains(3, $ids);
        $this->assertNotContains(1, $ids);
    }
}
