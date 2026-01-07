<?php

/**
 * DashMed — Patient Model
 *
 * This model handles all database operations related to Patients,
 * including retrieving records by email, verifying credentials,
 * and creating new Patient accounts.
 *
 * @package   DashMed\Modules\Models
 * @author    DashMed Team
 * @license   Proprietary
 */

/**
 * Manages data access for Patients.
 *
 * Provides methods for:
 *  - Creating a new Patient in the database
 *  - Retrieving patient information
 *  - Updating patient records
 *  - Managing patient-doctor associations
 *  - Handling room assignments
 *
 * @see PDO
 */
namespace modules\models;

use PDO;
use PDOException;

class PatientModel
{
    /**
     * PDO database connection instance.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Name of the table where patient records are stored.
     *
     * @var string
     */
    private string $table;

    /**
     * Constructor.
     *
     * Initializes the model with a PDO connection and an optional custom table name.
     *
     * @param PDO $pdo       Database connection.
     * @param string $table  Table name (defaults to 'patients').
     */
    public function __construct(PDO $pdo, string $table = 'patients')
    {
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Creates a new patient record in the database.
     *
     * Throws a PDOException if the insertion fails.
     *
     * @param array $data  Associative array containing:
     *                     - first_name
     *                     - last_name
     *                     - email
     *                     - password
     *                     - profession (optional)
     *                     - admin_status (optional)
     * @return int  The ID of the newly created patient.
     * @throws PDOException
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
     * Retrieves a patient by their ID.
     *
     * @param int $id Patient ID.
     * @return array|false Patient data or false if not found.
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

            if ($data) {
                $data['medical_history'] = 'Non renseigné (Donnée non stockée en base)';
            }

            return $data;
        } catch (PDOException $e) {
            error_log("[PatientModel] Error fetching patient $id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates patient information.
     *
     * @param int $id Patient ID.
     * @param array $data Data to update (first_name, last_name, birth_date, admission_cause, medical_history).
     * @return bool True on success, false otherwise.
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
            error_log("[PatientModel] Error updating patient $id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieves doctors assigned to a patient.
     *
     * @param int $patientId Patient ID.
     * @return array List of doctors.
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
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PatientModel::getDoctors Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves the patient ID associated with a given room.
     *
     * @param int $roomId Room ID.
     * @return int|null Patient ID or null if not found.
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
     * Retrieves the list of occupied rooms with summary patient information.
     *
     * @return array List of rooms with patient data.
     */
    public function getAllRoomsWithPatients(): array
    {
        $sql = "SELECT room_id, id_patient, first_name, last_name FROM {$this->table} WHERE room_id IS NOT NULL ORDER BY room_id";
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }
}