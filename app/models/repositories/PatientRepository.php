<?php

declare(strict_types=1);

namespace modules\models\repositories;

use modules\models\BaseRepository;
use PDO;
use PDOException;

/**
 * Class PatientRepository
 *
 * Data access layer for Patients.
 *
 * @package DashMed\Modules\Models\Repositories
 * @author DashMed Team
 * @license Proprietary
 */
class PatientRepository extends BaseRepository
{
    private string $table = 'patients';

    /**
     * Creates a new patient record.
     *
     * @param array{
     *   first_name: string,
     *   last_name: string,
     *   email: string,
     *   password: string,
     *   profession?: string|null,
     *   admin_status?: int
     * } $data Patient data
     * @return int New Patient ID
     * @throws PDOException If insertion fails
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table}
                (first_name, last_name, email, password, profession, admin_status)
                VALUES (:first_name, :last_name, :email, :password, :profession, :admin_status)";
        $st = $this->pdo->prepare($sql);
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);

        try {
            $st->execute([
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':email' => $data['email'],
                ':password' => $hash,
                ':profession' => $data['profession'] ?? null,
                ':admin_status' => (int) ($data['admin_status'] ?? 0),
            ]);
        } catch (PDOException $e) {
            throw $e;
        }

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Finds a patient by ID.
     *
     * @param int $id Patient ID
     * @return array{
     *   id_patient: int,
     *   first_name: string,
     *   last_name: string,
     *   birth_date: string|null,
     *   gender: string|null,
     *   admission_cause: string|null,
     *   medical_history: string
     * }|false Patient data or false
     * @throws PDOException
     */
    public function findById(int $id): array|false
    {
        $sql = "SELECT 
                p.id_patient,
                p.first_name,
                p.last_name,
                p.birth_date,
                p.gender,
                p.description as admission_cause
            FROM {$this->table} p
            WHERE p.id_patient = :id";

        $stmt = $this->pdo->prepare($sql);

        try {
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (is_array($data) && !empty($data)) {
                /** @var array{id_patient: int, first_name: string, last_name: string, birth_date: string|null, gender: string|null, admission_cause: string|null, medical_history: string} $data */
                $data['medical_history'] = 'Non renseigné';
                return $data;
            }

            return false;
        } catch (PDOException $e) {
            error_log("[PatientRepository] Error fetching patient $id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates patient information.
     *
     * @param int $id Patient ID
     * @param array{
     *   first_name: string,
     *   last_name: string,
     *   birth_date?: string,
     *   admission_cause: string,
     *   medical_history?: string
     * } $data Update data
     * @return bool True on success
     * @throws PDOException
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} 
            SET first_name = :first_name,
                last_name = :last_name,
                birth_date = :birth_date,
                description = :admission_cause,
                updated_at = CURRENT_TIMESTAMP
            WHERE id_patient = :id";

        $stmt = $this->pdo->prepare($sql);

        try {
            return $stmt->execute([
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':birth_date' => !empty($data['birth_date']) ? $data['birth_date'] : null,
                ':admission_cause' => $data['admission_cause'],
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("[PatientRepository] Error updating patient $id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieves doctors assigned to a patient.
     *
     * @param int $patientId Patient ID
     * @return array<int, array{
     *   id_user: int,
     *   first_name: string,
     *   last_name: string,
     *   profession_name: string|null
     * }> List of doctors
     */
    public function getDoctors(int $patientId): array
    {
        $sql = "SELECT DISTINCT 
                    u.id_user, 
                    u.first_name, 
                    u.last_name, 
                    p.label_profession as profession_name
                FROM users u
                JOIN consultations c ON u.id_user = c.id_user
                LEFT JOIN professions p ON u.id_profession = p.id_profession
                WHERE c.id_patient = :patientId
                ORDER BY u.last_name, u.first_name";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':patientId' => $patientId]);
            /** @var array<int, array{id_user: int, first_name: string, last_name: string, profession_name: string|null}> */
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PatientRepository::getDoctors Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves the Patient ID associated with a room.
     *
     * @param int $roomId Room ID
     * @return int|null Patient ID or null
     */
    public function getPatientIdByRoom(int $roomId): ?int
    {
        $sql = "SELECT id_patient FROM {$this->table} WHERE room_id = :room_id LIMIT 1";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':room_id' => $roomId]);
            $res = $stmt->fetchColumn();
            return $res ? (int) $res : null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Retrieves list of occupied rooms with patient info.
     *
     * @return array<int, array{
     *   room_id: int,
     *   id_patient: int,
     *   first_name: string,
     *   last_name: string
     * }> List of rooms
     */
    public function getAllRoomsWithPatients(): array
    {
        $sql = "
            SELECT room_id,
            id_patient,
            first_name,
            last_name 
            FROM {$this->table}
            WHERE room_id IS NOT NULL
            ORDER BY room_id";
        try {
            $stmt = $this->pdo->query($sql);
            if ($stmt === false) {
                return [];
            }
            /** @var array<int, array{room_id: int, id_patient: int, first_name: string, last_name: string}> */
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Creates a new patient in the patients table.
     *
     * @param array{
     *   first_name: string,
     *   last_name: string,
     *   email: string,
     *   birth_date: string,
     *   weight: string|float,
     *   height: string|float,
     *   gender: string,
     *   description?: string|null
     * } $data Patient data
     * @return int New Patient ID
     * @throws PDOException If insertion fails
     */
    public function createPatient(array $data): int
    {
        $sql = "INSERT INTO {$this->table}
                (first_name, last_name, email, birth_date, weight, height, gender, description)
                VALUES (:first_name, :last_name, :email, :birth_date, :weight, :height, :gender, :description)";
        $st = $this->pdo->prepare($sql);

        try {
            $st->execute([
                ':first_name'  => $data['first_name'],
                ':last_name'   => $data['last_name'],
                ':email'       => $data['email'],
                ':birth_date'  => $data['birth_date'],
                ':weight'      => (float) $data['weight'],
                ':height'      => (float) $data['height'],
                ':gender'      => $data['gender'],
                ':description' => $data['description'] ?? null,
            ]);
        } catch (PDOException $e) {
            throw $e;
        }

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Checks if a patient email already exists.
     *
     * @param string $email Patient email
     * @return bool True if exists
     */
    public function emailExists(string $email): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        $st = $this->pdo->prepare($sql);
        $st->execute([':email' => $email]);
        return (int) $st->fetchColumn() > 0;
    }
}
