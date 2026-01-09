<?php

namespace modules\views\auth;

/**
 * Class LoginView | Vue de Connexion
 *
 * Displays the login form allowing users to authenticate.
 * Affiche le formulaire de connexion permettant aux utilisateurs de s'authentifier.
 *
 * Includes CSRF protection, email/password fields, and navigation links.
 * Inclut la protection CSRF, les champs email/mot de passe et des liens de navigation.
 *
 * @package DashMed\Modules\Views\Auth
 * @author DashMed Team
 * @license Proprietary
 */
class LoginView
{
    /**
     * Renders the login form HTML.
     * Génère l'intégralité du HTML du formulaire de connexion.
     *
     * The form sends a POST request to /?page=login.
     * Le formulaire envoie une requête POST vers /?page=login.
     *
     * @param array $users Optional list of users for auto-fill demo | Liste optionnelle d'utilisateurs pour la démo.
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
                            <?php foreach ($users as $u) : ?>
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
