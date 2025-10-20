<?php

namespace modules\controllers\pages;

use modules\models\userModel;
use modules\views\pages\sysadminView;


/**
 * Contrôleur du tableau de bord administrateur.
 */
class SysadminController
{
    /**
     * Logique métier / modèle pour les opérations de connexion et d’inscription.
     *
     * @var userModel
     */
    private userModel $model;

    /**
     * Affiche la vue du tableau de bord administrateur si l'utilisateur est connecté.
     *
     * @return void
     */
    public function get(): void
    {
        if (!$this->isUserLoggedIn())
        {
            header('Location: /?page=login');
        }
        $view = new sysadminView();
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