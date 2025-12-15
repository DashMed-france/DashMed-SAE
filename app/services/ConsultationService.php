<?php

namespace modules\services;

use modules\models\consultation;

/**
 * Service centralisé pour gérer les consultations.
 */
class ConsultationService
{
    /**
     * Retourne toutes les consultations du patient.
     *
     * @return array
     */
    public static function getAllConsultations(): array
    {
        $consultations = [];

        $consultations[] = new consultation(
            "Dr. Dupont",
            "15/10/2025",
            "Contrôle post-opératoire",
            "Le patient présente un état de santé général (ESG) excellent pour son profil. 
            Les fonctions vitales (pouls, tension artérielle, saturation en oxygène) se maintiennent 
            dans les limites physiologiques normales et stables sans support pharmacologique. 
            Aucune plainte majeure rapportée. L'état d'hydratation et de nutrition est jugé satisfaisant.",
            "aucun"
        );

        $consultations[] = new consultation(
            "Dr. Martin",
            "20/09/2025",
            "Consultation initiale",
            "Présence d'une lésion de type III-B/C (classification de Gustilo) : perte de substance cutanée 
            et musculaire étendue, fractures comminutives tibiales et fibulaires, et lésion vasculo-nerveuse majeure. 
            L'exploration per-examen confirme une ischémie chaude du pied et de la partie distale de la jambe, 
            malgré les mesures de revascularisation initiales. Signes de nécrose et d'infection débutante (rougeur, 
            chaleur, crépitations possibles).",
            "rapport"
        );

        $consultations[] = new consultation(
            "Dr. Leroy",
            "01/11/2025",
            "Suivi psychologique",
            "L'évaluation psychologique révèle un moral en constante amélioration. Le patient exprime 
            une bonne acceptation de l'appareillage et une intégration positive de son schéma corporel modifié. 
            Il présente une forte motivation pour la reprise d'activités sociales et professionnelles. 
            Les scores sur les échelles d'anxiété et de dépression (ex : HADS) sont en baisse.",
            "aucun"
        );

        $consultations[] = new consultation(
            "Dr. Leclerc",
            "15/11/2025",
            "Ergothérapie",
            "Évaluation des **activités de la vie quotidienne (AVQ)**. Le patient montre une autonomie 
            accrue pour l'habillage matinal. Travail spécifique sur la manipulation d'objets fins 
            (écriture, utilisation d'ustensiles). Recommandation d'aménagements mineurs 
            pour la cuisine afin de faciliter la préparation des repas.",
            "aucun"
        );

        $consultations[] = new consultation(
            "Dr. Salles",
            "10/12/2025",
            "Diététique",
            "Première consultation pour évaluer les habitudes alimentaires. Le patient cherche à maintenir 
            un poids stable pour optimiser l'utilisation de la prothèse. Conseils sur l'apport en protéines 
            et en fibres. Planification d'un suivi pour équilibrer les apports énergétiques.",
            "aucun"
        );

        $consultations[] = new consultation(
            "Dr. Leroy",
            "01/02/2026",
            "Bilan psychologique annuel",
            "Bilan d'évolution positive. Le patient est pleinement réintégré socialement et professionnellement. 
            Aucun signe de détresse psychologique. Discussion sur le rôle des loisirs dans le bien-être. 
            Clôture formelle du dossier de suivi psychologique régulier.",
            "aucun"
        );

        $consultations[] = new consultation(
            "Dr. Dubois (Réa)",
            "25/11/2025",
            "Consultation de suivi post-réanimation",
            "Examen clinique complet. Absence de séquelles neurologiques. Persistance d'une faiblesse musculaire périphérique (score MRC à 45/60). 
            Discussion des difficultés de mémoire et d'anxiété liées au séjour. Prescription d'une rééducation fonctionnelle et orientation vers un psychologue. 
            Prochain suivi dans 3 mois.",
            "Scanner thoracique de contrôle à J+90"
        );

        return $consultations;
    }
}