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
    private PDO $pdo;

    /**
     * Constructeur du modèle Consultation.
     *
     * @param PDO $pdo Instance de connexion à la base de données.
     */
    public function __construct(PDO $pdo)
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
                // Création d'un objet de transfert de données (DTO) pour chaque ligne
                // Les paramètres sont : ID, Médecin, Date, Titre, Type, Note, Document
                $consultations[] = new Consultation(
                    (int) $row['id_consultations'],
                    $row['last_name'], // Correspond au nom du médecin
                    $row['date'],
                    $row['title'],
                    $row['type'],
                    $row['note'],
                    'Aucun' // Valeur par défaut pour le document (non présent dans la vue actuelle)
                );
            }

        } catch (\PDOException $e) {
            // Enregistrement de l'erreur dans les logs système pour le débogage
            error_log("Erreur ConsultationModel::getConsultationsByPatientId : " . $e->getMessage());

            // On retourne un tableau vide pour ne pas casser l'interface utilisateur
            return [];
        }

        return $consultations;
    }

    /**
     * Récupère les consultations du jour pour un patient.
     *
     * @param int $idPatient
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
}
