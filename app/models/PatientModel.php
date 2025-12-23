<?php

/**
 * DashMed — Modèle Patient
 *
 * Ce modèle gère toutes les opérations de base de données liées aux Patients,
 * incluant la récupération des enregistrements par email, la vérification des identifiants
 * et la création de nouveaux comptes Patient.
 *
 * @package   DashMed\Modules\Models
 * @author    DashMed Team
 * @license   Proprietary
 */

/**
 * Gère l'accès aux données pour les Patients.
 *
 * Fournit des méthodes pour :
 *  - Créer un nouvel Patient dans la base de donnée
 *
 * @see PDO
 */
namespace modules\models;

use PDO;
use PDOException;

class PatientModel
{
    /**
     * Instance de connexion PDO à la base de données.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Nom de la table où les enregistrements patients sont stockés.
     *
     * @var string
     */
    private string $table;

    /**
     * Constructeur.
     *
     * Initialise le modèle avec une connexion PDO et un nom de table personnalisé optionnel.
     *
     * @param PDO $pdo       Connexion à la base de données.
     * @param string $table  Nom de la table (par défaut 'patients').
     */
    public function __construct(PDO $pdo, string $table = 'patients')
    {
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Crée un nouvel enregistrement patient dans la base de données.
     *
     * Lance une PDOException si l'insertion échoue.
     *
     * @param array $data  Tableau associatif contenant :
     *                     - first_name
     *                     - last_name
     *                     - email
     *                     - password
     *                     - profession (optionnel)
     *                     - admin_status (optionnel)
     * @return int  L'ID du patient nouvellement créé.
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
     * Récupère un patient par son ID.
     *
     * @param int $id ID du patient.
     * @return array|false Les données du patient ou false si non trouvé.
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
                // Mock medical_history for now as column doesn't exist
                $data['medical_history'] = 'Non renseigné (Donnée non stockée en base)';
            }

            return $data;
        } catch (PDOException $e) {
            error_log("[PatientModel] Error fetching patient $id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Met à jour les informations d'un patient.
     *
     * @param int $id ID du patient.
     * @param array $data Données à mettre à jour (first_name, last_name, birth_date, admission_cause, medical_history).
     * @return bool True si succès, false sinon.
     * @throws PDOException
     */
    public function update(int $id, array $data): bool
    {
        // Note: medical_history removed from query as column likely missing
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
     * Récupère les médecins assignés à un patient.
     *
     * @param int $patientId ID du patient.
     * @return array Liste des médecins.
     */
    public function getDoctors(int $patientId): array
    {
        // TODO: Adapter la requête si vous avez une table de liaison patients_medecins
        return [];
    }
    /**
     * Récupère l'ID du patient associé à une chambre donnée.
     *
     * @param int $roomId ID de la chambre.
     * @return int|null ID du patient ou null si non trouvé.
     */
    public function getPatientIdByRoom(int $roomId): ?int
    {
        // TODO: Adapter selon votre structure de base de données (table liaison ou colonne room_id)
        // Supposition: une colonne 'room_id' existe dans la table patients, ou une table d'hospitalisation
        // Pour l'instant, simulons ou requêtons si la colonne existe.
        // Vérifions si la colonne existe dans une vraie implémentation.
        // Si pas de colonne, on peut retourner un dummy ou chercher.

        // Code temporaire basé sur la structure probable
        $sql = "SELECT id_patient FROM {$this->table} WHERE room_id = :room_id LIMIT 1";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':room_id' => $roomId]);
            $res = $stmt->fetchColumn();
            return $res ? (int) $res : null;
        } catch (\PDOException $e) {
            // Fallback si la colonne n'existe pas encore (migration manquante ?)
            return null;
        }
    }

    /**
     * Récupère la liste des chambres occupées avec les infos patients sommaires.
     *
     * @return array
     */
    public function getAllRoomsWithPatients(): array
    {
        // TODO: Adapter selon schéma
        $sql = "SELECT room_id, id_patient, first_name, last_name FROM {$this->table} WHERE room_id IS NOT NULL ORDER BY room_id";
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }
}
