<?php
/**
 * DashMed — Vue Profil
 *
 * Affiche l’interface de gestion du profil utilisateur.
 * Permet l’édition des informations personnelles (prénom, nom, spécialité)
 * et la suppression du compte, avec protection CSRF pour tous les formulaires.
 *
 * @package   DashMed\Modules\Views
 * @author    Équipe DashMed
 * @license   Propriétaire
 */
namespace modules\views\pages;

/**
 * Affiche la page profil pour les utilisateurs authentifiés de DashMed.
 *
 * Responsabilités :
 *  - Afficher les informations de l’utilisateur récupérées depuis la base de données
 *  - Permettre la mise à jour des informations personnelles et de la spécialité médicale
 *  - Gérer les messages de succès/erreur et le jeton CSRF pour la validation du formulaire
 *  - Inclure une zone dangereuse pour la confirmation de suppression de compte
 */

class profileView
{
    /**
     * Affiche le contenu HTML de la page profil.
     *
     * @param array|null $user         Tableau associatif contenant les données de l’utilisateur courant.
     * @param array      $specialties  Liste des spécialités médicales disponibles (id, name).
     * @param array|null $msg          Message optionnel (['type' => 'success|error', 'text' => string]).
     * @return void
     */
    public function show(?array $user, array $specialties = [], ?array $msg = null): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['csrf_profile'] = bin2hex(random_bytes(16));

        $h = static function ($v): string {
            return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
        };
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>DashMed - Mon profil</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name=" description" content="Modifiez vos informations personnelles ici.">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/dash.css">
            <link rel="stylesheet" href="assets/css/form.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/form.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>
        <body>
        <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

        <main class="container-form">
            <h1>Mon profil</h1>

            <?php if (is_array($msg) && isset($msg['text'])): ?>
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
                        <input type="email" id="email" name="email" disabled
                               value="<?= $h($user['email'] ?? '') ?>">
                    </article>

                    <article>
                        <label for="profession_id">Spécialité médicale</label>
                        <select id="profession_id" name="profession_id">
                            <option value="">-- Sélectionnez votre spécialité --</option>
                            <?php
                            $current = $user['profession_id'] ?? null;
                            foreach ($specialties as $s) {
                                $id = (int)($s['id'] ?? 0);
                                $name = $s['name'] ?? '';
                                $sel = ($current !== null && (int)$current === $id) ? 'selected' : '';
                                echo '<option value="'.$id.'" '.$sel.'>'.$h($name).'</option>';
                            }
                            ?>
                        </select>
                        <?php if (!empty($user['profession_name'])): ?>
                            <small>Actuelle : <?= $h($user['profession_name']) ?></small>
                        <?php endif; ?>
                    </article>
                </section>
                <section>
                    <button type="submit" class="pos">Enregistrer les modifications</button>
                </section>
            </form>

            <form action="/?page=profile" method="post" class="danger-zone"
                  onsubmit="return confirm('⚠️ Cette action est irréversible. Confirmer la suppression de votre compte ?');">
                <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf_profile'] ?? '') ?>">
                <input type="hidden" name="action" value="delete_account">
                <button type="submit" class="btn-danger">Supprimer mon compte</button>
                <small class="danger-help">Cette action supprimera définitivement votre compte.</small>
            </form>
        </main>
        </body>
        </html>
        <?php
    }
}
