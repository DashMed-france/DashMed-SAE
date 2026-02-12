<?php

namespace modules\controllers\static;

use modules\views\static\HomepageView;

/**
 * Class HomepageController | Contrôleur de la Page d'Accueil
 *
 * Manages the Homepage.
 * Contrôleur de la page d'accueil.
 *
 * @package DashMed\Modules\Controllers\Pages\Static
 * @author DashMed Team
 * @license Proprietary
 */
class HomepageController
{
    /**
     * Handles GET request.
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
        $view = new HomepageView();
        $view->show();
    }

    /**
     * Index method (alias for get).
     * Alias de la méthode get().
     *
     * @return void
     */
    public function index(): void
    {
        $this->get();
    }

    /**
     * Checks if user is logged in.
     * Vérifie si l'utilisateur est connecté.
     *
     * @return bool
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}
