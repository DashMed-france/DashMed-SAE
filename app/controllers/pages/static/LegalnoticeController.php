<?php

namespace modules\controllers\pages\static;

use modules\views\pages\static\LegalnoticeView;

/**
 * Controller for the legal notice page.
 *
 * Displays legal information and terms. Redirects authenticated users
 * to the dashboard as this page is only accessible to non-authenticated visitors.
 *
 * @package modules\controllers\pages\static
 */
class LegalnoticeController
{
    /**
     * Handles GET requests to display the legal notice page.
     *
     * Redirects authenticated users to the dashboard.
     * Otherwise, displays the legal notice view.
     *
     * @return void
     */
    public function get(): void
    {
        if ($this->isUserLoggedIn()) {
            header('Location: /?page=dashboard');
            exit;
        }
        $view = new LegalnoticeView();
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