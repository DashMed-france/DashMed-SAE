<?php

namespace modules\controllers\pages\static;

use modules\views\pages\static\SitemapView;

/**
 * Controller for handling sitemap page requests.
 *
 * This controller manages the display of the sitemap page and handles
 * user authentication redirects.
 */
class SitemapController
{
    /**
     * The view instance responsible for rendering the sitemap page.
     *
     * @var SitemapView
     */
    private SitemapView $view;

    /**
     * Constructor for SitemapController.
     *
     * Initializes the controller with a SitemapView instance. If no view is provided,
     * a new instance is created automatically.
     *
     * @param SitemapView|null $view Optional view instance for dependency injection
     */
    public function __construct(?SitemapView $view = null)
    {
        $this->view = $view ?? new SitemapView();
    }

    /**
     * Handles GET requests for the sitemap page.
     *
     * If a user is logged in, they are redirected to the dashboard.
     * Otherwise, the sitemap view is displayed.
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
     * Default index action for the sitemap controller.
     *
     * This method serves as an alias to the get() method.
     *
     * @return void
     */
    public function index(): void
    {
        $this->get();
    }

    /**
     * Checks if a user is currently logged in.
     *
     * Determines login status by checking for the presence of an email
     * in the session.
     *
     * @return bool True if user is logged in, false otherwise
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    /**
     * Redirects the user to a specified URL.
     *
     * Sends a Location header and terminates script execution.
     *
     * @param string $url The URL to redirect to
     * @return void
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}