<?php
/**
 * DashMed — Vue d’inscription
 *
 * Affiche le formulaire d’inscription permettant aux nouveaux utilisateurs de créer un compte.
 * Inclut la protection CSRF, les champs de confirmation de mot de passe
 * et la gestion des erreurs de validation.
 *
 * @package   DashMed\Modules\Views
 * @author    Équipe DashMed
 * @license   Propriétaire
 */
declare(strict_types=1);

namespace modules\views;

/**
 * Affiche la page d’inscription (enregistrement) pour les nouveaux utilisateurs DashMed.
 *
 * Responsabilités :
 *  - Afficher les champs prénom, nom, email et confirmation de mot de passe
 *  - Montrer les messages d’erreur en cas d’échec de validation
 *  - Conserver les saisies entre deux envois grâce aux données de session
 *  - Inclure un champ caché avec jeton CSRF pour un envoi sécurisé
 */
class signupView
{
    /**
     * Affiche le contenu HTML du formulaire d’inscription.
     *
     * La vue réutilise les valeurs stockées en session pour préremplir les champs
     * après des erreurs de validation, affiche les messages d’erreur éventuels
     * et inclut un jeton CSRF caché pour la sécurité.
     *
     * @return void
     */
    public function show(): void
    {
        $csrf = $_SESSION['_csrf'] ?? '';

        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);

        $old = $_SESSION['old_signup'] ?? [];
        unset($_SESSION['old_signup']);
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="description" content="Inscrivez vous à notre service.">
            <meta name="keywords" content="inscription, créer un compte, dashmed, médecin, patient, santé en ligne">
            <meta name="author" content="DashMed Team">
            <meta name="robots" content="noindex, nofollow">
            <title>DashMed - Créer un compte</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/form.css">
            <link rel="stylesheet" href="assets/css/components/buttons.css">
            <link id="theme" rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>
        <body>

        <?php if (!empty($error)): ?>
            <div class="form-errors" role="alert"
                 style="background:#fee;border:1px solid #f99;color:#900;padding:.75rem;border-radius:.5rem;margin:1rem 0;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="?page=signup" method="post" novalidate>
            <h1>Création d'un compte</h1>
            <section>
                <article>
                    <label for="last_name">Nom</label>
                    <input type="text" id="last_name" name="last_name" required
                           value="<?= htmlspecialchars($old['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </article>

                <article>
                    <label for="first_name">Prénom</label>
                    <input type="text" id="first_name" name="first_name" required
                           value="<?= htmlspecialchars($old['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </article>

                <article>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </article>

                <article>
                    <label for="password">Mot de passe</label>
                    <div class="password">
                        <input type="password" id="password" name="password" required autocomplete="new-password">
                        <button type="button" class="toggle" data-target="password">
                            <img src="assets/img/icons/eye-open.svg" alt="eye">
                        </button>
                    </div>
                </article>

                <article>
                    <label for="password_confirm">Confirmer le mot de passe</label>
                    <div class="password">
                        <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
                        <button type="button" class="toggle" data-target="password_confirm">
                            <img src="assets/img/icons/eye-open.svg" alt="eye">
                        </button>
                    </div>
                </article>

                <?php if (!empty($csrf)): ?>
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>

                <section class="buttons">
                    <a class="neg" href="?page=homepage">Annuler</a>
                    <button class="pos" type="submit">Créer le compte</button>
                </section>
            </section>
            <section class="links-signup">
                <a href="/?page=login">J'ai déjà un compte</a>
            </section>
        </form>

        <script src="assets/js/signin.js"></script>
        </body>
        </html>
        <?php
    }
}
