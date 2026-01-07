<?php

namespace modules\controllers\auth;

/**
 * Controller for user logout operations.
 *
 * Handles session termination and redirection after logout.
 *
 * @package modules\controllers\auth
 */
class LogoutController
{
    /**
     * Handles GET requests to log out the user.
     *
     * Destroys the current session, clears all session data,
     * and redirects to the homepage.
     *
     * @return void
     */
    public function get(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
        header('Location: /?page=homepage');
        exit();
    }
}