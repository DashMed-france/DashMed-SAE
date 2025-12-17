<?php

namespace modules\controllers\pages;

use DateTime;
use modules\views\pages\dashboardView;
use modules\models\ConsultationModel;

require_once __DIR__ . '/../../../assets/includes/database.php';
require_once __DIR__ . '/../../models/ConsultationModel.php';

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

        $pdo = \Database::getInstance();
        $model = new ConsultationModel($pdo);
        // TODO: Patient ID dynamics
        $toutesConsultations = $model->getConsultationsByPatientId(1);


        $dateAujourdhui = new DateTime();
        $consultationsPassees = [];
        $consultationsFutures = [];

        foreach ($toutesConsultations as $consultation) {
            try {
                $dateConsultation = new \DateTime($consultation->getDate());
            } catch (\Exception $e) {
                continue;
            }

            if ($dateConsultation < $dateAujourdhui) {
                $consultationsPassees[] = $consultation;
            } else {
                $consultationsFutures[] = $consultation;
            }
        }

        $view = new dashboardView($consultationsPassees, $consultationsFutures);
        $view->show();
    }

    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
