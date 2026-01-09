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

        // Auto-fill logic
        $codeFromUrl = $_GET['code'] ?? '';
        $codeDigits = array_fill(0, 6, '');
        if (!empty($codeFromUrl) && preg_match('/^\d{6}$/', $codeFromUrl)) {
            $codeDigits = str_split($codeFromUrl);
        }
        ?>
        <!DOCTYPE html>
        <html lang="fr">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>DashMed - Réinitialisation mot de passe</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/form.css">
            <link id="theme-style" rel="stylesheet" href="/assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/components/buttons.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
            <style>
                /* Additional inline styles for specific redesign tweaks */
                .login-header {
                    text-align: center;
                    margin-bottom: 2rem;
                }

                .login-logo {
                    width: 80px;
                    height: 80px;
                    margin-bottom: 1rem;
                }

                .login-title {
                    font-size: 1.5rem;
                    color: var(--primary-color, #0056b3);
                    font-weight: 700;
                    margin: 0;
                }

                .login-subtitle {
                    color: var(--text-secondary, #666);
                    font-size: 0.9rem;
                    margin-top: 0.5rem;
                }

                .security-notice {
                    background-color: #e3f2fd;
                    color: #0d47a1;
                    padding: 0.75rem;
                    border-radius: 8px;
                    font-size: 0.9rem;
                    margin-bottom: 1.5rem;
                    text-align: center;
                    border: 1px solid #bbdefb;
                }
            </style>
        </head>

        <body class="container-form">

            <form method="post" action="/?page=password">
                <div class="login-header">
                    <img src="assets/img/logo.svg" alt="DashMed Logo" class="login-logo">
                    <h1 class="login-title">DashMed</h1>
                    <p class="login-subtitle">Réinitialisation sécurisée</p>
                </div>

                <?php if ($msg): ?>
                    <p class="<?= htmlspecialchars($msg['type']) ?>"
                        style="text-align:center; padding: 10px; border-radius: 6px; width: 100%;">
                        <?= htmlspecialchars($msg['text']) ?>
                    </p>
                <?php endif; ?>

                <section>
                    <?php if (!$hasToken): ?>
                        <div style="text-align: center; color: var(--text-main); margin-bottom: 1rem;">
                            Entrez votre adresse e-mail pour recevoir un code de réinitialisation.
                        </div>
                        <article>
                            <label for="email">Adresse E-mail</label>
                            <input type="email" id="email" name="email" autocomplete="email" placeholder="exemple@dashmed.fr"
                                required>
                        </article>
                        <article>
                            <button class="pos" type="submit" name="action" value="send_code">Envoyer le code</button>
                        </article>
                        <div class="links" style="justify-content: center; margin-top: 10px;">
                            <a href="/?page=login">Retour à la connexion</a>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">

                        <div class="security-notice">
                            Un code à 6 chiffres a été envoyé à votre adresse e-mail.
                        </div>

                        <article>
                            <label for="code">Code de sécurité</label>
                            <div id="codeForm">
                                <div class="code-container">
                                    <?php foreach ($codeDigits as $digit): ?>
                                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="code-digit"
                                            value="<?= htmlspecialchars($digit) ?>" required>
                                    <?php endforeach; ?>
                                    <input type="hidden" id="code" name="code" value="<?= htmlspecialchars($codeFromUrl) ?>">
                                </div>
                            </div>
                        </article>

                        <article>
                            <label for="password">Nouveau mot de passe</label>
                            <div class="password">
                                <input type="password" id="password" name="password" minlength="8"
                                    placeholder="8 caractères minimum" required>
                                <button type="button" class="toggle" data-target="password" aria-pressed="false">
                                    <img src="assets/img/icons/eye-open.svg" alt="Afficher">
                                </button>
                            </div>
                        </article>

                        <article class="buttons" style="margin-top: 1rem;">
                            <a class="neg" href="/?page=login">Annuler</a>
                            <button class="pos" id="valider" type="submit" name="action" value="reset_password">Confirmer</button>
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
