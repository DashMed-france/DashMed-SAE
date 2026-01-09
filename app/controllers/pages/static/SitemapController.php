<?php

namespace modules\controllers\pages\static;

use modules\views\pages\static\SitemapView;

/**
 * Class SitemapController | Contrôleur Plan du Site
 *
 * Manages the Sitemap page.
 * Contrôleur du plan du site.
 *
 * @package DashMed\Modules\Controllers\Pages\Static
 * @author DashMed Team
 * @license Proprietary
 */
class SitemapController
{
    /** @var SitemapView View instance | Instance de la vue */
    private SitemapView $view;

    /**
     * Constructor.
     * Constructeur.
     *
     * @param SitemapView|null $view
     */
    public function __construct(?SitemapView $view = null)
    {
        $this->view = $view ?? new SitemapView();
    }

    /**
     * Handles GET request.
     * Affiche le plan du site ou redirige si connecté.
     *
     * @return void
     */
    public function get(): void
    {
        if ($this->isUserLoggedIn()) {
            $this->redirect('/?page=dashboard');
            return;
        }
        $this->view->show();
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

    /**
     * Redirects to URL.
     * Redirige vers une URL.
     *
     * @param string $url
     * @return void
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
