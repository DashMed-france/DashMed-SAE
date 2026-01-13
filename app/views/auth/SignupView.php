<?php

declare(strict_types=1);

namespace modules\views\auth;

/**
 * Class SignupView | Vue d'Inscription
 *
 * Displays the registration form.
 * Affiche le formulaire d'inscription.
 *
 * Allows new users (doctors) to create an account.
 * Permet aux nouveaux utilisateurs (médecins) de créer un compte.
 *
 * @package DashMed\Modules\Views\Auth
 * @author DashMed Team
 * @license Proprietary
 */
class SignupView
{
    /**
     * Renders the signup form.
     * Affiche le formulaire d'inscription.
     *
     * @param array<int, array{
     *   id_profession: int|string,
     *   label_profession: string
     * }> $professions List of medical professions | Liste des professions médicales.
     * @return void
     */
    public function show(array $professions = []): void
    {
        $csrf = $_SESSION['_csrf'] ?? '';
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);

        $oldRaw = $_SESSION['old_signup'] ?? [];
        $old = is_array($oldRaw) ? $oldRaw : [];
        unset($_SESSION['old_signup']);

        $h = static function ($v): string {
            return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        };
        ?>
        <!DOCTYPE html>
        <html lang="fr">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="description" content="Inscrivez vous à notre service.">
            <title>DashMed - Créer un compte</title>

            <link rel="stylesheet" href="assets/css/base/style.css">
            <link id="theme-style" rel="stylesheet" href="/assets/css/themes/light.css">
            <link rel="stylesheet" href="/assets/css/themes/dark.css">

            <link rel="stylesheet" href="assets/css/pages/signup.css">

            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <div class="signup-wrapper">
                <div class="signup-visual">
                    <div class="brand-content">
                        <img src="assets/img/logo.svg" alt="DashMed Logo" class="brand-logo">
                        <div class="brand-title">DashMed</div>
                        <div class="brand-tagline">
                            Rejoignez la communauté médicale<br>de demain.
                        </div>
                    </div>
                </div>

                <div class="signup-form-container">
                    <div class="signup-header">
                        <h1>Création de compte</h1>
                        <p>Remplissez le formulaire pour commencer.</p>
                    </div>

                    <?php if (!empty($error)) : ?>
                        <div class="form-errors" role="alert">
                            <svg style="width:20px;height:20px;fill:currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1
                                    15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                            </svg>
                            <span><?= $h($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="?page=signup" method="post" novalidate>

                        <div class="form-group">
                            <label for="last_name">Nom</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <path
                                        d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67
                                        0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                </svg>
                                <input type="text" id="last_name" name="last_name" required
                                    value="<?= $h($old['last_name'] ?? '') ?>" placeholder="Votre nom">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="first_name">Prénom</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <path
                                        d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67
                                        0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                </svg>
                                <input type="text" id="first_name" name="first_name" required
                                    value="<?= $h($old['first_name'] ?? '') ?>" placeholder="Votre prénom">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <path
                                        d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9
                                        2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                                </svg>
                                <input type="email" id="email" name="email" required autocomplete="email"
                                    value="<?= $h($old['email'] ?? '') ?>" placeholder="exemple@medecin.fr">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="id_profession">Spécialité médicale</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <path
                                        d="M20 6h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v12c0 1.1.9
                                        2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zM10 4h4v2h-4V4zm10 16H4V8h16v12z" />
                                </svg>
                                <select id="id_profession" name="id_profession" required>
                                    <option value="">Sélectionnez votre spécialité</option>
                                    <?php
                                    $current = null;
                                    if (isset($old['id_profession'])) {
                                        $rawProf = $old['id_profession'];
                                        $current = is_numeric($rawProf) ? (int) $rawProf : null;
                                    }
                                    foreach ($professions as $s) {
                                        $id = (int) $s['id_profession'];
                                        $name = (string) $s['label_profession'];
                                        $sel = ($current !== null && $current === $id) ? 'selected' : '';
                                        echo '<option value="' . $id . '" ' . $sel . '>' . $h($name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <path
                                        d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2
                                        2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2
                                        .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1
                                        3.1v2z" />
                                </svg>
                                <input type="password" id="password" name="password" required
                                       autocomplete="new-password"
                                    placeholder="••••••••">
                                <button type="button" class="password-toggle" data-target="password"
                                    aria-label="Afficher le mot de passe">
                                    <img src="assets/img/icons/eye-open.svg" alt="eye"
                                         style="width: 20px; height: 20px;">
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm">Confirmer le mot de passe</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <path
                                        d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2
                                        2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2
                                        2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z" />
                                </svg>
                                <input type="password" id="password_confirm" name="password_confirm" required
                                    autocomplete="new-password" placeholder="••••••••">
                                <button type="button" class="password-toggle" data-target="password_confirm"
                                    aria-label="Afficher le mot de passe">
                                    <img src="assets/img/icons/eye-open.svg" alt="eye"
                                         style="width: 20px; height: 20px;">
                                </button>
                            </div>
                        </div>

                        <?php if (!empty($csrf)) : ?>
                            <input type="hidden" name="_csrf" value="<?= $h($csrf) ?>">
                        <?php endif; ?>

                        <div class="form-actions">
                            <button class="submit-btn" type="submit">Créer le compte</button>

                            <div class="secondary-links">
                                <a class="link-mute" href="?page=homepage">Annuler</a>
                                <a class="link-mute" href="/?page=login">J'ai déjà un compte</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <script src="assets/js/auth/form.js"></script>
            <script src="assets/js/pages/dash.js"></script>
        </body>

        </html>
        <?php
    }
}
