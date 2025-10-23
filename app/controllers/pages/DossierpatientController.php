<?php

namespace modules\controllers\pages;

use modules\views\pages\dossierpatientView;

/**
 * Contrôleur du tableau de bord.
 */
class DossierpatientController
{
    /**
     * Affiche la vue du tableau de bord si l'utilisateur est connecté.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn())
        {
            header('Location: /?page=login');
        }
        $view = new dossierpatientView();
        $view->show();
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
