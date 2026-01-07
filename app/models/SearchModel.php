<?php

namespace modules\models;

use PDO;
use PDOException;

/**
 * Modèle de Recherche Globale.
 *
 * Ce modèle centralise la logique de recherche à travers les différentes entités
 * de l'application (Patients, Médecins, Consultations). Il gère les jointures
 * complexes et le filtrage contextuel (par exemple, limiter la recherche au patient actif).
 *
 * @package modules\models
 */
class SearchModel
{
    /**
     * Instance de connexion à la base de données.
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Constructeur du modèle de recherche.
     *
     * @param PDO $pdo Instance PDO injectée.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Exécute une recherche globale multi-critères.
     *
     * Recherche simultanément dans les tables patients, médecins et consultations.
     * Applique un filtrage contextuel si un ID patient est fourni.
     *
     * @param string   $query     Le terme recherché (minimum 2 caractères).
     * @param int      $limit     Nombre maximum de résultats par catégorie.
     * @param int|null $patientId ID du patient pour le filtrage contextuel (optionnel).
     *
     * @return array Tableau associatif contenant les clés 'patients', 'doctors', 'consultations'.
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
            // --- 1. Recherche des Patients ---
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

            // --- 2. Recherche des Médecins ---
            // Le filtrage s'adapte au contexte :
            // - Si $patientId présent : uniquement les médecins de l'équipe de ce patient.
            // - Sinon : recherche globale parmi tous les praticiens.
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

            // --- 3. Recherche des Consultations ---
            // Recherche uniquement par titre (nom de la consultation).
            // Les jointures LEFT JOIN garantissent le retour des résultats même si des données liées sont manquantes.
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
            // En production, préférer un système de log centralisé
            error_log("[SearchModel] Erreur SQL : " . $e->getMessage());
        }

        return $results;
    }
}
