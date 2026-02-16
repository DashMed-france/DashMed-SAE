<?php

namespace modules\controllers\static;

use modules\views\static\SitemapView;

/**
 * Class SitemapController
 *
 * Manages the Sitemap page.
 *
 * @package DashMed\Modules\Controllers\Pages\Static
 * @author DashMed Team
 * @license Proprietary
 */
class SitemapController
{
    /** @var SitemapView View instance */
    private SitemapView $view;

    /**
     * Constructor.
     *
     * @param SitemapView|null $view
     */
    public function __construct(?SitemapView $view = null)
    {
        $this->view = $view ?? new SitemapView();
    }

    /**
     * Handles GET request.
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
     *
     * @return void
     */
    public function index(): void
    {
        $this->get();
    }

    /**
     * Checks if user is logged in.
     *
     * @return bool
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Redirects to URL.
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
