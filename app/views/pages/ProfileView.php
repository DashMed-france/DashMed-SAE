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
     * @param array{
     *   first_name?: string,
     *   last_name?: string,
     *   email?: string,
     *   id_profession?: int|string,
     *   profession_name?: string
     * }|null $user User data | Tableau associatif contenant les données de l’utilisateur courant.
     * @param array<int, array{
     *   id: int|string,
     *   name: string
     * }> $professions List of specialties (id, name) | Liste des spécialités médicales disponibles.
     * @param array{type: string, text: string}|null $msg Flash message | Message optionnel.
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

            <link rel="stylesheet" href="assets/css/base/style.css">
            <link id="theme-style" rel="stylesheet" href="/assets/css/themes/light.css">
            <link rel="stylesheet" href="/assets/css/themes/dark.css">

            <link rel="stylesheet" href="assets/css/layout/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/alerts-toast.css">

            <link rel="stylesheet" href="assets/css/pages/profile.css">

            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

            <main class="container nav-space">
                <section class="dashboard-content-container">
                    <h1>Mon profil</h1>

                    <?php if ($msg !== null) : ?>
                        <div class="alert <?= $h($msg['type']) ?>">
                            <?= $h($msg['text']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Main Profile Card -->
                    <div class="profile-card">
                        <form action="/?page=profile" method="post" class="profile-form">
                            <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf_profile']) ?>">

                            <div class="form-group">
                                <label for="first_name">Prénom</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <path
                                            d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67
                                            0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                    </svg>
                                    <input type="text" id="first_name" name="first_name" required
                                        value="<?= $h($user['first_name'] ?? '') ?>" placeholder="Votre prénom">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="last_name">Nom</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <path
                                            d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67
                                            0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                    </svg>
                                    <input type="text" id="last_name" name="last_name" required
                                        value="<?= $h($user['last_name'] ?? '') ?>" placeholder="Votre nom">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <path
                                            d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9
                                            2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                                    </svg>
                                    <input type="email" id="email" name="email" disabled
                                        value="<?= $h($user['email'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="id_profession">Spécialité médicale</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <path
                                            d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11
                                            0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89
                                            2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z" />
                                    </svg>
                                    <select id="id_profession" name="id_profession">
                                        <option value="">-- Sélectionnez votre spécialité --</option>
                                        <?php
                                        $current = $user['id_profession'] ?? null;
                                        foreach ($professions as $s) {
                                            $id = (int) $s['id'];
                                            $name = $s['name'];
                                            $sel = ($current !== null && (int) $current === $id) ? 'selected' : '';
                                            echo '<option value="' . $id . '" ' . $sel . '>' . $h($name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <?php if (!empty($user['profession_name'])) : ?>
                                    <small class="current-info">Actuelle : <?= $h($user['profession_name']) ?></small>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="submit-btn">Enregistrer les modifications</button>
                        </form>
                    </div>

                    <!-- Danger Zone Card -->
                    <div class="danger-zone-card">
                        <div class="danger-info">
                            <h3>Supprimer mon compte</h3>
                            <p>Cette action supprimera définitivement votre compte et est irréversible.</p>
                        </div>

                        <form action="/?page=profile" method="post"
                            onsubmit="return confirm(
                                'Cette action est irréversible. Confirmer la suppression de votre compte ?'
                                );">
                            <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf_profile']) ?>">
                            <input type="hidden" name="action" value="delete_account">
                            <button type="submit" class="btn-danger-action">Supprimer mon compte</button>
                        </form>
                    </div>
                </section>

                <script src="assets/js/pages/dash.js"></script>
            </main>
            <?php include dirname(__DIR__) . '/components/global-alerts.php'; ?>
        </body>

        </html>
        <?php
    }
}
