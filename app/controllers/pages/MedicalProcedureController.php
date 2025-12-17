<?php

namespace modules\controllers\pages;

require_once __DIR__ . '/../../views/pages/medicalprocedureView.php';
require_once __DIR__ . '/../../models/ConsultationModel.php';
require_once __DIR__ . '/../../../assets/includes/database.php';

use modules\views\pages\medicalprocedureView;
use modules\models\ConsultationModel;

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

        // Récupérer toutes les consultations via le Modèle
        $pdo = \Database::getInstance();
        $model = new \modules\models\ConsultationModel($pdo);
        // TODO: Récupérer dynamiquement l'ID patient
        $consultations = $model->getConsultationsByPatientId(1);

        // Trier par date décroissante (plus récente -> plus ancienne)
        usort($consultations, function ($a, $b) {
            $dateA = \DateTime::createFromFormat('Y-m-d', $a->getDate());
            $dateB = \DateTime::createFromFormat('Y-m-d', $b->getDate());

            if (!$dateA)
                return 1;
            if (!$dateB)
                return -1;

            return $dateB <=> $dateA;
        });

        // Passer la liste triée à la vue
        $view = new medicalprocedureView($consultations);
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
}
