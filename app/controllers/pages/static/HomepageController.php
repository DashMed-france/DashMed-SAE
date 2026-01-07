<?php

namespace modules\controllers\pages\static;

use modules\views\pages\static\HomepageView;

/**
 * Controller for the homepage.
 *
 * Displays the main landing page for non-authenticated visitors.
 * Redirects authenticated users to the dashboard.
 *
 * @package modules\controllers\pages\static
 */
class HomepageController
{
    /**
     * Handles GET requests to display the homepage.
     *
     * Redirects authenticated users to the dashboard.
     * Otherwise, displays the homepage view.
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
     * Alias for the get() method.
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
     * @return bool True if user is authenticated, false otherwise.
     */
    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }
}