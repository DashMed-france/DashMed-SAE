<?php

namespace modules\controllers\pages\static;

use modules\views\pages\static\AboutView;

/**
 * Class AboutController
 *
 * Manages the "About" page.
 *
 * @package DashMed\Modules\Controllers\Pages\Static
 * @author DashMed Team
 * @license Proprietary
 */
class AboutController
{
    /**
     * Handles GET request.
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
}
