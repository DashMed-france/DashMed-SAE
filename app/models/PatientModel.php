<?php

/**
 * DashMed — Patient Model | Modèle Patient
 *
 * Handles all database operations related to Patients, including retrieval,
 * verification, and creation.
 * Gère toutes les opérations de base de données liées aux Patients, incluant
 * la récupération, la vérification et la création.
 *
 * @package   DashMed\Modules\Models
 * @author    DashMed Team
 * @license   Proprietary
 */

namespace modules\models;

use PDO;
use PDOException;

/**
 * Class PatientModel | Classe PatientModel
 *
 * Data access layer for Patients.
 * Gère l'accès aux données pour les Patients.
 */
class PatientModel
{
    /** @var PDO Database connection | Connexion à la base de données */
    private PDO $pdo;

    /** @var string Table name | Nom de la table */
    private string $table;

    /**
     * Constructor | Constructeur
     *
     * @param PDO $pdo Database connection | Connexion BDD
     * @param string $table Table name (default: 'patients') | Nom de la table
     */
    public function __construct(PDO $pdo, string $table = 'patients')
    {
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Creates a new patient record.
     * Crée un nouvel enregistrement patient.
     *
     * @param array{first_name: string, last_name: string, email: string, password: string, profession?: string|null, admin_status?: int} $data Patient data (first_name, last_name, email, password, etc) | Données patient
     * @return int New Patient ID | ID du nouveau patient
     * @throws PDOException If insertion fails | Si l'insertion échoue
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
     * Récupère un patient par son ID.
     *
     * @param int $id Patient ID | ID du patient
     * @return array{id_patient: int, first_name: string, last_name: string, birth_date: string|null, gender: string|null, admission_cause: string|null, medical_history: string}|false Patient data or false | Données du patient ou false
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
                $data['medical_history'] = 'Not provided (Data not stored in DB) | Non renseigné (Donnée non stockée en base)';
                return $data;
            }

            return false;
        } catch (PDOException $e) {
            error_log("[PatientModel] Error fetching patient $id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates patient information.
     * Met à jour les informations d'un patient.
     *
     * @param int $id Patient ID | ID du patient
     * @param array{first_name: string, last_name: string, birth_date?: string, admission_cause: string, medical_history?: string} $data Update data | Données à mettre à jour
     * @return bool True on success | True si succès
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
     * Récupère les médecins assignés à un patient.
     *
     * @param int $patientId Patient ID | ID du patient
     * @return array<int, array{id_user: int, first_name: string, last_name: string, profession_name: string|null}> List of doctors | Liste des médecins
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
            error_log("PatientModel::getDoctors Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves the Patient ID associated with a room.
     * Récupère l'ID du patient associé à une chambre.
     *
     * @param int $roomId Room ID | ID de la chambre
     * @return int|null Patient ID or null | ID du patient ou null
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
     * Récupère la liste des chambres occupées avec les infos patients.
     *
     * @return array<int, array{room_id: int, id_patient: int, first_name: string, last_name: string}> List of rooms | Liste des chambres
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
}
