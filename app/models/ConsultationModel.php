<?php

namespace modules\models;

use PDO;

require_once __DIR__ . '/Consultation.php';

class ConsultationModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère les consultations pour un patient donné via la vue SQL.
     *
     * @param int $idPatient
     * @return Consultation[] Returns an array of Consultation objects.
     */
    public function getConsultationsByPatientId(int $idPatient): array
    {
        $sql = "SELECT * FROM view_consultations WHERE id_patient = :id_patient ORDER BY date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_patient' => $idPatient]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $consultations = [];

        foreach ($results as $row) {
            // Mapping DB columns to DTO
            // view_consultations has: id_consultations, id_patient, id_user, last_name, date, title, type, note
            // Consultation DTO expects: $Doctor, $Date, $EvenementType, $note, $Document

            // We need to update the DTO to store 'title' and 'id' as well, but for now we map what we can.
            // We map:
            // Doctor <- last_name
            // Date <- date
            // EvenementType <- type (or title? The UI shows "Consultation - [Type]" usually, but let's check view)
            // note <- note
            // Document <- null (not in DB view)

            // To support 'title' which is in DB, we should probably modify the DTO. 
            // For now, let's map 'type' to EvenementType.

            $consultations[] = new Consultation(
                $row['id_consultations'],
                $row['last_name'], // Doctor
                $row['date'],
                $row['title'],
                $row['type'],
                $row['note'],
                'Aucun' // Document
            );
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
