<?php

namespace modules\models;

use PDO;

/**
 * Class ConsultationModel | Classe ConsultationModel
 *
 * Manages access to medical consultation data.
 * Gère l'accès aux données des consultations médicales.
 *
 * @package DashMed\Modules\Models
 * @author DashMed Team
 * @license Proprietary
 */
class ConsultationModel
{
    /** @var PDO Database connection instance | Instance de connexion à la base de données */
    private \PDO $pdo;

    /**
     * Constructor | Constructeur
     *
     * @param PDO $pdo PDO Instance | Instance PDO
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves the list of consultations for a specific patient.
     * Récupère la liste des consultations pour un patient spécifique.
     *
     * @param int $idPatient Patient ID | ID du patient
     * @return Consultation[] Array of Consultation objects | Tableau d'objets Consultation
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
                if (!is_array($row)) {
                    continue;
                }
                $rawId = $row['id_consultations'] ?? 0;
                $rawIdUser = $row['id_user'] ?? 0;
                $rawLastName = $row['last_name'] ?? '';
                $rawDate = $row['date'] ?? '';
                $rawTitle = $row['title'] ?? '';
                $rawType = $row['type'] ?? '';
                $rawNote = $row['note'] ?? '';
                $consultations[] = new Consultation(
                    is_numeric($rawId) ? (int) $rawId : 0,
                    is_numeric($rawIdUser) ? (int) $rawIdUser : 0,
                    is_string($rawLastName) ? $rawLastName : '',
                    is_string($rawDate) ? $rawDate : '',
                    is_string($rawTitle) ? $rawTitle : '',
                    is_string($rawType) ? $rawType : '',
                    is_string($rawNote) ? $rawNote : '',
                );
            }
        } catch (\PDOException $e) {
            error_log("Error ConsultationModel::getConsultationsByPatientId : " . $e->getMessage());
            return [];
        }

        return $consultations;
    }

    /**
     * Creates a new consultation.
     * Crée une nouvelle consultation.
     *
     * @param int $idPatient Patient ID | ID du patient
     * @param int $idDoctor Doctor ID (User) | ID du médecin (utilisateur)
     * @param string $date Date (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
     * @param string $type Consultation Type | Type de consultation
     * @param string $note Notes or report | Notes ou compte rendu
     * @param string $title Consultation Title | Titre de la consultation
     * @return bool True on success, False otherwise | True si succès, False sinon
     */
    public function createConsultation(
        int $idPatient,
        int $idDoctor,
        string $date,
        string $type,
        string $note,
        string $title
    ): bool {
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
            error_log("Error ConsultationModel::createConsultation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates an existing consultation.
     * Met à jour une consultation existante.
     *
     * @param int $idConsultation Consultation ID | ID de la consultation
     * @param int $idUser Doctor ID | ID du médecin
     * @param string $date Date
     * @param string $type Type
     * @param string $note Notes
     * @param string $title Title | Titre
     * @return bool True on success, False otherwise | True si succès, False sinon
     */
    public function updateConsultation(
        int $idConsultation,
        int $idUser,
        string $date,
        string $type,
        string $note,
        string $title
    ): bool {
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
            error_log("Error ConsultationModel::updateConsultation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a consultation.
     * Supprime une consultation.
     *
     * @param int $idConsultation Consultation ID | ID de la consultation
     * @return bool True on success, False otherwise | True si succès, False sinon
     */
    public function deleteConsultation(int $idConsultation): bool
    {
        try {
            $sql = "DELETE FROM consultations WHERE id_consultations = :id_consultation";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id_consultation' => $idConsultation]);
        } catch (\PDOException $e) {
            error_log("Error ConsultationModel::deleteConsultation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves today's consultations for a patient.
     * Récupère les consultations du jour pour un patient.
     *
     * @param int $idPatient Patient ID | ID du patient
     * @return array<int, array{id: int, title: string, type: string, doctor: string, time: string}>
     */
    public function getTodayConsultations(int $idPatient): array
    {
        $sql = "SELECT id_consultations, title, type, last_name, date 
                FROM view_consultations 
                WHERE id_patient = :id 
                  AND DATE(date) = CURDATE() 
                  AND date >= NOW()
                ORDER BY date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $idPatient]);

        $results = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $timestamp = strtotime((string) $row['date']);
            $results[] = [
                'id' => (int) $row['id_consultations'],
                'title' => (string) $row['title'],
                'type' => (string) $row['type'],
                'doctor' => (string) $row['last_name'],
                'time' => $timestamp !== false ? date('H:i', $timestamp) : '00:00'
            ];
        }
        return $results;
    }

    /**
     * Retrieves a consultation by its ID.
     * Récupère une consultation par son ID.
     *
     * @param int $idConsultation Consultation ID | ID de la consultation
     * @return Consultation|null Consultation object or null | Objet Consultation ou null
     */
    public function getConsultationById(int $idConsultation): ?Consultation
    {
        try {
            $sql = "SELECT * FROM view_consultations WHERE id_consultations = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $idConsultation]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (is_array($row)) {
                $rawId = $row['id_consultations'] ?? 0;
                $rawIdUser = $row['id_user'] ?? 0;
                $rawLastName = $row['last_name'] ?? '';
                $rawDate = $row['date'] ?? '';
                $rawTitle = $row['title'] ?? '';
                $rawType = $row['type'] ?? '';
                $rawNote = $row['note'] ?? '';
                return new Consultation(
                    is_numeric($rawId) ? (int) $rawId : 0,
                    is_numeric($rawIdUser) ? (int) $rawIdUser : 0,
                    is_string($rawLastName) ? $rawLastName : '',
                    is_string($rawDate) ? $rawDate : '',
                    is_string($rawTitle) ? $rawTitle : '',
                    is_string($rawType) ? $rawType : '',
                    is_string($rawNote) ? $rawNote : '',
                    'Aucun'
                );
            }
            return null;
        } catch (\PDOException $e) {
            error_log("Error ConsultationModel::getConsultationById : " . $e->getMessage());
            return null;
        }
    }
}
