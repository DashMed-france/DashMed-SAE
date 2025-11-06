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
    public function show( array $professions = []): void
    {
        $csrf = $_SESSION['_csrf'] ?? '';

        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);

        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['success']);

        $old = $_SESSION['old_sysadmin'] ?? [];
        unset($_SESSION['old_sysadmin']);

        $h = static function ($v): string {
            return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
        };
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
                        <input type="hidden" name="_form" value="create_user">
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

                            <article>
                                <label for="profession_id">Spécialité médicale</label>
                                <select id="profession_id" name="profession_id">
                                    <option value="">-- Sélectionnez la profession --</option>
                                    <?php
                                    $current = $old['profession_id'] ?? null;
                                    foreach ($professions as $s) {
                                        $id = (int)($s['id'] ?? 0);
                                        $name = $s['name'] ?? '';
                                        $sel = ($current !== null && (int)$current === $id) ? 'selected' : '';
                                        echo '<option value="'.$id.'" '.$sel.'>'.$h($name).'</option>';
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
                                        <input type="radio" name="admin_status" value="0"
                                                <?= !isset($old['admin_status']) || $old['admin_status'] === '0' ? 'checked' : '' ?>>
                                        Non
                                    </label>
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

                    <form action="?page=sysadmin" method="POST" novalidate>
                        <h1>Création d'un patient</h1>
                        <input type="hidden" name="_form" value="create_patient">
                        <section>
                            <article>
                                <label for="room">Chambre</label>
                                <select id="room" name="room" required>
                                    <option value="">-- Sélectionnez une chambre --</option>
                                    <option value="1" <?= isset($old['room']) && $old['room'] === '1' ? 'selected' : '' ?>>Chambre 1</option>
                                    <option value="2" <?= isset($old['room']) && $old['room'] === '2' ? 'selected' : '' ?>>Chambre 2</option>
                                    <option value="3" <?= isset($old['room']) && $old['room'] === '3' ? 'selected' : '' ?>>Chambre 3</option>
                                    <option value="4" <?= isset($old['room']) && $old['room'] === '4' ? 'selected' : '' ?>>Chambre 4</option>
                                    <option value="5" <?= isset($old['room']) && $old['room'] === '5' ? 'selected' : '' ?>>Chambre 5</option>
                                    <option value="6" <?= isset($old['room']) && $old['room'] === '6' ? 'selected' : '' ?>>Chambre 6</option>
                                    <option value="7" <?= isset($old['room']) && $old['room'] === '7' ? 'selected' : '' ?>>Chambre 7</option>
                                    <option value="8" <?= isset($old['room']) && $old['room'] === '8' ? 'selected' : '' ?>>Chambre 8</option>
                                    <option value="9" <?= isset($old['room']) && $old['room'] === '9' ? 'selected' : '' ?>>Chambre 9</option>
                                    <option value="10" <?= isset($old['room']) && $old['room'] === '10' ? 'selected' : '' ?>>Chambre 10</option>
                                    <option value="11" <?= isset($old['room']) && $old['room'] === '11' ? 'selected' : '' ?>>Chambre 11</option>
                                    <option value="12" <?= isset($old['room']) && $old['room'] === '12' ? 'selected' : '' ?>>Chambre 12</option>
                                    <option value="13" <?= isset($old['room']) && $old['room'] === '13' ? 'selected' : '' ?>>Chambre 13</option>
                                    <option value="14" <?= isset($old['room']) && $old['room'] === '14' ? 'selected' : '' ?>>Chambre 14</option>
                                    <option value="15" <?= isset($old['room']) && $old['room'] === '15' ? 'selected' : '' ?>>Chambre 15</option>
                                    <option value="16" <?= isset($old['room']) && $old['room'] === '16' ? 'selected' : '' ?>>Chambre 16</option>
                                    <option value="17" <?= isset($old['room']) && $old['room'] === '17' ? 'selected' : '' ?>>Chambre 17</option>
                                    <option value="18" <?= isset($old['room']) && $old['room'] === '18' ? 'selected' : '' ?>>Chambre 18</option>
                                    <option value="19" <?= isset($old['room']) && $old['room'] === '19' ? 'selected' : '' ?>>Chambre 19</option>
                                    <option value="20" <?= isset($old['room']) && $old['room'] === '20' ? 'selected' : '' ?>>Chambre 20</option>
                                </select>
                            </article>


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
                                <label for="gender">Sexe de naissance</label>
                                <div class="radio-group">
                                    <label>
                                        <input type="radio" name="gender" value="M"
                                                <?= isset($old['gender']) && $old['gender'] === 'Homme' ? 'checked' : '' ?>>
                                        Homme
                                    </label>
                                    <label>
                                        <input type="radio" name="gender" value="F"
                                                <?= isset($old['gender']) && $old['gender'] === 'Femme' ? 'checked' : '' ?>>
                                        Femme
                                    </label>
                                </div>
                            </article>

                            <article>
                                <label for="status">Statut du patient</label>
                                <select id="status" name="status" required>
                                    <option value="">-- Sélectionnez un statut --</option>
                                    <option value="En réanimation">En réanimation</option>
                                    <option value="Sorti">Sorti</option>
                                    <option value="Décédé">Décédé</option>
                                </select>
                            </article>


                            <article>
                                <label for="birth_date">Date de naissance</label>
                                <input type="date" id="birth_date" name="birth_date" required
                                       value="<?= htmlspecialchars($old['birth_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </article>

                            <article>
                                <label for="description">Raison d’admission</label>
                                <textarea id="description" name="description" rows="4" required
                                          placeholder="Décrivez brièvement la raison de l’admission..."><?= htmlspecialchars($old['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </article>


                            <article>
                                <label for="height">Taille (en cm)</label>
                                <input type="number" id="height" name="height" required
                                       value="<?= htmlspecialchars($old['height'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </article>

                            <article>
                                <label for="weight">Poids (en kg)</label>
                                <input type="number" id="weight" name="weight" required
                                       value="<?= htmlspecialchars($old['weight'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
            </section>
            <script src="assets/js/auth/form.js"></script>
        </main>
        </body>
        </html>
        <?php
    }
}