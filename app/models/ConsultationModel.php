<?php

namespace modules\models;

use PDO;

require_once __DIR__ . '/Consultation.php';

/**
 * Model for managing medical consultations.
 *
 * This model handles database operations for consultation data including
 * retrieval, creation, updating, and deletion of consultation records.
 *
 * @package modules\models
 */
class ConsultationModel
{
    /**
     * PDO database connection instance.
     *
     * @var PDO
     */
    private \PDO $pdo;

    /**
     * Constructor for ConsultationModel.
     *
     * @param PDO $pdo Database connection instance
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all consultations for a specific patient.
     *
     * Queries the `view_consultations` view to obtain the complete medical
     * history for a patient, ordered by date in descending order (most recent first).
     *
     * @param int $idPatient The patient's unique identifier
     * @return Consultation[] Array of Consultation objects, or empty array on error
     */
    public function getConsultationsByPatientId(int $idPatient): array
    {
        $consultations = [];

        try {
            $sql = "SELECT * FROM view_consultations WHERE id_patient = :id_patient ORDER BY date DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id_patient', $idPatient, PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as $row) {
                $consultations[] = new Consultation(
                    (int) $row['id_consultations'],
                    (int) $row['id_user'],
                    $row['last_name'],
                    $row['date'],
                    $row['title'],
                    $row['type'],
                    $row['note'],
                );
            }

        } catch (\PDOException $e) {
            error_log("Erreur ConsultationModel::getConsultationsByPatientId : " . $e->getMessage());
            return [];
        }

        return $consultations;
    }

    /**
     * Creates a new consultation record.
     *
     * Inserts a new consultation into the database with all provided details.
     *
     * @param int $idPatient The patient's unique identifier
     * @param int $idDoctor The doctor's (user) unique identifier
     * @param string $date Date in format YYYY-MM-DD or YYYY-MM-DD HH:MM:SS
     * @param string $type Type of consultation
     * @param string $note Notes or consultation report
     * @param string $title Consultation title
     * @return bool True on success, false on failure
     */
    public function createConsultation(int $idPatient, int $idDoctor, string $date, string $type, string $note, string $title): bool
    {
        try {
            $sql = "INSERT INTO consultations (id_patient, id_user, date, type, note, title) 
                    VALUES (:id_patient, :id_user, :date, :type, :note, :title)";

            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                ':id_patient' => $idPatient,
                ':id_user' => $idDoctor,
                ':date' => $date,
                ':type' => $type,
                ':note' => $note,
                ':title' => $title
            ]);

        } catch (\PDOException $e) {
            error_log("Erreur ConsultationModel::createConsultation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates an existing consultation record.
     *
     * Modifies all fields of a consultation and updates the timestamp.
     *
     * @param int $idConsultation The consultation's unique identifier
     * @param int $idUser The doctor's (user) unique identifier
     * @param string $date Date in format YYYY-MM-DD or YYYY-MM-DD HH:MM:SS
     * @param string $type Type of consultation
     * @param string $note Notes or consultation report
     * @param string $title Consultation title
     * @return bool True on success, false on failure
     */
    public function updateConsultation(int $idConsultation, int $idUser, string $date, string $type, string $note, string $title): bool
    {
        try {
            $sql = "UPDATE consultations 
                    SET id_user = :id_user, 
                        date = :date, 
                        type = :type, 
                        note = :note, 
                        title = :title,
                        updated_at = NOW()
                    WHERE id_consultations = :id_consultation";

            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                ':id_consultation' => $idConsultation,
                ':id_user' => $idUser,
                ':date' => $date,
                ':type' => $type,
                ':note' => $note,
                ':title' => $title
            ]);

        } catch (\PDOException $e) {
            error_log("Erreur ConsultationModel::updateConsultation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a consultation record.
     *
     * Permanently removes a consultation from the database.
     *
     * @param int $idConsultation The consultation's unique identifier
     * @return bool True on success, false on failure
     */
    public function deleteConsultation(int $idConsultation): bool
    {
        try {
            $sql = "DELETE FROM consultations WHERE id_consultations = :id_consultation";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id_consultation' => $idConsultation]);

        } catch (\PDOException $e) {
            error_log("Erreur ConsultationModel::deleteConsultation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves a single consultation by its ID.
     *
     * Fetches complete consultation details from the view.
     *
     * @param int $idConsultation The consultation's unique identifier
     * @return Consultation|null Consultation object if found, null otherwise
     */
    public function getConsultationById(int $idConsultation): ?Consultation
    {
        try {
            $sql = "SELECT * FROM view_consultations WHERE id_consultations = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $idConsultation]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return new Consultation(
                    (int) $row['id_consultations'],
                    (int) $row['id_user'],
                    $row['last_name'],
                    $row['date'],
                    $row['title'],
                    $row['type'],
                    $row['note'],
                    'Aucun'
                );
            }
            return null;

        } catch (\PDOException $e) {
            error_log("Erreur ConsultationModel::getConsultationById : " . $e->getMessage());
            return null;
        }
    }
}