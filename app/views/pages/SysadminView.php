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
     * @param array<int, array{
     *   id_profession: int|string,
     *   label_profession: string
     * }> $professions List of available professions for doctors | Liste des professions disponibles pour les médecins.
     * @param array<int, array{
     *   id_user: int,
     *   first_name: string,
     *   last_name: string,
     *   email: string,
     *   admin_status: int,
     *   profession_label: string|null
     * }> $users List of all users | Liste de tous les utilisateurs.
     * @return void
     */
    public function show(array $professions = [], array $users = []): void
    {
        $csrf = $_SESSION['_csrf'] ?? '';

        $error = is_scalar($_SESSION['error'] ?? '') ? (string) ($_SESSION['error'] ?? '') : '';
        unset($_SESSION['error']);

        $success = is_scalar($_SESSION['success'] ?? '') ? (string) ($_SESSION['success'] ?? '') : '';
        unset($_SESSION['success']);

        /** @var array<string, string> */
        $old = isset($_SESSION['old_sysadmin']) && is_array($_SESSION['old_sysadmin']) ? $_SESSION['old_sysadmin'] : [];
        unset($_SESSION['old_sysadmin']);

        $h = static function ($v): string {
            return htmlspecialchars(is_scalar($v) ? (string) $v : '', ENT_QUOTES, 'UTF-8');
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
            <link rel="stylesheet" href="assets/css/base/style.css">
            <link id="theme-style" rel="stylesheet" href="/assets/css/themes/light.css">
            <link rel="stylesheet" href="/assets/css/themes/dark.css">

            <link rel="stylesheet" href="assets/css/layout/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/alerts-toast.css">

            <link rel="stylesheet" href="assets/css/pages/sysadmin.css">

            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

            <main class="container nav-space">
                <section class="dashboard-content-container">
                    <h1>Administrateur système</h1>

                    <?php if (!empty($error)) : ?>
                        <div class="alert error" role="alert">
                            <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)) : ?>
                        <div class="alert success" role="alert">
                            <?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <div class="admin-grid">
                        <div class="sysadmin-card">
                            <form action="?page=sysadmin" method="POST" novalidate>
                                <h2>Création d'un compte</h2>

                                <div class="form-group">
                                    <label for="last_name">Nom</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4
                                                1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                        </svg>
                                        <input type="text"
                                               id="last_name"
                                               name="last_name"
                                               required placeholder="Nom de famille"
                                            value="<?= $h($old['last_name'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="first_name">Prénom</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4
                                                4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                        </svg>
                                        <input type="text"
                                               id="first_name"
                                               name="first_name"
                                               required placeholder="Prénom"
                                            value="<?= $h($old['first_name'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1
                                                0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                                        </svg>
                                        <input type="email" id="email" name="email" required autocomplete="email"
                                            placeholder="exemple@dashmed.fr" value="<?= $h($old['email'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="password">Mot de passe</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2
                                                2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6
                                                9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71
                                                1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z" />
                                        </svg>
                                        <input type="password" id="password" name="password" required
                                            autocomplete="new-password" placeholder="••••••••">
                                        <button type="button" class="password-toggle" data-target="password"
                                            aria-label="Afficher le mot de passe">
                                            <img src="assets/img/icons/eye-open.svg" alt="eye"
                                                style="width: 20px; height: 20px;">
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="password_confirm">Confirmer le mot de passe</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0
                                                1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1
                                                0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1
                                                3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z" />
                                        </svg>
                                        <input type="password" id="password_confirm" name="password_confirm" required
                                            autocomplete="new-password" placeholder="••••••••">
                                        <button type="button" class="password-toggle" data-target="password_confirm"
                                            aria-label="Afficher le mot de passe">
                                            <img src="assets/img/icons/eye-open.svg" alt="eye"
                                                style="width: 20px; height: 20px;">
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="profession_id">Spécialité médicale</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11
                                                0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89
                                                2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z" />
                                        </svg>
                                        <select id="profession_id" name="profession_id">
                                            <option value="">-- Sélectionnez la profession --</option>
                                            <?php
                                            $current = $old['profession_id'] ?? null;
                                            foreach ($professions as $s) {
                                                $id = (int) $s['id_profession'];
                                                $name = $s['label_profession'];
                                                $sel = ($current !== null && (int) $current === $id) ? 'selected' : '';
                                                echo '<option 
                                                value="' . $id . '" ' . $sel . '>' . $h($name) . '
                                                </option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
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
                                </div>

                                <?php if (!empty($csrf)) : ?>
                                    <input type="hidden" name="_csrf" value="<?= $h($csrf) ?>">
                                <?php endif; ?>

                                <button class="submit-btn" type="submit">Créer le compte</button>
                            </form>
                        </div>

                        <div class="sysadmin-card">
                            <form action="?page=sysadmin" method="POST" novalidate>
                                <h2>Création d'un patient</h2>

                                <div class="form-group">
                                    <label for="room">Chambre</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M12 7V3H2v18h20V7H12zM6
                                                19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4
                                                12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10
                                                12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0
                                                4h-2v2h2v-2z" />
                                        </svg>
                                        <?php
                                        $selectedRoom = $old['room'] ?? '';
                                        $rooms = ['101', '102', '103', '104'];
                                        ?>

                                        <select id="room" name="room" required>
                                            <option value="">-- Sélectionnez une chambre --</option>

                                            <?php foreach ($rooms as $room) : ?>
                                                <option
                                                        value="<?= $room ?>"
                                                        <?= $selectedRoom === $room ? 'selected' : '' ?>
                                                >
                                                    Chambre <?= $room ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="last_name_p">Nom</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4
                                                1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                        </svg>
                                        <input type="text" id="last_name_p" name="last_name" required
                                            placeholder="Nom du patient" value="<?= $h($old['last_name'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="first_name_p">Prénom</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4
                                                4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                        </svg>
                                        <input type="text"
                                               id="first_name_p"
                                               name="first_name"
                                               required
                                               placeholder="Prénom du patient"
                                               value="<?= $h($old['first_name'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email_p">Email</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0
                                                2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                                        </svg>
                                        <input type="email" id="email_p" name="email" required autocomplete="email"
                                            placeholder="email@patient.com" value="<?= $h($old['email'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="form-group">
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
                                </div>

                                <div class="form-group">
                                    <label for="birth_date">Date de naissance</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2
                                                2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z" />
                                        </svg>
                                        <input type="date" id="birth_date" name="birth_date" required
                                            value="<?= $h($old['birth_date'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="admission_reason">Raison d’admission</label>
                                    <div class="input-wrapper">
                                        <textarea id="admission_reason" name="admission_reason" rows="4" required
                                            placeholder="Décrivez brièvement la raison de l’admission...">
                                            <?= $h($old['admission_reason'] ?? '') ?>
                                        </textarea>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="height">Taille (cm)</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9
                                                2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14zM9 7h6v2H9zm0 8h6v2H9z" />
                                        </svg>
                                        <input type="text" id="height" name="height" required placeholder="Ex: 175"
                                            value="<?= $h($old['height'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="weight">Poids (kg)</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12
                                                2zm1 14h-2v-2h2v2zm0-4h-2V7h2v5z" />
                                        </svg>
                                        <input type="text" id="weight" name="weight" required placeholder="Ex: 70.5"
                                            value="<?= $h($old['weight'] ?? '') ?>">
                                    </div>
                                </div>

                                <?php if (!empty($csrf)) : ?>
                                    <input type="hidden" name="_csrf" value="<?= $h($csrf) ?>">
                                <?php endif; ?>

                                <button class="submit-btn" type="submit">Créer le patient</button>
                            </form>
                        </div>
                    </div>

                    <!-- Card: Gestion des profils -->
                    <div class="sysadmin-card sysadmin-card-full">
                        <h2>Gestion des profils</h2>

                        <div class="form-group">
                            <label for="search-profiles">Rechercher un profil</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <path
                                        d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3
                                         5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49
                                          19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99
                                           14 9.5 14z" />
                                </svg>
                                <input type="text" id="search-profiles" placeholder="Tapez un nom pour filtrer...">
                            </div>
                        </div>

                        <div class="profiles-grid" id="profiles-list">
                            <?php if (empty($users)) : ?>
                                <p class="no-profiles">Aucun profil trouvé.</p>
                            <?php else : ?>
                                <?php foreach ($users as $u) : ?>
                                    <div class="profile-card-item"
                                         data-user-id="<?= (int) $u['id_user'] ?>"
                                         data-name="<?= $h($u['last_name'] . ' ' . $u['first_name']) ?>">
                                        <div class="profile-card-info">
                                            <div class="profile-avatar">
                                                <?= strtoupper(mb_substr($u['first_name'], 0, 1)) . strtoupper(mb_substr($u['last_name'], 0, 1)) ?>
                                            </div>
                                            <div class="profile-details">
                                                <div class="profile-name">
                                                    <?= $h($u['last_name'] . ' ' . $u['first_name']) ?>
                                                    <?php if ((int)$u['admin_status'] === 1) : ?>
                                                        <span class="badge-admin">Admin</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="profile-email"><?= $h($u['email']) ?></div>
                                                <?php if (!empty($u['profession_label'])) : ?>
                                                    <div class="profile-profession"><?= $h($u['profession_label']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ((int)$u['admin_status'] !== 1) : ?>
                                        <button type="button" class="delete-profile-btn"
                                                data-user-id="<?= (int) $u['id_user'] ?>"
                                                data-user-name="<?= $h($u['last_name'] . ' ' . $u['first_name']) ?>"
                                                title="Supprimer ce profil">
                                            <svg viewBox="0 0 24 24" width="18" height="18">
                                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <p id="no-profile-results" class="no-profiles" style="display:none;">Aucun profil correspondant.</p>
                    </div>

                </section>

                <!-- Modal de confirmation de suppression -->
                <div id="delete-modal" class="delete-modal-overlay" style="display:none;">
                    <div class="delete-modal">
                        <div class="delete-modal-icon">
                            <svg viewBox="0 0 24 24" width="48" height="48">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                            </svg>
                        </div>
                        <h3>Êtes-vous sûr ?</h3>
                        <p>Voulez-vous vraiment supprimer le profil de <strong id="delete-modal-name"></strong> ?</p>
                        <p class="delete-modal-warning">Cette action est irréversible.</p>
                        <form action="?page=sysadmin" method="POST" id="delete-form">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="delete_user_id" id="delete-user-id" value="">
                            <?php if (!empty($csrf)) : ?>
                                <input type="hidden" name="_csrf" value="<?= $h($csrf) ?>">
                            <?php endif; ?>
                            <div class="delete-modal-actions">
                                <button type="button" class="modal-btn modal-btn-cancel" id="delete-cancel">Annuler</button>
                                <button type="submit" class="modal-btn modal-btn-delete">Supprimer</button>
                            </div>
                        </form>
                    </div>
                </div>

                <script src="assets/js/auth/form.js"></script>
                <script src="assets/js/pages/dash.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // --- Search/filter profiles ---
                        const searchInput = document.getElementById('search-profiles');
                        const profileCards = document.querySelectorAll('.profile-card-item');
                        const noResultsMsg = document.getElementById('no-profile-results');

                        function normalizeString(str) {
                            return str.normalize('NFD')
                                .replace(/[\u0300-\u036f]/g, '')
                                .toLowerCase()
                                .trim();
                        }

                        function filterProfiles() {
                            const term = normalizeString(searchInput.value);
                            let visibleCount = 0;

                            profileCards.forEach(card => {
                                const name = normalizeString(card.getAttribute('data-name') || '');
                                const email = normalizeString(
                                    card.querySelector('.profile-email')?.textContent || ''
                                );

                                if (name.includes(term) || email.includes(term)) {
                                    card.style.display = 'flex';
                                    visibleCount++;
                                } else {
                                    card.style.display = 'none';
                                }
                            });

                            noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
                        }

                        let searchTimeout;
                        if (searchInput) {
                            searchInput.addEventListener('input', function() {
                                clearTimeout(searchTimeout);
                                searchTimeout = setTimeout(filterProfiles, 200);
                            });
                            searchInput.addEventListener('keypress', function(e) {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    clearTimeout(searchTimeout);
                                    filterProfiles();
                                }
                            });
                        }

                        // --- Delete modal ---
                        const modal = document.getElementById('delete-modal');
                        const modalName = document.getElementById('delete-modal-name');
                        const deleteIdInput = document.getElementById('delete-user-id');
                        const cancelBtn = document.getElementById('delete-cancel');

                        document.querySelectorAll('.delete-profile-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const userId = this.getAttribute('data-user-id');
                                const userName = this.getAttribute('data-user-name');
                                deleteIdInput.value = userId;
                                modalName.textContent = userName;
                                modal.style.display = 'flex';
                            });
                        });

                        if (cancelBtn) {
                            cancelBtn.addEventListener('click', function() {
                                modal.style.display = 'none';
                            });
                        }

                        // Close modal on overlay click
                        if (modal) {
                            modal.addEventListener('click', function(e) {
                                if (e.target === modal) {
                                    modal.style.display = 'none';
                                }
                            });
                        }
                    });
                </script>
            </main>
        </body>

        </html>
        <?php
    }
}
