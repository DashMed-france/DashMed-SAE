<?php

namespace modules\controllers\pages;

use modules\views\pages\dashboardView;
use modules\models\consultation;

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
        if (!$this->isUserLoggedIn())
        {
            header('Location: /?page=login');
            exit();
        }

        // Récupérer les consultations
        $consultations = $this->getConsultations();

        // Passer les consultations à la vue
        $view = new dashboardView($consultations);
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
        // TODO: Remplacer par votre logique de récupération depuis la base de données

        // Exemple avec des données fictives (à remplacer)
        $consultations = [];

        $consultations[] = new consultation(
            'Dr. Dupont',
            '08/10/2025',
            'Radio du genou',
            'Résultats normaux',
            'doc123.pdf'
        );

        $consultations[] = new consultation(
            'Dr. Martin',
            '15/10/2025',
            'Consultation de suivi',
            'Patient en bonne voie de guérison',
            'doc124.pdf'
        );

        $consultations[] = new consultation(
            'Dr. Leblanc',
            '22/10/2025',
            'Examen sanguin',
            'Valeurs normales',
            'doc125.pdf'
        );

        return $consultations;

        /*
        // Version avec base de données (exemple)
        $consultationRepository = new ConsultationRepository();
        return $consultationRepository->findByPatientEmail($_SESSION['email']);
        */
    }
}