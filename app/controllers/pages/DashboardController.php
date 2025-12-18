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

        // Fetch Patient Data (Defaulting to 1 for now, as per Monitoring context)
        try {
            $pdo = \Database::getInstance();
            $patientModel = new \modules\models\PatientModel($pdo);
        } catch (\Throwable $e) {
            error_log("[DashboardController] Error connecting DB: " . $e->getMessage());
            $patientModel = null;
        }

        $patientId = 1;
        try {
            $patientData = $patientModel ? $patientModel->findById($patientId) : [];
        } catch (\Throwable $e) {
            $patientData = [];
            error_log("[DashboardController] Error fetching patient: " . $e->getMessage());
        }

        $view = new dashboardView($consultationsPassees, $consultationsFutures, $patientData);
        $view->show();
    }

    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
