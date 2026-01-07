<?php

namespace modules\services;

use modules\models\PatientModel;

/**
 * Service managing navigation context (Room selection / Active patient).
 *
 * Centralizes the logic for reading/writing cookies and resolving the patient ID
 * based on room selection or direct patient parameters.
 *
 * @package modules\services
 */
class PatientContextService
{
    /**
     * Patient model instance for database operations.
     *
     * @var PatientModel
     */
    private PatientModel $patientModel;

    /**
     * Constructor.
     *
     * @param PatientModel $patientModel Patient model instance.
     */
    public function __construct(PatientModel $patientModel)
    {
        $this->patientModel = $patientModel;
    }

    /**
     * Handles context updates based on the request (GET parameters).
     *
     * Processes the 'room' GET parameter and updates the room_id cookie if valid.
     * Should be called at the beginning of controllers requiring context.
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
     * Retrieves the active room ID from cookies.
     *
     * @return int|null The current room ID, or null if not set.
     */
    public function getCurrentRoomId(): ?int
    {
        return isset($_COOKIE['room_id']) ? (int) $_COOKIE['room_id'] : null;
    }

    /**
     * Retrieves the active patient ID based on context (room or direct parameter).
     *
     * Resolution order:
     * 1. Direct 'id_patient' request parameter (if present and valid)
     * 2. Patient associated with the current room (from cookie)
     * 3. Default fallback to patient ID 1
     *
     * @return int Patient ID (defaults to 1 if not found).
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