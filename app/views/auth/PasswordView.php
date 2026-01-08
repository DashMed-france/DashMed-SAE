<?php

namespace modules\views\auth;

/**
 * Class PasswordView | Vue Mot de Passe
 *
 * Displays password reset interface.
 * Affiche l'interface de réinitialisation de mot de passe.
 *
 * Handles two states: requesting email and entering new password.
 * Gère deux états : demande d'email et saisie du nouveau mot de passe.
 *
 * @package DashMed\Modules\Views\Auth
 * @author DashMed Team
 * @license Proprietary
 */
class PasswordView
{
    /**
     * Renders the password reset page.
     * Affiche le contenu HTML de la page de réinitialisation de mot de passe.
     *
     * @param array|null $msg Flash message (type, text) | Message flash (type, texte).
     * @return void
     */
    public function show(?array $msg = null): void
    {
        $token = $_GET['token'] ?? '';
        $hasToken = (bool) preg_match('/^[a-f0-9]{32}$/', $token);
        ?>
        <!DOCTYPE html>
        <html lang="fr">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="description" content="Page pour la réinitialisation du mot de passe.">
            <meta name="keywords" content="réinitialisation mot de passe,
             dashmed, mot de passe oublié, sécurité compte, santé en ligne">
            <meta name="author" content="DashMed Team">
            <meta name="robots" content="noindex, nofollow">
            <title>DashMed - Réinitialisation mot de passe</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/form.css">
            <link id="theme-style" rel="stylesheet" href="/assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/components/buttons.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body class="container-form">

            <form method="post" action="/?page=password">
                <h1>Réinitialisation de votre mot de passe</h1>

                <?php if ($msg) : ?>
                    <p class="<?= htmlspecialchars($msg['type']) ?>">
                        <?= htmlspecialchars($msg['text']) ?>
                    </p>
                <?php endif; ?>

                <section>
                    <?php if (!$hasToken) : ?>
                        <article>
                            <label for="email">Veuillez entrer votre email</label>
                            <input type="email" id="email" name="email" autocomplete="email" required>
                        </article>
                        <article>
                            <button class="pos" type="submit" name="action" value="send_code">Recevoir le code</button>
                        </article>
                    <?php else : ?>
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">

                        <article>
                            <label for="code">Veuillez entrer le code reçu par e-mail</label>
                            <div id="codeForm">
                                <div class="code-container">
                                    <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="code-digit"
                                        required>
                                    <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="code-digit"
                                        required>
                                    <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="code-digit"
                                        required>
                                    <div class="line"></div>
                                    <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="code-digit"
                                        required>
                                    <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="code-digit"
                                        required>
                                    <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="code-digit"
                                        required>
                                    <input type="hidden" id="code" name="code">
                                </div>
                            </div>
                        </article>

                        <article>
                            <label for="password">Nouveau mot de passe</label>
                            <div class="password">
                                <input type="password" id="password" name="password" minlength="8" required>
                                <button type="button" class="toggle" data-target="password" aria-pressed="false">
                                    <img src="assets/img/icons/eye-open.svg" alt="Afficher le mot de passe">
                                </button>
                            </div>
                        </article>


                        <article class="buttons">
                            <a class="neg" href="/?page=login">Annuler</a>
                            <button class="pos" id="valider" type="submit" name="action" value="reset_password">Valider</button>
                        </article>
                    <?php endif; ?>
                </section>
            </form>

            <script src="assets/js/auth/password.js"></script>
            <script src="assets/js/pages/dash.js"></script>
        </body>

        </html>
        <?php
    }
}
