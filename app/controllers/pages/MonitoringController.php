<?php
namespace modules\controllers\pages;

use modules\views\pages\monitoringView;
use modules\models\consultation;
use modules\models\monitorModel;

require_once __DIR__ . '/../../../assets/includes/database.php';

class MonitoringController
{
    private monitorModel $model;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $this->model = new monitorModel(\Database::getInstance(), 'patient_data');
    }

    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit();
        }

        // TODO: récupère dynamiquement l’ID du patient (route/session).
        $idPatient = 1;

        $toutesConsultations = $this->getConsultations();

        $dateAujourdhui = new \DateTime();
        $consultationsPassees = [];
        $consultationsFutures = [];

        foreach ($toutesConsultations as $consultation) {
            $dateConsultation = \DateTime::createFromFormat('d/m/Y', $consultation->getDate());
            if ($dateConsultation < $dateAujourdhui) $consultationsPassees[] = $consultation;
            else $consultationsFutures[] = $consultation;
        }

        $metrics = $this->model->getLatestMetricsForPatient($idPatient);

        $view = new monitoringView($consultationsPassees, $consultationsFutures, $metrics);
        $view->show();
    }

    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    private function getConsultations(): array
    {
        return [
            new consultation('Dr. Dupont',  '08/10/2025', 'Radio du genou',            'Résultats normaux',                 'doc123.pdf'),
            new consultation('Dr. Martin',  '15/10/2025', 'Consultation de suivi',     'Patient en bonne voie de guérison', 'doc124.pdf'),
            new consultation('Dr. Leblanc', '22/10/2025', 'Examen sanguin',            'Valeurs normales',                  'doc125.pdf'),
            new consultation('Dr. Durant',  '10/11/2025', 'Contrôle post-opératoire',  'Cicatrisation à vérifier',          'doc126.pdf'),
            new consultation('Dr. Bernard', '20/11/2025', 'Radiographie thoracique',   'Contrôle de routine',               'doc127.pdf'),
            new consultation('Dr. Petit',   '05/12/2025', 'Bilan sanguin complet',     'Analyse annuelle',                   'doc128.pdf'),
        ];
    }
}