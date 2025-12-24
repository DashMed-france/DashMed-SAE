<?php

/**
 * DashMed — Vue d’inscription
 */

declare(strict_types=1);

namespace modules\views\auth;

class SignupView
{
    public function show(array $professions = []): void
    {
        $csrf  = $_SESSION['_csrf'] ?? '';
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);

        $old = $_SESSION['old_signup'] ?? [];
        unset($_SESSION['old_signup']);

        $h = static function ($v): string {
            return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
        };
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
        <body class="container-form">

        <?php if (!empty($error)) : ?>
            <div class="form-errors" role="alert"
                 style="background:#fee;border:1px solid #f99;color:#900;
                 padding:.75rem;border-radius:.5rem;margin:1rem 0;">
                <?= $h($error) ?>
            </div>
        <?php endif; ?>

        <form action="?page=signup" method="post" novalidate>
            <h1>Création d'un compte</h1>
            <section>
                <article>
                    <label for="last_name">Nom</label>
                    <input type="text" id="last_name" name="last_name" required
                           value="<?= $h($old['last_name'] ?? '') ?>">
                </article>

                <article>
                    <label for="first_name">Prénom</label>
                    <input type="text" id="first_name" name="first_name" required
                           value="<?= $h($old['first_name'] ?? '') ?>">
                </article>

                <article>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           value="<?= $h($old['email'] ?? '') ?>">
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
                        <input type="password" id="password_confirm" name="password_confirm"
                        required autocomplete="new-password">
                        <button type="button" class="toggle" data-target="password_confirm">
                            <img src="assets/img/icons/eye-open.svg" alt="eye">
                        </button>
                    </div>
                </article>

                <article>
                    <label for="id_profession">Spécialité médicale</label>
                    <select id="id_profession" name="id_profession" required>
                        <option value="">-- Sélectionnez votre spécialité --</option>
                        <?php
                        $current = isset($old['id_profession']) ? (int)$old['id_profession'] : null;
                        foreach ($professions as $s) {
                            $id   = (int)($s['id_profession'] ?? 0);
                            $name = $s['label_profession'] ?? '';
                            $sel  = ($current !== null && $current === $id) ? 'selected' : '';
                            echo '<option value="' . $id . '" ' . $sel . '>' . $h($name) . '</option>';
                        }
                        ?>
                    </select>
                </article>

                <?php if (!empty($csrf)) : ?>
                    <input type="hidden" name="_csrf" value="<?= $h($csrf) ?>">
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

        <script src="assets/js/auth/form.js"></script>
        </body>
        </html>
        <?php
    }
}
