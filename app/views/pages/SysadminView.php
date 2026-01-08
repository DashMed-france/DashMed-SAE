<?php

namespace modules\views\pages;

/**
 * Class SysadminView | Vue Administrateur Système
 *
 * View for the system administrator dashboard.
 * Vue du tableau de bord administrateur système.
 *
 * Displays forms to create doctors and patients.
 * Affiche la page principale du tableau de bord pour les administrateurs authentifiés.
 * Contient deux formulaires pour créer soit un patient soit un docteur.
 *
 * @package DashMed\Modules\Views\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class SysadminView
{
    /**
     * Renders the complete dashboard HTML.
     * Génère la structure HTML complète de la page du tableau de bord.
     *
     * Includes sidebar, error/success messages, and creation forms.
     * Inclut la barre latérale, la gestion des erreurs et les formulaires de création.
     *
     * @param array $professions List of available professions for doctors | Liste des professions disponibles pour les médecins.
     * @return void
     */
    public function show(array $professions = []): void
    {
        $csrf = $_SESSION['_csrf'] ?? '';

        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);

        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['success']);

        $old = $_SESSION['old_sysadmin'] ?? [];
        unset($_SESSION['old_sysadmin']);

        $h = static function ($v): string {
            return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        };

        $adminNoChecked = (!isset($old['admin_status']) || $old['admin_status'] === '0') ? 'checked' : '';
        $genderHommeChecked = (isset($old['gender']) && $old['gender'] === 'Homme') ? 'checked' : '';
        $genderFemmeChecked = (isset($old['gender']) && $old['gender'] === 'Femme') ? 'checked' : '';
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
            <meta name="description" content="Tableau de bord privé pour les administrateurs
            du système dashmed, accessible uniquement aux utilisateurs administrateur authentifiés.">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/themes/dark.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/dash.css">
            <link rel="stylesheet" href="assets/css/form.css">
            <link rel="stylesheet" href="assets/css/components/buttons.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/alerts.css">
            <link rel="stylesheet" href="assets/css/admin.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

            <main class="container nav-space">
                <section class="dashboard-content-container">
                    <h1>Administrateur système</h1>
                    <?php if (!empty($error)): ?>
                        <div class="alert error" role="alert">
                            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert success" role="alert">
                            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                    <section class="admin-form-container">
                        <form action="?page=sysadmin" method="POST" novalidate>
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
                                        <input type="password" id="password" name="password" required
                                            autocomplete="new-password">
                                        <button type="button" class="toggle" data-target="password">
                                            <img src="assets/img/icons/eye-open.svg" alt="eye">
                                        </button>
                                    </div>
                                </article>

                                <article>
                                    <label for="password_confirm">Confirmer le mot de passe</label>
                                    <div class="password">
                                        <input type="password" id="password_confirm" name="password_confirm" required
                                            autocomplete="new-password">
                                        <button type="button" class="toggle" data-target="password_confirm">
                                            <img src="assets/img/icons/eye-open.svg" alt="eye">
                                        </button>
                                    </div>
                                </article>

                                <article>
                                    <label for="profession_id">Spécialité médicale</label>
                                    <select id="profession_id" name="profession_id">
                                        <option value="">-- Sélectionnez la profession --</option>
                                        <?php
                                        $current = $old['profession_id'] ?? null;
                                        foreach ($professions as $s) {
                                            $id = (int) ($s['id'] ?? 0);
                                            $name = $s['name'] ?? '';
                                            $sel = ($current !== null && (int) $current === $id) ? 'selected' : '';
                                            echo '<option value="' . $id . '" ' . $sel . '>' . $h($name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </article>

                                <article>
                                    <label for="admin_status">Administration</label>
                                    <div class="radio-group">
                                        <label>
                                            <input type="radio" name="admin_status" value="1">
                                            Oui
                                        </label>
                                        <label>
                                            <input type="radio" name="admin_status" value="0" <?= $adminNoChecked ?>>
                                            Non
                                        </label>
                                    </div>
                                </article>

                                <?php if (!empty($csrf)): ?>
                                    <input type="hidden" name="_csrf" value="<?= $h($csrf) ?>">
                                <?php endif; ?>

                                <section class="buttons">
                                    <button class="pos" type="submit">Créer le compte</button>
                                </section>
                            </section>
                        </form>

                        <form action="?page=sysadmin" method="POST" novalidate>
                            <h1>Création d'un patient</h1>
                            <section>
                                <article>
                                    <label for="room">Chambre</label>
                                    <select id="room" name="room" required>
                                        <option value="">-- Sélectionnez une chambre --</option>
                                        <option value="101" <?= isset($old['room']) &&
                                            $old['room'] === '101' ? 'selected' : '' ?>>
                                            Chambre 101</option>
                                        <option value="102" <?= isset($old['room']) &&
                                            $old['room'] === '102' ? 'selected' : '' ?>>
                                            Chambre 102</option>
                                        <option value="103" <?= isset($old['room']) &&
                                            $old['room'] === '103' ? 'selected' : '' ?>>
                                            Chambre 103</option>
                                        <option value="104" <?= isset($old['room']) &&
                                            $old['room'] === '104' ? 'selected' : '' ?>>
                                            Chambre 104</option>
                                    </select>
                                </article>


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
                                    <label for="gender">Sexe de naissance</label>
                                    <div class="radio-group">
                                        <label>
                                            <input type="radio" name="gender" value="Homme" <?= $genderHommeChecked ?>>
                                            Homme
                                        </label>
                                        <label>
                                            <input type="radio" name="gender" value="Femme" <?= $genderFemmeChecked ?>>
                                            Femme
                                        </label>
                                    </div>
                                </article>

                                <article>
                                    <label for="birth_date">Date de naissance</label>
                                    <input type="date" id="birth_date" name="birth_date" required
                                        value="<?= $h($old['birth_date'] ?? '') ?>">
                                </article>

                                <article>
                                    <label for="admission_reason">Raison d’admission</label>
                                    <textarea id="admission_reason" name="admission_reason" rows="4" required
                                        placeholder="Décrivez brièvement la raison de l’admission...">
                                                                    <?= $h($old['admission_reason'] ?? '') ?>
                                                                </textarea>
                                </article>


                                <article>
                                    <label for="height">Taille (en cm)</label>
                                    <input type="text" id="height" name="height" required
                                        value="<?= $h($old['height'] ?? '') ?>">
                                </article>

                                <article>
                                    <label for="weight">Poids (en kg)</label>
                                    <input type="text" id="weight" name="weight" required
                                        value="<?= $h($old['weight'] ?? '') ?>">
                                </article>

                                <?php if (!empty($csrf)): ?>
                                    <input type="hidden" name="_csrf" value="<?= $h($csrf) ?>">
                                <?php endif; ?>

                                <section class="buttons">
                                    <button class="pos" type="submit">Créer le patient</button>
                                </section>
                            </section>
                        </form>
                    </section>
                </section>
                <script src="assets/js/auth/form.js"></script>
                <script src="assets/js/pages/dash.js"></script>
            </main>
        </body>

        </html>
        <?php
    }
}
