<?php

namespace modules\services;

use modules\models\repositories\PatientRepository;

/**
 * Class PatientContextService
 *
 * Service managing navigation context (Room selection / Active patient).
 * Centralizes logic for reading/writing cookies and resolving patient ID.
 *
 * @package DashMed\Modules\Services
 * @author DashMed Team
 * @license Proprietary
 */
class PatientContextService
{
    /** @var PatientRepository Patient repository */
    private PatientRepository $patientModel;

    /**
     * Constructor.
     *
     * @param PatientRepository $patientModel
     */
    public function __construct(PatientRepository $patientModel)
    {
        $this->patientModel = $patientModel;
    }

    /**
     * Handles context updates based on the request (GET).
     *
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
        } elseif (!isset($_COOKIE['room_id']) || $_COOKIE['room_id'] === '') {
            $roomId = 1;
            setcookie('room_id', (string) $roomId, time() + 60 * 60 * 24 * 30, '/');
            $_COOKIE['room_id'] = (string) $roomId;
        }
    }

    /**
     * Retrieves the current room ID.
     *
     * @return int|null Room ID or null
     */
    public function getCurrentRoomId(): ?int
    {
        $rawCookie = $_COOKIE['room_id'] ?? null;
        return $rawCookie !== null && is_numeric($rawCookie) ? (int) $rawCookie : null;
    }

    /**
     * Retrieves the current patient ID based on context (Room or direct parameter).
     *
     * @return int Patient ID (defaulting to 1 if not found)
     */
    public function getCurrentPatientId(): int
    {
        $idPatient = $_REQUEST['id_patient'] ?? null;
        if ($idPatient !== null && is_string($idPatient) && ctype_digit($idPatient)) {
            return (int) $idPatient;
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
