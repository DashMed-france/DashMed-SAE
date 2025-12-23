<?php

namespace modules\controllers\pages;

require_once __DIR__ . '/../../views/pages/medicalprocedureView.php';
require_once __DIR__ . '/../../models/ConsultationModel.php';
require_once __DIR__ . '/../../../assets/includes/database.php';

use modules\views\pages\medicalprocedureView;
use modules\models\ConsultationModel;
use modules\services\PatientContextService;
use modules\models\PatientModel;

/**
 * Contrôleur de la page actes médicaux du patient.
 */
class MedicalProcedureController
{
    private \PDO $pdo;
    private \modules\models\ConsultationModel $consultationModel;
    private \modules\services\PatientContextService $contextService;
    private \modules\models\PatientModel $patientModel;

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getInstance();
        $this->consultationModel = new \modules\models\ConsultationModel($this->pdo); // Use class property
        $this->patientModel = new \modules\models\PatientModel($this->pdo);
        $this->contextService = new \modules\services\PatientContextService($this->patientModel);
    }

    /**
     * Affiche la vue des actes médicaux du patient si l'utilisateur est connecté.
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit;
        }

        // Gestion du contexte (Cookies / URL)
        $this->contextService->handleRequest();

        // Récupération de l'ID patient via le contexte
        $patientId = $this->contextService->getCurrentPatientId();

        // Si aucun patient n'est sélectionné, on peut soit rediriger, soit afficher vide
        // Ici, on tente de récupérer les consultations si un ID existe
        $consultations = [];
        if ($patientId) {
            $consultations = $this->consultationModel->getConsultationsByPatientId($patientId);
        }

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
