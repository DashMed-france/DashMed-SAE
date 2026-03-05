<?php

namespace modules\views\user;

/**
 * Class ProfileView
 *
 * View for the user profile page.
 *
 * Displays personal info, allows updates and account deletion.
 *
 * @package DashMed\Modules\Views\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class ProfileView
{
    /**
     * Renders the profile page HTML.
     *
     * @param array{
     *   first_name?: string,
     *   last_name?: string,
     *   email?: string,
     *   id_profession?: int|string|null,
     *   profession_name?: string|null
     * }|null $user User data
     * @param array<int, array{
     *   id: int|string,
     *   name: string
     * }> $professions List of specialties (id, name)
     * @param array{type: string, text: string}|null $msg Flash message
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
            <?php include dirname(__DIR__) . '/partials/_sidebar.php'; ?>

            <main class="container nav-space">
                <section class="dashboard-content-container">
                    <h1>Mon profil</h1>

                    <?php if ($msg !== null) : ?>
                        <div class="alert <?= $h($msg['type']) ?>">
                            <?= $h($msg['text']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="profile-card">
                        <form action="/?page=profile" method="post" class="profile-form">
                            <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf_profile']) ?>">

                            <div class="form-group">
                                <label for="first_name">Prénom</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" viewBox="0 0 24 24">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67
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
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67
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
                                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9
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
                                        <path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11
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

                    <div class="settings-card" style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 16px; padding: 2rem; margin-bottom: 2rem; margin-top: 1rem;">
                        <div class="settings-info" style="margin-bottom: 1rem;">
                            <h3 style="margin: 0 0 0.5rem 0; color: var(--text-primary); font-size: 1.1rem; font-weight: 600;">Préférences de développement</h3>
                            <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">Options de débug : Mode ne pas déranger.</p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <label class="toggle-switch" style="display: flex; align-items: center; cursor: pointer; gap: 0.5rem; font-size: 0.95rem; color: var(--text-main);">
                                <input type="checkbox" id="dnd-dev-toggle" style="width: 1.2rem; height: 1.2rem; cursor: pointer;">
                                <span>Activer le mode "Ne pas déranger"</span>
                            </label>
                        </div>
                        <script>
                            const dndToggle = document.getElementById('dnd-dev-toggle');
                            dndToggle.checked = localStorage.getItem('dashmed_dnd') === 'true';
                            dndToggle.addEventListener('change', (e) => {
                                localStorage.setItem('dashmed_dnd', e.target.checked);
                                if (typeof iziToast !== 'undefined') {
                                    if (e.target.checked) {
                                        iziToast.info({ title: 'Info', message: 'Mode Ne pas déranger activé.', position: 'topRight' });
                                    } else {
                                        iziToast.success({ title: 'Succès', message: 'Mode Ne pas déranger désactivé.', position: 'topRight' });
                                    }
                                }
                            });
                        </script>
                    </div>

                    <div class="danger-zone-card">
                        <div class="danger-info">
                            <h3>Supprimer mon compte</h3>
                            <p>Cette action supprimera définitivement votre compte et est irréversible.</p>
                        </div>

                        <form action="/?page=profile" method="post" onsubmit="return confirm(
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
            <?php include dirname(__DIR__) . '/partials/_global-alerts.php'; ?>
        </body>

        </html>
        <?php
    }
}
