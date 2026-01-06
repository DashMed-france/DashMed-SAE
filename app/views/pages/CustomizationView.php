<?php

namespace modules\views\pages;

class CustomizationView
{
    public function show(array $parameters, array $errors = []): void
    {
        $h = static function ($v): string {
            return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        };
        ?>
        <!DOCTYPE html>
        <html lang="fr">

        <head>
            <meta charset="UTF-8">
            <title>DashMed - Personnalisation</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/dash.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">
            <link rel="stylesheet" href="assets/css/pages/customization.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

            <main class="container nav-space">
                <section class="dashboard-content-container">
                    <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>

                    <section class="customization-content">
                        <div class="customization-header">
                            <h1>Personnaliser l'affichage</h1>
                            <p style="color: var(--text-muted); margin-top: 5px;">
                                Activez ou désactivez les indicateurs que vous souhaitez voir apparaître
                                sur le tableau de bord.
                            </p>
                        </div>

                        <?php if (isset($_GET['success'])) : ?>
                            <div class="alert alert-success" style="background: #d4edda; color: #155724;
                                padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                Vos préférences ont été enregistrées avec succès.
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)) : ?>
                            <div class="alert alert-danger" style="background: #f8d7da; color: #721c24;
                                padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                Attention : Certains ordres d'affichage sont dupliqués. Veuillez les corriger.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/?page=customization">
                            <div class="customization-grid">
                                <?php
                                usort($parameters, fn($a, $b) => $a['display_order'] <=> $b['display_order']);
                                ?>
                                <?php foreach ($parameters as $param) : ?>
                                    <?php
                                    $isError = in_array($param['id'], $errors);
                                    ?>
                                    <div class="custom-card <?= $isError ? 'card-error' : '' ?>">
                                        <div class="custom-vis-group">
                                            <label class="switch">
                                                <input type="checkbox" name="visible[]" value="<?= $h($param['id']) ?>"
                                                    <?= !$param['is_hidden'] ? 'checked' : '' ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                        <div class="custom-info">
                                            <span class="custom-name"><?= $h($param['name']) ?></span>
                                            <span class="custom-category"><?= $h($param['category']) ?></span>
                                        </div>
                                        <div class="custom-order-group">
                                            <label for="ord-<?= $h($param['id']) ?>" class="order-label">Ordre</label>
                                            <input type="number" id="ord-<?= $h($param['id']) ?>"
                                                name="display_order[<?= $h($param['id']) ?>]"
                                                value="<?= (int) $param['display_order'] ?>"
                                                class="order-input" min="1">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="customization-actions">
                                <button type="submit" class="btn-save">Enregistrer</button>
                            </div>
                        </form>
                    </section>
                </section>
            </main>

            <script src="assets/js/pages/customization-unsaved.js"></script>

            <div id="unsaved-bar" class="unsaved-bar" style="display: none;">
                <p>Modifications non enregistrées</p>
                <button id="save-changes-btn" class="btn-save">Enregistrer</button>
            </div>
        </body>

        </html>
        <?php
    }
}
