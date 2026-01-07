<?php

namespace modules\controllers\pages\static;

use modules\views\pages\static\AboutView;

/**
 * Controller for the about page.
 *
 * Displays information about the application or organization.
 * Redirects authenticated users to the dashboard.
 *
 * @package modules\controllers\pages\static
 */
class AboutController
{
    /**
     * Handles GET requests to display the about page.
     *
     * Redirects authenticated users to the dashboard.
     * Otherwise, displays the about view.
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