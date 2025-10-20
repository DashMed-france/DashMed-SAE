<?php

/**
 * DashMed — Vue du tableau de bord administrateur
 *
 * Affiche la page principale du tableau de bord pour les administrateurs authentifiés.
 * Contient deux formulaires pour créer soit un patient soit un docteur.
 * et des composants latéraux tels que la barre latérale.
 *
 * @package   DashMed\Modules\Views
 * @author    Équipe DashMed
 * @license   Propriétaire
 */

namespace modules\views\pages;

/**
 * Affiche l’interface du tableau de bord de la plateforme DashMed.
 *
 * Responsabilités :
 *  - Inclure les composants de mise en page nécessaires (barre latérale, formulaires de création, etc.)
 *
 */

class sysadminView
{
    /**
     * Génère la structure HTML complète de la page du tableau de bord.
     *
     * Inclut la barre latérale, la barre de recherche supérieure, le panneau d’informations patient,
     * le calendrier et la liste des médecins.
     * Cette vue n’effectue aucune logique métier — elle se limite uniquement au rendu.
     *
     * @return void
     */
    public function show(): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>DashMed - Sysadmin</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <meta name="author" content="DashMed Team">
            <meta name="keywords" content="dashboard, santé, médecins, patients, DashMed, sysadmin, administrateur">
            <meta name="description" content="Tableau de bord privé pour les administrateurs du système dashmed, accessible uniquement aux utilisateurs administrateur authentifiés.">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/dash.css">
            <link rel="stylesheet" href="assets/css/form.css">
            <link rel="stylesheet" href="assets/css/components/buttons.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">
            <link rel="stylesheet" href="assets/css/admin.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>
        <body>
        <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

        <main class="dashboard-content-container nav-space">

            <section class="center">
                <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>
            </section>
            <section class="container center">
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
                            <button class="pos" type="submit">Créer le compte</button>
                        </section>
                    </section>
                </form>

                <form action="?page=signup" method="post" novalidate>
                    <h1>Création d'un patient</h1>
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

                        <?php if (!empty($csrf)): ?>
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>

                        <section class="buttons">
                            <button class="pos" type="submit">Créer le patient</button>
                        </section>
                    </section>
                </form>
            </section>
            <script src="assets/js/signin.js"></script>
        </main>
        </body>
        </html>
        <?php
    }
}