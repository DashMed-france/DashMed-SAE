<?php

namespace modules\controllers\static;

use modules\views\static\HomepageView;

/**
 * Class HomepageController
 *
 * Manages the Homepage.
 *
 * @package DashMed\Modules\Controllers\Pages\Static
 * @author DashMed Team
 * @license Proprietary
 */
class HomepageController
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
        $view = new HomepageView();
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
