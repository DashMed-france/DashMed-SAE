<?php

declare(strict_types=1);

namespace modules\views\user;

/**
 * Class CustomizationView | Vue Personnalisation
 *
 * View for dashboard customization page.
 * Vue de la page de personnalisation du dashboard.
 *
 * @package DashMed\Modules\Views\Pages
 * @author DashMed Team
 * @license Proprietary
 */
final class CustomizationView
{
    /**
     * Displays the customization page.
     * Affiche la page de personnalisation.
     *
     * @param array<int, array{
     * id: string,
     * name: string,
     * category: string,
     * x: int,
     * y: int,
     * w: int,
     * h: int
     * }> $widgets Active widgets | Widgets actifs
     * @param array<int, array{id: string, name: string}> $hidden Hidden widgets | Widgets masqués
     * @return void
     */
    public function show(array $widgets, array $hidden = []): void
    {
        $h = static fn(mixed $v): string => htmlspecialchars(
            is_scalar($v) ? (string) $v : '',
            ENT_QUOTES,
            'UTF-8'
        );
        ?>
        <!DOCTYPE html>
        <html lang="fr">

        <head>
            <meta charset="UTF-8">
            <title>DashMed - Personnalisation</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/themes/dark.css">
            <link rel="stylesheet" href="assets/css/base/style.css">
            <link rel="stylesheet" href="assets/css/pages/dashboard.css">
            <link rel="stylesheet" href="assets/css/layout/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar/searchbar.css">
            <link rel="stylesheet" href="assets/css/pages/dashboard-customize.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@10/dist/gridstack.min.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>
            <main class="container nav-space">
                <section class="dashboard-content-container">
                    <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>
                    <section class="dm-customize">
                        <div class="dm-customize-header">
                            <div>
                                <h1>Personnaliser le tableau de bord</h1>
                                <p>Déplacez, redimensionnez et masquez les widgets.</p>
                            </div>
                            <div class="dm-customize-actions">
                                <button type="button" id="reset-layout-btn"
                                    class="dm-btn dm-btn--secondary">Réinitialiser</button>
                                <button type="submit" form="customize-form" class="dm-btn dm-btn--primary">
                                    Enregistrer
                                </button>
                            </div>
                        </div>
                        <?php if (isset($_GET['success'])): ?>
                            <div class="dm-alert dm-alert--success">Préférences enregistrées.</div>
                        <?php endif; ?>
                        <?php if (!empty($hidden)): ?>
                            <details class="dm-hidden-list" open>
                                <summary>Widgets masqués</summary>
                                <div class="dm-hidden-list-items" id="hidden-widgets-list">
                                    <?php foreach ($hidden as $hw): ?>
                                        <span class="dm-hidden-chip" data-widget-id="<?= $h($hw['id']) ?>">
                                            <?= $h($hw['name']) ?>
                                            <button type="button">+</button></span>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        <?php else: ?>
                            <details class="dm-hidden-list" style="display:none">
                                <summary>Widgets masqués</summary>
                                <div class="dm-hidden-list-items" id="hidden-widgets-list"></div>
                            </details>
                        <?php endif; ?>
                        <form method="POST" action="/?page=customization" id="customize-form">
                            <input type="hidden" name="layout_data" id="layout-data">
                            <input type="hidden" name="reset_layout" id="reset-layout">
                            <div class="grid-stack dm-grid">
                                <?php foreach ($widgets as $w): ?>
                                    <div class="grid-stack-item" gs-x="<?= (int) $w['x'] ?>" gs-y="<?= (int) $w['y'] ?>"
                                        gs-w="<?= max(4, (int) $w['w']) ?>" gs-h="<?= max(3, (int) $w['h']) ?>" gs-min-w="4"
                                        gs-min-h="3" data-widget-id="<?= $h($w['id']) ?>">
                                        <div class="grid-stack-item-content">
                                            <div class="dm-widget">
                                                <div class="dm-widget-header">
                                                    <div>
                                                        <div class="dm-widget-title"><?= $h($w['name']) ?></div>
                                                        <div class="dm-widget-category"><?= $h($w['category']) ?></div>
                                                    </div>
                                                    <div class="dm-widget-controls">
                                                        <span class="dm-widget-grip" title="Déplacer"><svg viewBox="0
                                                                0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2">
                                                                <circle cx="9" cy="5" r="1" />
                                                                <circle cx="15" cy="5" r="1" />
                                                                <circle cx="9" cy="12" r="1" />
                                                                <circle cx="15" cy="12" r="1" />
                                                                <circle cx="9" cy="19" r="1" />
                                                                <circle cx="15" cy="19" r="1" />
                                                            </svg></span>
                                                        <button type="button" class="dm-widget-hide" title="Masquer">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2">
                                                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7
                                                                    0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9
                                                                    4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5
                                                                    18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1
                                                                    1-4.24-4.24" />
                                                                <line x1="1" y1="1" x2="23" y2="23" />
                                                            </svg></button>
                                                    </div>
                                                </div>
                                                <div class="dm-widget-body">
                                                    <div class="dm-widget-value">—</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    </section>
                </section>
            </main>
            <div id="unsaved-bar" class="unsaved-bar" style="display:none;">
                <p>Modifications non enregistrées</p><button id="save-changes-btn"
                    class="dm-btn dm-btn--primary">Enregistrer</button>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/gridstack@10/dist/gridstack-all.js"></script>
            <script src="assets/js/pages/customization-grid.js"></script>
        </body>

        </html>
        <?php
    }
}
