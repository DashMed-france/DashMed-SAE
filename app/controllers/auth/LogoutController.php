<?php

namespace modules\controllers\auth;

/**
 * Class LogoutController | Contrôleur de Déconnexion
 *
 * Handles user logout.
 * Gère la déconnexion utilisateur.
 *
 * @package DashMed\Modules\Controllers\Auth
 * @author DashMed Team
 * @license Proprietary
 */
class LogoutController
{
    /**
     * Logs out the user, destroys the session, and redirects to homepage.
     * Déconnecte l'utilisateur, détruit la session et redirige vers la page d'accueil.
     *
     * @return void
     */
    public function get(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
        header('Location: /?page=homepage');
        exit();
    }
}
