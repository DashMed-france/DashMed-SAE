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

            <!-- Global Styles & Theme -->
            <link rel="stylesheet" href="assets/css/style.css">
            <link id="theme-style" rel="stylesheet" href="/assets/css/themes/light.css">
            <link rel="stylesheet" href="/assets/css/themes/dark.css">

            <!-- Page Specific Style -->
            <link rel="stylesheet" href="assets/css/pages/password.css">

            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <div class="password-wrapper">
                <!-- Left Side: Visual Brand Area -->
                <div class="password-visual">
                    <div class="brand-content">
                        <img src="assets/img/logo.svg" alt="DashMed Logo" class="brand-logo">
                        <div class="brand-title">DashMed</div>
                        <div class="brand-tagline">
                            Sécurité et confidentialité<br>au cœur de notre engagement.
                        </div>
                    </div>
                </div>

                <!-- Right Side: Form Interaction -->
                <div class="password-form-container">
                    <div class="password-header">
                        <h1>Mot de passe oublié ?</h1>
                        <p>Pas de panique, nous allons vous aider à récupérer votre accès.</p>
                    </div>

                    <?php if ($msg): ?>
                        <div class="message-box <?= htmlspecialchars($msg['type']) === 'error' ? 'error' : 'success' ?>">
                            <?php if ($msg['type'] === 'error'): ?>
                                <svg style="width:20px;height:20px;fill:currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                                </svg>
                            <?php else: ?>
                                <svg style="width:20px;height:20px;fill:currentColor" viewBox="0 0 24 24">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                </svg>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($msg['text']) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="/?page=password">
                        <?php if (!$hasToken): ?>
                            <!-- Step 1: Request Email -->
                            <div class="form-group">
                                <label for="email">Adresse E-mail</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <path
                                            d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                                    </svg>
                                    <input type="email" id="email" name="email" autocomplete="email"
                                        placeholder="exemple@dashmed.fr" required>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">
                                    Entrez votre adresse e-mail pour recevoir un code de réinitialisation.
                                </p>
                            </div>

                            <div class="form-actions">
                                <button class="submit-btn" type="submit" name="action" value="send_code">Envoyer le code</button>
                                <div class="secondary-links">
                                    <a class="link-mute" href="/?page=login">Retour à la connexion</a>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- Step 2: Reset Password -->
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">

                            <div class="security-notice">
                                <svg style="width:20px;height:20px;flex-shrink:0;fill:currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z" />
                                </svg>
                                <span>Un code à 6 chiffres a été envoyé à votre adresse e-mail. Veuillez le saisir
                                    ci-dessous.</span>
                            </div>

                            <div class="form-group">
                                <label for="code">Code de sécurité</label>
                                <div id="codeForm">
                                    <div class="code-container">
                                        <?php foreach ($codeDigits as $i => $digit): ?>
                                            <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="code-digit"
                                                name="code_digits[]" value="<?= htmlspecialchars($digit) ?>" required
                                                aria-label="Chiffre <?= $i + 1 ?>"
                                                oninput="this.value=this.value.replace(/[^0-9]/g,''); if(this.value.length === 1) { var next = this.nextElementSibling; if(next) next.focus(); }">
                                        <?php endforeach; ?>
                                    </div>
                                    <!-- Hidden input to store full code if needed by backend or JS assembly -->
                                    <input type="hidden" id="code" name="code" value="<?= htmlspecialchars($codeFromUrl) ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password">Nouveau mot de passe</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <path
                                            d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z" />
                                    </svg>
                                    <input type="password" id="password" name="password" minlength="8"
                                        placeholder="8 caractères minimum" required>
                                    <button type="button" class="password-toggle" data-target="password"
                                        aria-label="Afficher le mot de passe">
                                        <img src="assets/img/icons/eye-open.svg" alt="Afficher" style="width: 20px; height: 20px;">
                                    </button>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button class="submit-btn" id="valider" type="submit" name="action" value="reset_password">Confirmer
                                    le nouveau mot de passe</button>
                                <div class="secondary-links">
                                    <a class="link-mute" href="/?page=login">Annuler</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <script src="assets/js/auth/password.js"></script>
            <script src="assets/js/auth/form.js"></script>
            <script src="assets/js/pages/dash.js"></script>
        </body>

        </html>
        <?php
    }
}
