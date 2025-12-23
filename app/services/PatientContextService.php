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
        // Si une chambre est sélectionnée via l'URL, on met à jour le cookie
        if (isset($_GET['room']) && ctype_digit($_GET['room'])) {
            $roomId = (int) $_GET['room'];
            // Expiration 30 jours
            setcookie('room_id', (string) $roomId, time() + 60 * 60 * 24 * 30, '/');
            // Mise à jour immédiate de la superglobale pour le script courant
            $_COOKIE['room_id'] = (string) $roomId;
        }
    }

    /**
     * Récupère l'ID de la chambre active.
     */
    public function getCurrentRoomId(): ?int
    {
        // Priorité : 1. GET (déjà traité par handleRequest normalement, mais on vérifie Cookie)
        // Mais handleRequest met à jour $_COOKIE, donc on lit $_COOKIE.
        return isset($_COOKIE['room_id']) ? (int) $_COOKIE['room_id'] : null;
    }

    /**
     * Récupère l'ID du patient actif en fonction du contexte (Chambre ou paramètre direct).
     *
     * @return int ID du patient (ou 1 par défaut si non trouvé)
     */
    public function getCurrentPatientId(): int
    {
        // 1. Si un ID patient est explicitement demandé (ex: Dossier Patient spécifique)
        if (isset($_REQUEST['id_patient']) && ctype_digit($_REQUEST['id_patient'])) {
            return (int) $_REQUEST['id_patient'];
        }

        // 2. Sinon, on déduit le patient depuis la chambre active
        $roomId = $this->getCurrentRoomId();
        if ($roomId) {
            $patientId = $this->patientModel->getPatientIdByRoom($roomId);
            if ($patientId) {
                return $patientId;
            }
        }

        // 3. Fallback : Patient par défaut (ex: 1)
        return 1;
    }
}
