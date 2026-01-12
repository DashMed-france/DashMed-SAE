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
            <title>DashMed - Connexion</title>

            <!-- Global Styles & Theme -->
            <link rel="stylesheet" href="assets/css/base/style.css">
            <link id="theme-style" rel="stylesheet" href="/assets/css/themes/light.css">
            <link rel="stylesheet" href="/assets/css/themes/dark.css">
            <link rel="stylesheet" href="assets/css/pages/login.css">

            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <div class="login-wrapper">
                <!-- Left Side: Visual Brand Area -->
                <div class="login-visual">
                    <div class="brand-content">
                        <img src="assets/img/logo.svg" alt="DashMed Logo" class="brand-logo">
                        <div class="brand-title">DashMed</div>
                        <div class="brand-tagline">
                            La solution de gestion médicale<br>nouvelle génération.
                        </div>
                    </div>
                </div>

                <!-- Right Side: Login Form -->
                <div class="login-form-container">
                    <div class="login-header">
                        <h1>Bienvenue</h1>
                        <p>Veuillez vous identifier pour accéder à votre espace.</p>
                    </div>

                    <form action="/?page=login" method="post" novalidate>

                        <!-- Search / User Filter -->
                        <div class="form-group">
                            <label for="search">Rechercher votre compte</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <path
                                        d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                                </svg>
                                <input type="text" id="search" name="search" placeholder="Tapez votre nom...">
                            </div>
                        </div>

                        <!-- Hidden email field populated by JS -->
                        <input type="hidden" id="email" name="email" value="">

                        <!-- User Selection Grid -->
                        <div class="user-selection-area">
                            <label class="user-list-label">Sélection rapide</label>

                            <!-- Selected User Feedback Block -->
                            <div id="selected-user-info" style="display: none;" class="selected-user-feedback">
                                <div class="user-avatar-placeholder">✓</div>
                                <div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">Compte sélectionné</div>
                                    <div id="selected-user-name" style="font-weight: 600;"></div>
                                </div>
                            </div>

                            <div class="user-grid" id="user-list">
                                <?php foreach ($users as $u) : ?>
                                    <div class="user-card-item" data-email="<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>">
                                        <div class="user-avatar-placeholder">
                                            <?= strtoupper(substr($u['first_name'], 0, 1)) ?>
                                        </div>
                                        <div class="user-name-text">
                                            <?= htmlspecialchars($u['last_name'] . ' ' . $u['first_name'], ENT_QUOTES) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <path
                                        d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z" />
                                </svg>
                                <input type="password" id="password" name="password" autocomplete="current-password" required
                                    placeholder="••••••••">
                                <button type="button" class="password-toggle" data-target="password"
                                    aria-label="Afficher le mot de passe">
                                    <img src="assets/img/icons/eye-open.svg" alt="eye" style="width: 20px; height: 20px;">
                                </button>
                            </div>
                        </div>

                        <?php if (!empty($csrf)) : ?>
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>

                        <!-- Actions -->
                        <div class="form-actions-container">
                            <button class="submit-btn" type="submit">Se connecter</button>

                            <div class="secondary-links">
                                <a class="link-mute" href="/?page=homepage">Retour</a>
                                <a class="link-mute" href="/?page=password">Mot de passe oublié ?</a>
                                <a class="link-mute" href="/?page=signup">Créer un compte</a>
                            </div>
                        </div>

                    </form>
                </div>
            </div>

            <script src="assets/js/auth/form.js"></script>
            <script src="assets/js/auth/users.js"></script>
            <script src="assets/js/pages/dash.js"></script>
        </body>

        </html>
        <?php
    }
}
