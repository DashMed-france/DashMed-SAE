<?php

namespace modules\controllers\auth;

/**
 * Contrôleur de déconnexion utilisateur.
 */
class logoutController
{
    /**
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
