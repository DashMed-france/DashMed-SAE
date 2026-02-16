<?php

namespace modules\controllers\static;

use modules\views\static\LegalnoticeView;

/**
 * Class LegalnoticeController
 *
 * Manages the Legal Notice page.
 *
 * @package DashMed\Modules\Controllers\Pages\Static
 * @author DashMed Team
 * @license Proprietary
 */
class LegalnoticeController
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
        $view = new LegalnoticeView();
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
