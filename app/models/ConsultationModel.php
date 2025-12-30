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
     * Cette méthode interroge la vue `view_consultations` pour obtenir l'historique
     * médical complet d'un patient, trié par date décroissante (le plus récent en premier).
     *
     * @param int $idPatient L'identifiant unique du patient.
     * @return Consultation[] Retourne un tableau d'objets Consultation, ou un tableau vide en cas d'erreur.
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
}
