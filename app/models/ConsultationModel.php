<?php

namespace modules\models;

use PDO;

require_once __DIR__ . '/Consultation.php';

class ConsultationModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère les consultations pour un patient donné via la vue SQL.
     *
     * @param int $idPatient
     * @return array Returns an array of Consultation objects.
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
}
