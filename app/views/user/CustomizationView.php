<?php

declare(strict_types=1);

namespace modules\views\user;

/**
 * Class CustomizationView
 *
 * View for dashboard customization page.
 *
 * @package DashMed\Modules\Views\Pages
 * @author DashMed Team
 * @license Proprietary
 */
final class CustomizationView
{
    /**
     * Displays the customization page.
     *
     * @param array<int, array{
     * id: string,
     * name: string,
     * category: string,
     * x: int,
     * y: int,
     * w: int,
     * h: int
     * }> $widgets Active widgets
     * @param array<int, array{id: string, name: string}> $hidden Hidden widgets
     * @return void
     */
    public function show(array $widgets, array $hidden = []): void
    {
        $h = static fn(mixed $v): string => htmlspecialchars(
            is_scalar($v) ? (string) $v : '',
            ENT_QUOTES,
            'UTF-8'
        );

        $layout = new \modules\views\layout\Layout(
            title: 'Personnalisation',
            cssFiles: [
                'assets/css/pages/dashboard.css',
                'assets/css/components/searchbar/searchbar.css',
                'assets/css/pages/dashboard-customize.css',
                'https://cdn.jsdelivr.net/npm/gridstack@10/dist/gridstack.min.css',
            ],
            jsFiles: [
                'https://cdn.jsdelivr.net/npm/gridstack@10/dist/gridstack-all.js',
                'assets/js/pages/customization-grid.js',
            ],
            showSidebar: true,
            showAlerts: false
        );

        $layout->render(function () use ($widgets, $hidden, $h) {
            ?>

            <main class="container nav-space">
                <section class="dashboard-content-container">
                    <?php include dirname(__DIR__) . '/partials/_searchbar.php'; ?>
                    <div class="skeleton-wrapper" id="skeleton-customization" data-skeleton-for="real-customization"
                        data-skeleton-auto data-skeleton-delay="400">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <div class="skeleton skeleton-text skeleton-text--xl" style="width: 300px;"></div>
                                <div class="skeleton skeleton-text" style="width: 280px;"></div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <div class="skeleton skeleton-btn" style="width: 120px;"></div>
                                <div class="skeleton skeleton-btn" style="width: 120px;"></div>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                            <?php for ($cw = 0; $cw < 6; $cw++): ?>
                                <div class="skeleton-widget">
                                    <div class="skeleton-widget-header">
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <div class="skeleton skeleton-text" style="width: 120px; height: 14px;"></div>
                                            <div class="skeleton skeleton-text skeleton-text--sm" style="width: 80px;"></div>
                                        </div>
                                        <div style="display: flex; gap: 6px;">
                                            <div class="skeleton skeleton-circle" style="width: 24px; height: 24px;"></div>
                                            <div class="skeleton skeleton-circle" style="width: 24px; height: 24px;"></div>
                                        </div>
                                    </div>
                                    <div class="skeleton skeleton-widget-body"></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div id="real-customization" style="display: none;">

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

                    </div>
                </section>
            </main>
            <div id="unsaved-bar" class="unsaved-bar" style="display:none;">
                <p>Modifications non enregistrées</p><button id="save-changes-btn"
                    class="dm-btn dm-btn--primary">Enregistrer</button>
            </div>

            <?php
        });
    }
}
