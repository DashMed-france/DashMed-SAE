<?php

declare(strict_types=1);

namespace modules\models\repositories;

use modules\models\BaseRepository;
use modules\models\entities\Consultation;
use modules\models\entities\ConsultationDocument;
use PDO;

/**
 * Class ConsultationRepository
 *
 * Manages access to medical consultation data.
 *
 * @package DashMed\Modules\Models\Repositories
 * @author DashMed Team
 * @license Proprietary
 */
class ConsultationRepository extends BaseRepository
{
    /**
     * Retrieves the list of consultations for a specific patient.
     *
     * @param int $idPatient Patient ID
     * @return Consultation[] Array of Consultation objects
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
            error_log("Error ConsultationRepository::getConsultationsByPatientId : " . $e->getMessage());
            return [];
        }

        return $consultations;
    }

    /**
     * Creates a new consultation.
     *
     * @param int $idPatient Patient ID
     * @param int $idDoctor Doctor ID (User)
     * @param string $date Date (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
     * @param string $type Consultation Type
     * @param string $note Notes or report
     * @param string $title Consultation Title
     * @return bool True on success, False otherwise
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
            error_log("Error ConsultationRepository::createConsultation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates an existing consultation.
     *
     * @param int $idConsultation Consultation ID
     * @param int $idUser Doctor ID
     * @param string $date Date
     * @param string $type Type
     * @param string $note Notes
     * @param string $title Title
     * @return bool True on success, False otherwise
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
            error_log("Error ConsultationRepository::updateConsultation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a consultation.
     *
     * @param int $idConsultation Consultation ID
     * @return bool True on success, False otherwise
     */
    public function deleteConsultation(int $idConsultation): bool
    {
        try {
            $sql = "DELETE FROM consultations WHERE id_consultations = :id_consultation";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id_consultation' => $idConsultation]);
        } catch (\PDOException $e) {
            error_log("Error ConsultationRepository::deleteConsultation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves today's consultations for a patient.
     *
     * @param int $idPatient Patient ID
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
     *
     * @param int $idConsultation Consultation ID
     * @return Consultation|null Consultation object or null
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
            error_log("Error ConsultationRepository::getConsultationById : " . $e->getMessage());
            return null;
        }
    }

    // =========================================================
    // Document (PDF attachment) methods
    // =========================================================

    /**
     * Returns all documents attached to a consultation.
     *
     * @param int $idConsultation Consultation ID
     * @return ConsultationDocument[]
     */
    public function getDocumentsByConsultationId(int $idConsultation): array
    {
        try {
            $sql = "SELECT * FROM consultation_documents
                    WHERE id_consultation = :id
                    ORDER BY created_at ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $idConsultation]);

            $documents = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (!is_array($row)) continue;
                $documents[] = new ConsultationDocument(
                    (int) ($row['id_document']     ?? 0),
                    (int) ($row['id_consultation'] ?? 0),
                    (string) ($row['filename']       ?? ''),
                    (string) ($row['stored_filename']?? ''),
                    (string) ($row['mime_type']      ?? 'application/pdf'),
                    (int) ($row['file_size']       ?? 0),
                    (int) ($row['uploaded_by']     ?? 0),
                    (string) ($row['created_at']    ?? '')
                );
            }
            return $documents;
        } catch (\PDOException $e) {
            error_log('Error ConsultationRepository::getDocumentsByConsultationId: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Records a new document in the database.
     *
     * @param int    $idConsultation  Consultation ID
     * @param string $filename        Display filename (original)
     * @param string $storedFilename  Filename on disk (UUID.pdf)
     * @param int    $fileSize        Size in bytes
     * @param int    $uploadedBy      User ID of the uploader
     * @return bool
     */
    public function addDocument(
        int    $idConsultation,
        string $filename,
        string $storedFilename,
        int    $fileSize,
        int    $uploadedBy
    ): bool {
        try {
            $sql = "INSERT INTO consultation_documents
                        (id_consultation, filename, stored_filename, mime_type, file_size, uploaded_by)
                    VALUES
                        (:id_consultation, :filename, :stored_filename, 'application/pdf', :file_size, :uploaded_by)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id_consultation'  => $idConsultation,
                ':filename'         => $filename,
                ':stored_filename'  => $storedFilename,
                ':file_size'        => $fileSize,
                ':uploaded_by'      => $uploadedBy,
            ]);
        } catch (\PDOException $e) {
            error_log('Error ConsultationRepository::addDocument: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a document record and returns the entity so the caller can
     * remove the physical file from disk.
     *
     * @param int $idDocument Document ID
     * @return ConsultationDocument|null The deleted document, or null if not found
     */
    public function deleteDocument(int $idDocument): ?ConsultationDocument
    {
        try {
            // Fetch first so we can return the stored filename
            $sql  = "SELECT * FROM consultation_documents WHERE id_document = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $idDocument]);
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!is_array($row)) {
                return null;
            }

            $doc = new ConsultationDocument(
                (int) ($row['id_document']     ?? 0),
                (int) ($row['id_consultation'] ?? 0),
                (string) ($row['filename']       ?? ''),
                (string) ($row['stored_filename']?? ''),
                (string) ($row['mime_type']      ?? 'application/pdf'),
                (int) ($row['file_size']       ?? 0),
                (int) ($row['uploaded_by']     ?? 0),
                (string) ($row['created_at']    ?? '')
            );

            $del  = $this->pdo->prepare("DELETE FROM consultation_documents WHERE id_document = :id");
            $del->execute([':id' => $idDocument]);

            return $doc;
        } catch (\PDOException $e) {
            error_log('Error ConsultationRepository::deleteDocument: ' . $e->getMessage());
            return null;
        }
    }
}
