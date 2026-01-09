<?php

namespace modules\controllers\pages\static;

use modules\views\pages\static\AboutView;

/**
 * Class AboutController | Contrôleur de la page À Propos
 *
 * Manages the "About" page.
 * Contrôleur de la page à propos.
 *
 * @package DashMed\Modules\Controllers\Pages\Static
 * @author DashMed Team
 * @license Proprietary
 */
class AboutController
{
    /**
     * Handles GET request.
     * Affiche la vue de la page à propos ou redirige vers le tableau de bord si l'utilisateur est connecté.
     *
     * @return void
     */
    public function get(): void
    {
        if ($this->isUserLoggedIn()) {
            header('Location: /?page=dashboard');
            exit;
        }
        $view = new AboutView();
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
