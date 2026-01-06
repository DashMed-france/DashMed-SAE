<?php

namespace modules\services;

use modules\models\PatientModel;

/**
 * Service gérant le contexte de navigation (Sélection de chambre / Patient actif).
 * Centralise la logique de lecture/écriture des cookies et la résolution de l'ID patient.
 */
class PatientContextService
{
    private PatientModel $patientModel;

    public function __construct(PatientModel $patientModel)
    {
        $this->patientModel = $patientModel;
    }

    /**
     * Gère la mise à jour du contexte basée sur la requête (GET).
     * Doit être appelé au début des contrôleurs nécessitant un contexte.
     */
    public function handleRequest(): void
    {
        if (isset($_GET['room']) && ctype_digit($_GET['room'])) {
            $roomId = (int) $_GET['room'];
            setcookie('room_id', (string) $roomId, time() + 60 * 60 * 24 * 30, '/');
            $_COOKIE['room_id'] = (string) $roomId;
        }
    }

    /**
     * Récupère l'ID de la chambre active.
     */
    public function getCurrentRoomId(): ?int
    {
        return isset($_COOKIE['room_id']) ? (int) $_COOKIE['room_id'] : null;
    }

    /**
     * Récupère l'ID du patient actif en fonction du contexte (Chambre ou paramètre direct).
     *
     * @return int ID du patient (ou 1 par défaut si non trouvé)
     */
    public function getCurrentPatientId(): int
    {
        if (isset($_REQUEST['id_patient']) && ctype_digit($_REQUEST['id_patient'])) {
            return (int) $_REQUEST['id_patient'];
        }

        $roomId = $this->getCurrentRoomId();
        if ($roomId) {
            $patientId = $this->patientModel->getPatientIdByRoom($roomId);
            if ($patientId) {
                return $patientId;
            }
        }

        return 1;
    }
}
