<?php

namespace modules\controllers;

use modules\views\sitemapView;

/**
 * Contrôleur de la page du plan du site.
 */

class SitemapController
{
    /**
     * Affiche la vue de la page du plan du site ou redirige vers le tableau de bord si l'utilisateur est connecté.
     *
     * @return void
     */
    public function get(): void
    {
        if ($this->isUserLoggedIn()) {
            header('Location: /?page=dashboard');
            exit;
        }
        $view = new sitemapView();
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