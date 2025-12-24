<?php

namespace modules\controllers\pages;

use modules\views\pages\DashboardView;
use modules\models\Consultation;

/**
 * Contrôleur du tableau de bord.
 */
class DashboardController
{
    /**
     * Affiche la vue du tableau de bord si l'utilisateur est connecté.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit();
        }

        $toutesConsultations = $this->getConsultations();

        $dateAujourdhui = new \DateTime();
        $consultationsPassees = [];
        $consultationsFutures = [];

        foreach ($toutesConsultations as $consultation) {
            $dateConsultation = \DateTime::createFromFormat('d/m/Y', $consultation->getDate());

            if ($dateConsultation < $dateAujourdhui) {
                $consultationsPassees[] = $consultation;
            } else {
                $consultationsFutures[] = $consultation;
            }
        }

        $view = new DashboardView($consultationsPassees, $consultationsFutures);
        $view->show();
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

    /**
     * Récupère les consultations du patient.
     *
     * @return array
     */
    private function getConsultations(): array
    {

        $consultations = [];

        $consultations[] = new Consultation(
            'Dr. Dupont',
            '08/10/2025',
            'Radio du genou',
            'Résultats normaux',
            'doc123.pdf'
        );

        $consultations[] = new Consultation(
            'Dr. Martin',
            '15/10/2025',
            'Consultation de suivi',
            'Patient en bonne voie de guérison',
            'doc124.pdf'
        );

        $consultations[] = new Consultation(
            'Dr. Leblanc',
            '22/10/2025',
            'Examen sanguin',
            'Valeurs normales',
            'doc125.pdf'
        );

        // Consultations futures
        $consultations[] = new Consultation(
            'Dr. Durant',
            '10/11/2025',
            'Contrôle post-opératoire',
            'Cicatrisation à vérifier',
            'doc126.pdf'
        );

        $consultations[] = new Consultation(
            'Dr. Bernard',
            '20/11/2025',
            'Radiographie thoracique',
            'Contrôle de routine',
            'doc127.pdf'
        );

        $consultations[] = new Consultation(
            'Dr. Petit',
            '05/12/2025',
            'Bilan sanguin complet',
            'Analyse annuelle',
            'doc128.pdf'
        );

        return $consultations;
    }
}
