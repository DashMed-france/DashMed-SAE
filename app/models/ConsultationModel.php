<?php

namespace modules\models;

use PDO;

require_once __DIR__ . '/Consultation.php';

/**
 * Modèle pour la gestion des consultations.
 *
 * Gère l'accès aux données des consultations médicales.
 *
 * @package modules\models
 */
class ConsultationModel
{
    private \PDO $pdo;

    /**
     * Constructeur du modèle Consultation.
     *
     * @param \PDO $pdo Instance de connexion à la base de données.
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère la liste des consultations pour un patient spécifique.
     *
     * @param int $idPatient
     * @return Consultation[]
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
     * Crée une nouvelle consultation.
     *
     * @param int $idPatient ID du patient
     * @param int $idDoctor ID du médecin (utilisateur)
     * @param string $date Date au format YYYY-MM-DD (ou YYYY-MM-DD HH:MM:SS)
     * @param string $type Type de consultation
     * @param string $note Notes ou compte rendu
     * @param string $title Titre de la consultation
     * @return bool True si succès, False sinon
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
     * Met à jour une consultation existante.
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
     * Supprime une consultation.
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
     * Récupère une consultation par son ID.
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
