<?php

namespace modules\controllers\pages;

use DateTime;
use modules\views\pages\dashboardView;
use modules\services\ConsultationService;
use PDO;

/**
 * Contrôleur du tableau de bord.
 */
class DashboardController
{

    private \PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getInstance();
    }
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

        // Contient dans le cookie l'ID de la chambre
        if (isset($_GET['room']) && ctype_digit($_GET['room'])) {
            setcookie('room_id', $_GET['room'], time() + 60*60*24*30, '/');
            $_COOKIE['room_id'] = $_GET['room'];
        }


        $toutesConsultations = ConsultationService::getAllConsultations();

        $dateAujourdhui = new DateTime();
        $consultationsPassees = [];
        $consultationsFutures = [];

        foreach ($toutesConsultations as $consultation) {
            $dateConsultation = \DateTime::createFromFormat('Y-m-d', $consultation->getDate());

            if ($dateConsultation < $dateAujourdhui) {
                $consultationsPassees[] = $consultation;
            } else {
                $consultationsFutures[] = $consultation;
            }
        }

        $rooms = $this->getRooms();

        $view = new dashboardView($consultationsPassees, $consultationsFutures, $rooms);
        $view->show();
    }

    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Returns all rooms.
     *
     * @return array of rooms.
     */
    public function getRooms(): array
    {
        $st = $this->pdo->query("
            SELECT
                room_id AS room_id
            FROM patients
            ORDER BY room_id
        ");
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
