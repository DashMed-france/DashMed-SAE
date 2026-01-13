<?php

namespace modules\models;

use PDO;
use PDOException;

/**
 * Class SearchModel | Classe SearchModel
 *
 * Global Search Model.
 * Modèle de Recherche Globale.
 *
 * Centralizes search logic across application entities (Patients, Doctors, Consultations).
 * Centralise la logique de recherche à travers les entités de l'application (Patients, Médecins, Consultations).
 *
 * @package DashMed\Modules\Models
 * @author DashMed Team
 * @license Proprietary
 */
class SearchModel
{
    /** @var PDO Database connection instance | Instance de connexion à la base de données */
    private PDO $pdo;

    /**
     * Constructor | Constructeur
     *
     * @param PDO $pdo PDO Instance | Instance PDO
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Executes a global multi-criteria search.
     * Exécute une recherche globale multi-critères.
     *
     * Searches concurrently in patients, doctors, and consultations tables.
     * Recherche simultanément dans les tables patients, médecins et consultations.
     *
     * @param string $query Search term (min 2 chars) | Terme recherché (min 2 caractères)
     * @param int $limit Max results per category | Nombre maximum de résultats par catégorie
     * @param int|null $patientId Optional context patient ID | ID patient contextuel optionnel
     *
     * @return array{patients: array<int, array<string, mixed>>, doctors: array<int, array<string, mixed>>, consultations: array<int, array<string, mixed>>}|array<never, never> Associative array keys: 'patients', 'doctors', 'consultations' | Tableau associatif clés : 'patients', 'doctors', 'consultations'
     */
    public function searchGlobal(string $query, int $limit = 5, ?int $patientId = null): array
    {
        if (mb_strlen($query) < 2) {
            return [];
        }

        $term = '%' . mb_strtolower($query) . '%';
        $results = [
            'patients' => [],
            'doctors' => [],
            'consultations' => []
        ];

        try {
            // --- 1. Patients Search | Recherche des Patients ---
            $sqlPatients = "SELECT id_patient, first_name, last_name, birth_date 
                            FROM patients 
                            WHERE LOWER(first_name) LIKE :q1 OR LOWER(last_name) LIKE :q2 
                            LIMIT :limit";

            $stmt = $this->pdo->prepare($sqlPatients);
            $stmt->bindValue(':q1', $term);
            $stmt->bindValue(':q2', $term);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $results['patients'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // --- 2. Doctors Search | Recherche des Médecins ---
            $sqlDoctors = "SELECT DISTINCT u.id_user, u.first_name, u.last_name, p.label_profession as profession
                           FROM users u
                           LEFT JOIN professions p ON u.id_profession = p.id_profession";

            if ($patientId) {
                $sqlDoctors .= "
                    JOIN consultations c_link ON u.id_user = c_link.id_user 
                    WHERE c_link.id_patient = :pid AND (LOWER(u.first_name) LIKE :q1 OR LOWER(u.last_name) LIKE :q2)";
            } else {
                $sqlDoctors .= " WHERE LOWER(u.first_name) LIKE :q1 OR LOWER(u.last_name) LIKE :q2";
            }

            $sqlDoctors .= " LIMIT :limit";

            $stmt = $this->pdo->prepare($sqlDoctors);
            $stmt->bindValue(':q1', $term);
            $stmt->bindValue(':q2', $term);
            if ($patientId) {
                $stmt->bindValue(':pid', $patientId, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $results['doctors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // --- 3. Consultations Search | Recherche des Consultations ---
            $sqlConsultations = "SELECT c.id_consultations as id_consultation, c.title, c.type, c.date, 
                                        COALESCE(p.id_patient, c.id_patient) as id_patient,
                                        COALESCE(p.first_name, 'Inconnu') as p_first, 
                                        COALESCE(p.last_name, '') as p_last,
                                        COALESCE(u.last_name, 'Inconnu') as doc_name
                                 FROM consultations c
                                 LEFT JOIN patients p ON c.id_patient = p.id_patient
                                 LEFT JOIN users u ON c.id_user = u.id_user
                                 WHERE LOWER(c.title) LIKE :q1";

            if ($patientId) {
                $sqlConsultations .= " AND c.id_patient = :pid";
            }

            $sqlConsultations .= " ORDER BY c.date DESC LIMIT :limit";

            $stmt = $this->pdo->prepare($sqlConsultations);
            $stmt->bindValue(':q1', $term);

            if ($patientId) {
                $stmt->bindValue(':pid', $patientId, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $results['consultations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("[SearchModel] SQL Error: " . $e->getMessage());
        }

        return $results;
    }
}
