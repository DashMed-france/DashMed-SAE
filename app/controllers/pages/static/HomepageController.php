<?php

namespace modules\controllers\pages\static;

use modules\views\pages\static\homepageView;

/**
 * Contrôleur de la page d'accueil.
 */
class HomepageController
{
    /**
     * Affiche la vue de la page d'accueil ou redirige vers le tableau de bord si l'utilisateur est connecté.
     *
     * @return void
     */
    public function get(): void
    {
        if ($this->isUserLoggedIn()) {
            header('Location: /?page=dashboard');
            exit;
        }
        $view = new homepageView();
        $view->show();
    }

    /**
     * Alias de la méthode get().
     *
     * @return void
     */
    public function index(): void
    {
        $this->get();
    }

    /**
     * Vérifie si l'utilisateur est connecté.
     *
     * @return bool
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
