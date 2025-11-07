<?php

namespace modules\controllers\pages;

require_once __DIR__ . '/../../views/pages/medicalprocedureView.php';
require_once __DIR__ . '/../../services/ConsultationService.php';

use modules\views\pages\medicalprocedureView;
use modules\services\ConsultationService;

/**
 * Contrôleur de la page actes médicaux du patient.
 */
class MedicalProcedureController
{
    /**
     * Affiche la vue des actes médicaux du patient si l'utilisateur est connecté.
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit;
        }

        $consultations = ConsultationService::getAllConsultations();

        $view = new medicalprocedureView($consultations);
        $view->show();
    }

    /**
     * Vérifie si l'utilisateur est connecté.
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
