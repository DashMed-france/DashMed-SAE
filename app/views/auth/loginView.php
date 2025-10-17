<?php
/**
 * DashMed — Vue de connexion
 *
 * Affiche le formulaire de connexion permettant aux utilisateurs de s’authentifier sur DashMed.
 * Inclut la protection CSRF, les champs email/mot de passe et des liens vers l’inscription
 * et la récupération de mot de passe.
 *
 * @package   DashMed\Modules\Views
 * @author    Équipe DashMed
 * @license   Propriétaire
 */

namespace modules\views\auth;

/**
 * Affiche la page de connexion de la plateforme DashMed.
 *
 * Responsabilités :
 *  - Afficher le formulaire de connexion avec jeton CSRF
 *  - Fournir les champs de saisie pour l’email et le mot de passe
 *  - Inclure les boutons d’envoi du formulaire et de navigation
 *  - Charger les feuilles de style et scripts dédiés pour l’interactivité du formulaire
 */
class loginView
{
    /**
     * Génère l’intégralité du HTML du formulaire de connexion.
     *
     * Le formulaire envoie une requête POST vers la route /?page=login et inclut :
     *  - Les champs de saisie email et mot de passe
     *  - Un jeton CSRF pour la validation de la requête
     *  - Des liens de navigation pour la création de compte et la récupération de mot de passe
     *
     * @return void
     */
    public function show(): void
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
            <link id="theme" rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>
        <body>
        <form action="/?page=login" method="post" novalidate>
            <h1>Se connecter</h1>
            <section>
                <article>
                    <label for="email">Email</label>
                    <input type="text" id="email" name="email" autocomplete="email" required>
                </article>
                <article>
                    <label for="password">Mot de passe</label>
                    <div class="password">
                        <input type="password" id="password" name="password" autocomplete="current-password" required>
                        <button type="button" class="toggle-password" aria-label="Afficher/Masquer le mot de passe">
                            <img src="assets/img/icons/eye-open.svg" alt="eye">
                        </button>
                    </div>
                </article>

                <?php if (!empty($csrf)): ?>
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

        <script src="assets/js/login.js"></script>
        </body>
        </html>
        <?php
    }
}
