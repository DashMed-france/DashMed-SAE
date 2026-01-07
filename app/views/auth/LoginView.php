<?php

/**
 * DashMed — Login View
 *
 * Displays the login form allowing users to authenticate on DashMed.
 * Includes CSRF protection, email/password fields, and links to registration
 * and password recovery.
 *
 * @package   DashMed\Modules\Views
 * @author    DashMed Team
 * @license   Proprietary
 */

namespace modules\views\auth;

/**
 * Displays the login page for the DashMed platform.
 *
 * Responsibilities:
 * - Display the login form with a CSRF token
 * - Provide input fields for email and password
 * - Include form submission and navigation buttons
 * - Load dedicated stylesheets and scripts for form interactivity
 */
class LoginView
{
    /**
     * Generates the complete HTML for the login form.
     *
     * The form sends a POST request to the /?page=login route and includes:
     * - Email and password input fields
     * - A CSRF token for request validation
     * - Navigation links for account creation and password recovery
     *
     * @param array $users List of users for the selection shortcut
     * @return void
     */
    public function show(array $users = []): void
    {
        $csrf = $_SESSION['_csrf'] ?? '';
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="description" content="Connectez-vous à votre espace DashMed.">
            <meta name="keywords" content="connexion, login, dashmed, compte médecin, espace patient, santé en ligne">
            <meta name="author" content="DashMed Team">
            <meta name="robots" content="noindex, nofollow">
            <title>DashMed - Se connecter</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/form.css">
            <link rel="stylesheet" href="assets/css/components/buttons.css">
            <link rel="stylesheet" href="assets/css/components/user-card.css">
            <link id="theme-style" rel="stylesheet" href="/assets/css/themes/light.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>
        <body class="container-form">
        <form action="/?page=login" method="post" novalidate>
            <h1>Se connecter</h1>
            <section>

                <article>
                    <label>Rechercher votre nom</label>
                    <input type="text" id="search" name="search" placeholder="Nom">
                </article>

                <input type="hidden" id="email" name="email" value="">

                <article>
                    <label>Choisissez votre compte :</label>
                    <p id="selected-user-info" style="display: none; color: #3b82f6;
                     font-size: 0.9em; margin-bottom: 0.5rem;">
                        ✓ Utilisateur sélectionné : <span id="selected-user-name"></span>
                    </p>
                    <div class="user-list" id="user-list">
                        <?php foreach ($users as $u) :?>
                            <div class="user-card" data-email="<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>">
                                <span>
                                    <?= htmlspecialchars($u['last_name'] . ' ' . $u['first_name'], ENT_QUOTES) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article>
                    <label for="password">Mot de passe</label>
                    <div class="password">
                        <input type="password" id="password" name="password" autocomplete="current-password" required>
                        <button type="button" class="toggle" data-target="password">
                            <img src="assets/img/icons/eye-open.svg" alt="eye">
                        </button>
                    </div>
                </article>

                <?php if (!empty($csrf)) : ?>
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>

                <section class="buttons">
                    <a class="neg" href="/?page=homepage">Annuler</a>
                    <button class="pos" type="submit">Se connecter</button>
                </section>
                <section class="links">
                    <a href="/?page=signup">Je n'ai pas de compte</a>
                    <a href="/?page=password">Mot de passe oublié</a>
                </section>
            </section>
        </form>

        <script src="assets/js/auth/form.js"></script>
        <script src="assets/js/auth/users.js"></script>
        <script src="assets/js/pages/dash.js"></script>
        </body>
        </html>
        <?php
    }
}