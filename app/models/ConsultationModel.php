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
            $consultations[] = new Consultation(
                $row['id_consultations'],
                $row['last_name'],
                $row['date'],
                $row['title'],
                $row['type'],
                $row['note'],
                'Aucun'
            );
        }

        return $consultations;
    }
}
