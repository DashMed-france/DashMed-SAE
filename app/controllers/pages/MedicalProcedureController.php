<?php

namespace modules\controllers\pages;

//require_once __DIR__ . '/../../models/Consultation.php';
//require_once __DIR__ . '/../../views/pages/MedicalprocedureView.php';

use modules\views\pages\MedicalprocedureView;
use modules\models\Consultation;

/**
 * Contrôleur de la pages actes patient.
 */
class MedicalProcedureController
{
    /**
     * Affiche la vue des actes médicaux du patient si l'utilisateur est connecté.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit;
        }

        // Créer les consultations
        $consultations = $this->getConsultations();

        // Passer la liste à la vue et afficher
        $view = new MedicalprocedureView($consultations);
        $view->show();
    }

    /**
     * Récupère la liste des consultations.
     *
     * @return array
     */
    private function getConsultations(): array
    {
        $consultations = [];

        $consultations[] = new Consultation(
            "Dr. Dupont",
            "2025-10-15",
            "Contrôle post-opératoire",
            "Le patient présente un état de santé général (ESG) excellent pour son profil. 
            Les fonctions vitales (pouls, tension artérielle, saturation en oxygène) se maintiennent 
            dans les limites physiologiques normales et stables sans support pharmacologique. 
            Aucune plainte majeure rapportée. L'état d'hydratation et de nutrition est jugé satisfaisant.",
            "aucun"
        );

        $consultations[] = new Consultation(
            "Dr. Martin",
            "2025-09-20",
            "Consultation initiale",
            "Présence d'une lésion de type III-B/C (classification de Gustilo) : perte de substance cutanée 
            et musculaire étendue, fractures comminutives tibiales et fibulaires, et lésion vasculo-nerveuse majeure. 
            L'exploration per-examen confirme une ischémie chaude du pied et de la partie distale de la jambe, 
            malgré les mesures de revascularisation initiales. Signes de nécrose et d'infection débutante (rougeur, 
            chaleur, crépitations possibles).",
            "rapport"
        );

        $consultations[] = new Consultation(
            "Dr. Leroy",
            "2025-11-01",
            "Suivi psychologique",
            "L'évaluation psychologique révèle un moral en constante amélioration. Le patient exprime 
            une bonne acceptation de l'appareillage et une intégration positive de son schéma corporel modifié. 
            Il présente une forte motivation pour la reprise d'activités sociales et professionnelles. 
            Les scores sur les échelles d'anxiété et de dépression (ex : HADS) sont en baisse.",
            "aucun"
        );

        return $consultations;
    }

    /**
     * Vérifie si l'utilisateur est connecté.
     *
     * @return bool
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
