<?php

namespace modules\views\pages;

/**
 * Class ProfileView | Vue Profil
 *
 * View for the user profile page.
 * Vue de la page de profil utilisateur.
 *
 * Displays personal info, allows updates and account deletion.
 * Affiche les informations de l’utilisateur récupérées depuis la base de données,
 * permet la mise à jour des informations personnelles et de la spécialité médicale,
 * et inclut une zone dangereuse pour la confirmation de suppression de compte.
 *
 * @package DashMed\Modules\Views\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class ProfileView
{
    /**
     * Renders the profile page HTML.
     * Affiche le contenu HTML de la page profil.
     *
     * @param array|null $user         User data (assoc array) | Tableau associatif contenant les données de l’utilisateur courant.
     * @param array      $professions  List of specialties (id, name) | Liste des spécialités médicales disponibles.
     * @param array|null $msg          Flash message | Message optionnel (['type' => 'success|error', 'text' => string]).
     * @return void
     */
    public function show(?array $user, array $professions = [], ?array $msg = null): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['csrf_profile'] = bin2hex(random_bytes(16));

        $h = static function ($v): string {
            return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        };
        ?>
        <!DOCTYPE html>
        <html lang="fr">

        <head>
            <meta charset="UTF-8">
            <title>DashMed - Mon profil</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name=" description" content="Modifiez vos informations personnelles ici.">
            <link rel="stylesheet" href="/assets/css/themes/light.css">
            <link rel="stylesheet" href="/assets/css/themes/dark.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/dash.css">
            <link rel="stylesheet" href="assets/css/form.css">
            <link rel="stylesheet" href="assets/css/components/danger.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <!-- <link rel="stylesheet" href="assets/css/components/form.css"> Removed: File does not exist -->
            <link rel="stylesheet" href="assets/css/components/buttons.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

            <main class="container-form">
                <h1>Mon profil</h1>

                <?php if (is_array($msg) && isset($msg['text'])) : ?>
                    <div class="alert <?= $h($msg['type'] ?? 'info') ?>">
                        <?= $h($msg['text']) ?>
                    </div>
                <?php endif; ?>

                <form action="/?page=profile" method="post" class="profile-form">
                    <section>
                        <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf_profile'] ?? '') ?>">

                        <article>
                            <label for="first_name">Prénom</label>
                            <input type="text" id="first_name" name="first_name" required
                                value="<?= $h($user['first_name'] ?? '') ?>">
                        </article>

                        <article>
                            <label for="last_name">Nom</label>
                            <input type="text" id="last_name" name="last_name" required
                                value="<?= $h($user['last_name'] ?? '') ?>">
                        </article>

                        <article>
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" disabled value="<?= $h($user['email'] ?? '') ?>">
                        </article>

                        <article>
                            <label for="id_profession">Spécialité médicale</label>
                            <select id="id_profession" name="id_profession">
                                <option value="">-- Sélectionnez votre spécialité --</option>
                                <?php
                                $current = $user['id_profession'] ?? null;
                                foreach ($professions as $s) {
                                    $id = (int) ($s['id'] ?? 0);
                                    $name = $s['name'] ?? '';
                                    $sel = ($current !== null && (int) $current === $id) ? 'selected' : '';
                                    echo '<option value="' . $id . '" ' . $sel . '>' . $h($name) . '</option>';
                                }
                                ?>
                            </select>
                            <?php if (!empty($user['profession_name'])) : ?>
                                <small>Actuelle : <?= $h($user['profession_name']) ?></small>
                            <?php endif; ?>
                        </article>

                        <button type="submit" class="pos">Enregistrer les modifications</button>
                    </section>
                </form>

                <form action="/?page=profile" method="post" class="danger-zone" onsubmit="return confirm('Cette action est irréversible.' +
                       ' Confirmer la suppression de votre compte ?');">
                    <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf_profile'] ?? '') ?>">
                    <input type="hidden" name="action" value="delete_account">
                    <button type="submit" class="btn-danger">Supprimer mon compte</button>
                    <small class="danger-help">Cette action supprimera définitivement votre compte.</small>
                </form>
                <script src="assets/js/pages/dash.js"></script>
            </main>
            <?php include dirname(__DIR__) . '/components/global-alerts.php'; ?>
        </body>

        </html>
        <?php
    }
}
