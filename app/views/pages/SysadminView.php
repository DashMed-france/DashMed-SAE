<?php

namespace modules\views\pages;

/**
 * Class SysadminView
 *
 * View for the system administrator dashboard.
 *
 * Displays forms to create doctors and patients.
 *
 * @package DashMed\Modules\Views\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class SysadminView
{
    /**
     * Renders the complete dashboard HTML.
     *
     * Includes sidebar, error/success messages, and creation forms.
     *
     * @param array<int, array{
     *   id_profession: int|string,
     *   label_profession: string
     * }> $professions List of available professions for doctors
     * @return void
     */
    public function show(array $professions = []): void
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
                </section>

                <script src="assets/js/auth/form.js"></script>
                <script src="assets/js/pages/dash.js"></script>
            </main>
        </body>

        </html>
        <?php
    }
}
