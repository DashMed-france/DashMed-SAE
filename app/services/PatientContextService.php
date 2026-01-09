<?php

namespace modules\services;

use modules\models\PatientModel;

/**
 * Class PatientContextService | Service de Contexte Patient
 *
 * Service managing navigation context (Room selection / Active patient).
 * Service gérant le contexte de navigation (Sélection de chambre / Patient actif).
 *
 * Centralizes logic for reading/writing cookies and resolving patient ID.
 * Centralise la logique de lecture/écriture des cookies et la résolution de l'ID patient.
 *
 * @package DashMed\Modules\Services
 * @author DashMed Team
 * @license Proprietary
 */
class PatientContextService
{
    /** @var PatientModel Patient model instance | Instance du modèle patient */
    private PatientModel $patientModel;

    /**
     * Constructor.
     * Constructeur.
     *
     * @param PatientModel $patientModel
     */
    public function __construct(PatientModel $patientModel)
    {
        $this->patientModel = $patientModel;
    }

    /**
     * Handles context updates based on the request (GET).
     * Gère la mise à jour du contexte basée sur la requête (GET).
     *
     * Should be called at the beginning of controllers requiring context.
     * Doit être appelé au début des contrôleurs nécessitant un contexte.
     *
     * @return void
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
     * Retrieves the current room ID.
     * Récupère l'ID de la chambre active.
     *
     * @return int|null Room ID or null | ID de la chambre ou null.
     */
    public function getCurrentRoomId(): ?int
    {
        return isset($_COOKIE['room_id']) ? (int) $_COOKIE['room_id'] : null;
    }

    /**
     * Retrieves the current patient ID based on context (Room or direct parameter).
     * Récupère l'ID du patient actif en fonction du contexte (Chambre ou paramètre direct).
     *
     * @return int Patient ID (defaulting to 1 if not found) | ID du patient (1 par défaut si non trouvé).
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
