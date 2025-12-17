<?php
namespace modules\controllers\pages;

use Database;
use DateTime;
use modules\views\pages\monitoringView;
use modules\models\consultation;
use modules\models\monitorModel;

require_once __DIR__ . '/../../../assets/includes/database.php';

class MonitoringController
{
    private monitorModel $model;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE)
            session_start();
        $this->model = new monitorModel(Database::getInstance(), 'patient_data');
    }

    public function post(): void
    {
        $this->get();
    }

    public function get(): void
    {
        if (!$this->isUserLoggedIn()) {
            header('Location: /?page=login');
            exit();
        }

        $roomId = isset($_GET['room']) ? (int) $_GET['room'] : (isset($_COOKIE['room_id']) ? (int) $_COOKIE['room_id'] : null);

        $patientModel = new \modules\models\PatientModel(Database::getInstance());
        $idPatient = null;

        if ($roomId) {
            $idPatient = $patientModel->getPatientIdByRoom($roomId);
        }


        if (!$idPatient) {
            header('Location: /?page=dashboard');
            exit();
        }

        $toutesConsultations = $this->getConsultations();
        $dateAujourdhui = new DateTime();
        $consultationsPassees = [];
        $consultationsFutures = [];
        foreach ($toutesConsultations as $consultation) {
            $dateConsultation = DateTime::createFromFormat('d/m/Y', $consultation->getDate());
            if ($dateConsultation < $dateAujourdhui)
                $consultationsPassees[] = $consultation;
            else
                $consultationsFutures[] = $consultation;
        }

        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        $metrics = $this->model->getLatestMetricsForPatient($idPatient, $userId);

        $rawHistory = $this->model->getRawHistoryForPatient($idPatient);
        $historyByParam = [];
        foreach ($rawHistory as $r) {
            $pid = (string) $r['parameter_id'];
            if (!isset($historyByParam[$pid]))
                $historyByParam[$pid] = [];
            $historyByParam[$pid][] = [
                'timestamp' => $r['timestamp'],
                'value' => $r['value'],
                'alert_flag' => (int) $r['alert_flag'],
            ];
        }
        $MAX_PER_PARAM = 20;
        foreach ($historyByParam as $pid => $list) {
            $historyByParam[$pid] = array_slice($list, 0, $MAX_PER_PARAM);
        }

        foreach ($metrics as &$m) {
            $pid = (string) ($m['parameter_id'] ?? '');
            $m['history'] = $historyByParam[$pid] ?? [];
        }
        unset($m);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chart_pref_submit'])) {
            $pId = $_POST['parameter_id'] ?? '';
            $cType = $_POST['chart_type'] ?? '';

            if ($pId && $cType && $userId) {
                $this->model->saveUserChartPreference($userId, $pId, $cType);
                $currentUrl = $_SERVER['REQUEST_URI'];
                header('Location: ' . $currentUrl);
                exit();
            }
        }

        foreach ($metrics as &$val) {
            $str = $val['allowed_charts_str'] ?? '';
            $val['chart_allowed'] = $str ? explode(',', $str) : ['line'];
        }
        unset($val);

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
            new consultation('Dr. Dupont', '08/10/2025', 'Radio du genou', 'Résultats normaux', 'doc123.pdf'),
            new consultation('Dr. Martin', '15/10/2025', 'Consultation de suivi', 'Patient en bonne voie de guérison', 'doc124.pdf'),
            new consultation('Dr. Leblanc', '22/10/2025', 'Examen sanguin', 'Valeurs normales', 'doc125.pdf'),
            new consultation('Dr. Durant', '10/11/2025', 'Contrôle post-opératoire', 'Cicatrisation à vérifier', 'doc126.pdf'),
            new consultation('Dr. Bernard', '20/11/2025', 'Radiographie thoracique', 'Contrôle de routine', 'doc127.pdf'),
            new consultation('Dr. Petit', '05/12/2025', 'Bilan sanguin complet', 'Analyse annuelle', 'doc128.pdf'),
        ];
    }
}