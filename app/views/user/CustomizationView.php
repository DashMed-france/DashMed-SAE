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
     *   id: string,
     *   name: string,
     *   category: string,
     *   x: int,
     *   y: int,
     *   w: int,
     *   h: int
     * }> $widgets Active widgets
     * @param array<int, array{id: string, name: string}> $hidden Hidden widgets
     * @param array<int, array{parameter_id: string, display_name: string, category: string}> $allParameters
     * @param array<int, array{id: int, name: string, color: string, indicator_ids: array<int, string>}> $existingGroups
     * @param array{group: array{id: int, name: string, color: string}, indicators: array<int, array>}|null $editGroupData Edit group data
     * @return void
     */
    public function show(
        array $widgets,
        array $hidden = [],
        array $allParameters = [],
        array $existingGroups = [],
        ?array $editGroupData = null
    ): void {
        $h = static fn(mixed $v): string => htmlspecialchars(
            is_scalar($v) ? (string) $v : '',
            ENT_QUOTES,
            'UTF-8'
        );

        $activeTab = 'layout';
        $rawTab = $_GET['tab'] ?? '';
        if (is_string($rawTab) && in_array($rawTab, ['layout', 'add_group', 'my_groups', 'edit_group'], true)) {
            $activeTab = $rawTab;
        }

        /** @var array{type: string, text: string}|null $groupMsg */
        $groupMsg = isset($_SESSION['group_msg']) && is_array($_SESSION['group_msg'])
            ? $_SESSION['group_msg']
            : null;
        unset($_SESSION['group_msg']);

        $groupsByCategory = [];
        foreach ($allParameters as $param) {
            $cat = $param['category'] ?? 'Autre';
            $groupsByCategory[$cat][] = $param;
        }
        ksort($groupsByCategory);

        $existingGroupNames = array_map(fn($g) => $g['name'], $existingGroups);
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
            <?php include dirname(__DIR__) . '/partials/_sidebar.php'; ?>
            <main class="container nav-space">
                <section class="dashboard-content-container">
                    <?php include dirname(__DIR__) . '/partials/_searchbar.php'; ?>
                    <section class="dm-customize">
                        <div class="dm-customize-header">
                            <div>
                                <h1>Personnaliser le tableau de bord</h1>
                                <p>Déplacez, redimensionnez, masquez les widgets ou créez vos groupes.</p>
                            </div>
                            <div class="dm-customize-actions" id="dm-layout-actions" <?= $activeTab !== 'layout' ? 'style="display:none"' : '' ?>>
                                <button type="button" id="reset-layout-btn"
                                    class="dm-btn dm-btn--secondary">Réinitialiser</button>
                                <button type="submit" form="customize-form" class="dm-btn dm-btn--primary">Enregistrer</button>
                            </div>
                        </div>

                        <div class="dm-tabs">
                            <button type="button" class="dm-tab-label <?= $activeTab === 'layout' ? 'active' : '' ?>"
                                data-target="tab-layout">Disposition principale</button>
                            <div class="category-vert-separator"></div>
                            <button type="button" class="dm-tab-label <?= $activeTab === 'my_groups' ? 'active' : '' ?>"
                                data-target="tab-my_groups">
                                Mes
                                groupes<?= !empty($existingGroups) ? ' <span class="dm-tab-count">' . count($existingGroups) . '</span>' : '' ?>
                            </button>
                            <div class="category-vert-separator"></div>
                            <button type="button"
                                class="dm-tab-label dm-tab-add <?= $activeTab === 'add_group' ? 'active' : '' ?>"
                                data-target="tab-add_group" style="color: var(--text-brand, #3b82f6);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                                    class="tab-icon-plus">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Ajouter un groupe
                            </button>
                        </div>

                        <?php if ($groupMsg !== null): ?>
                            <div class="dm-alert dm-alert--<?= $groupMsg['type'] === 'success' ? 'success' : 'error' ?>">
                                <?= $h($groupMsg['text']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="dm-alert dm-alert--success">Préférences enregistrées.</div>
                        <?php endif; ?>

                        <div id="tab-layout" class="dm-tab-content" <?= $activeTab !== 'layout' ? 'style="display:none;"' : '' ?>>
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
                        </div>

                        <div id="tab-add_group" class="dm-tab-content" <?= $activeTab !== 'add_group' ? 'style="display:none;"' : '' ?>>
                            <div class="dm-group-form-wrap">
                                <form method="POST" action="/?page=custom_group" id="create-group-form" novalidate>
                                    <input type="hidden" name="action" value="create_group">
                                    <div class="dm-field">
                                        <label for="group_name">Nom du groupe</label>
                                        <input type="text" id="group_name" name="group_name" maxlength="100" autocomplete="off"
                                            placeholder="Ex : Cardio avancé" required>
                                        <span class="dm-field-error" id="name-error"></span>
                                    </div>
                                    <div class="dm-field" style="margin-top:15px; margin-bottom:15px;">
                                        <label for="group_color">Couleur de l'onglet</label>
                                        <input type="color" id="group_color" name="group_color" value="#3b82f6"
                                            style="cursor:pointer; width:60px; height:40px; padding:0; border:1px solid var(--border-subtle); border-radius:4px;">
                                    </div>
                                    <div class="dm-field">
                                        <label>Indicateurs du groupe</label>

                                        <!-- Zone de dépôt des indicateurs sélectionnés -->
                                        <div class="dm-hidden-list" style="margin-bottom: 15px;">
                                            <div class="dm-hidden-list-title">Indicateurs sélectionnés</div>
                                            <div class="dm-hidden-list-items" id="selected-indicators-list">
                                                <p class="dm-no-groups" id="no-indicators-msg"
                                                    style="margin: 0; padding: 5px 0; font-size: 0.85rem;">Aucun indicateur
                                                    sélectionné.</p>
                                            </div>
                                        </div>

                                        <!-- Liste des indicateurs disponibles (style hidden chips) -->
                                        <label>Indicateurs disponibles</label>
                                        <div class="dm-indicators-library">
                                            <?php foreach ($groupsByCategory as $cat => $params): ?>
                                                <div class="dm-indicator-category">
                                                    <span class="dm-indicator-cat-label"><?= $h($cat) ?></span>
                                                    <div class="dm-hidden-list-items" style="margin-top: 8px;">
                                                        <?php foreach ($params as $param): ?>
                                                            <span class="dm-hidden-chip indicator-available-chip"
                                                                data-id="<?= $h($param['parameter_id']) ?>"
                                                                data-name="<?= $h($param['display_name']) ?>">
                                                                <?= $h($param['display_name']) ?>
                                                                <button type="button" class="add-indicator-btn">+</button>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <span class="dm-field-error" id="indicators-error"></span>
                                        <div id="hidden-inputs-container"></div>
                                    </div>
                                    <div class="dm-customize-actions">
                                        <button type="submit" class="dm-btn dm-btn--primary" id="create-group-btn">
                                            Créer le groupe
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div id="tab-my_groups" class="dm-tab-content" <?= $activeTab !== 'my_groups' ? 'style="display:none;"' : '' ?>>
                            <div class="dm-groups-list-wrap">
                                <?php if (empty($existingGroups)): ?>
                                    <p class="dm-no-groups">Aucun groupe personnalisé créé.</p>
                                <?php else: ?>
                                    <table class="dm-groups-table">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Indicateurs</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($existingGroups as $group): ?>
                                                <tr>
                                                    <td><?= $h($group['name']) ?></td>
                                                    <td><?= count($group['indicator_ids']) ?>
                                                        indicateur<?= count($group['indicator_ids']) > 1 ? 's' : '' ?></td>
                                                    <td>
                                                        <div style="display:flex; gap:10px; justify-content: flex-end;">
                                                            <a href="/?page=customization&tab=edit_group&id=<?= (int) $group['id'] ?>"
                                                                class="dm-btn dm-btn--secondary">Editer</a>
                                                            <form method="POST" action="/?page=custom_group"
                                                                onsubmit="return confirm('Supprimer le groupe « <?= $h($group['name']) ?> » ?')">
                                                                <input type="hidden" name="action" value="delete_group">
                                                                <input type="hidden" name="group_id" value="<?= (int) $group['id'] ?>">
                                                                <button type="submit" class="dm-btn dm-btn--danger">Supprimer</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($editGroupData !== null):
                            $eg = $editGroupData['group'];
                            $egInds = $editGroupData['indicators'];
                            ?>
                            <div id="tab-edit_group" class="dm-tab-content" <?= $activeTab !== 'edit_group' ? 'style="display:none;"' : '' ?>>
                                <form method="POST" action="/?page=custom_group" id="edit-group-form" novalidate>
                                    <input type="hidden" name="action" value="edit_group">
                                    <input type="hidden" name="group_id" value="<?= $h($eg['id']) ?>">
                                    <input type="hidden" name="layout_data" id="edit-layout-data">

                                    <div class="dm-group-form-wrap">
                                        <div class="dm-field">
                                            <label for="edit_group_name">Nom du groupe</label>
                                            <input type="text" id="edit_group_name" name="group_name" maxlength="100"
                                                autocomplete="off" value="<?= $h($eg['name']) ?>" required>
                                            <span class="dm-field-error" id="edit-name-error"></span>
                                        </div>
                                        <div class="dm-field" style="margin-top:15px; margin-bottom:15px;">
                                            <label for="edit_group_color">Couleur de l'onglet</label>
                                            <input type="color" id="edit_group_color" name="group_color"
                                                value="<?= $h($eg['color']) ?>"
                                                style="cursor:pointer; width:60px; height:40px; padding:0; border:1px solid var(--border-subtle); border-radius:4px;">
                                        </div>

                                        <div class="dm-field">
                                            <label>Indicateurs du groupe</label>

                                            <!-- Zone de dépôt des indicateurs sélectionnés -->
                                            <div class="dm-hidden-list" style="margin-bottom: 15px;">
                                                <div class="dm-hidden-list-title">Indicateurs sélectionnés</div>
                                                <div class="dm-hidden-list-items" id="edit-selected-indicators-list">
                                                    <p class="dm-no-groups" id="edit-no-indicators-msg"
                                                        style="margin: 0; padding: 5px 0; font-size: 0.85rem;">Aucun indicateur
                                                        sélectionné.</p>
                                                </div>
                                            </div>

                                            <!-- Liste des indicateurs disponibles (style hidden chips) -->
                                            <label>Indicateurs disponibles</label>
                                            <div class="dm-indicators-library">
                                                <?php foreach ($groupsByCategory as $cat => $params): ?>
                                                    <div class="dm-indicator-category">
                                                        <span class="dm-indicator-cat-label"><?= $h($cat) ?></span>
                                                        <div class="dm-hidden-list-items" style="margin-top: 8px;">
                                                            <?php foreach ($params as $param): ?>
                                                                <span class="dm-hidden-chip edit-indicator-available-chip"
                                                                    data-id="<?= $h($param['parameter_id']) ?>"
                                                                    data-name="<?= $h($param['display_name']) ?>"
                                                                    data-cat="<?= $h($cat) ?>">
                                                                    <?= $h($param['display_name']) ?>
                                                                    <button type="button" class="edit-add-indicator-btn">+</button>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <span class="dm-field-error" id="edit-indicators-error"></span>
                                            <div id="edit-hidden-inputs-container"></div>
                                        </div>
                                    </div>

                                    <div class="dm-field" style="margin-top:20px;">
                                        <label>Disposition au sein du groupe</label>
                                        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:10px;">Réorganisez
                                            l'affichage des widgets pour ce groupe en les déplaçant ci-dessous.</p>
                                        <div class="grid-stack dm-grid" id="edit-group-grid">
                                            <?php foreach ($egInds as $w): ?>
                                                <div class="grid-stack-item group-grid-item" gs-x="<?= (int) $w['x'] ?>"
                                                    gs-y="<?= (int) $w['y'] ?>" gs-w="<?= max(4, (int) $w['w']) ?>"
                                                    gs-h="<?= max(3, (int) $w['h']) ?>" gs-min-w="4" gs-min-h="3"
                                                    data-widget-id="<?= $h($w['id']) ?>">
                                                    <div class="grid-stack-item-content">
                                                        <div class="dm-widget">
                                                            <div class="dm-widget-header">
                                                                <div>
                                                                    <div class="dm-widget-title"><?= $h($w['name']) ?></div>
                                                                    <div class="dm-widget-category"><?= $h($w['category']) ?></div>
                                                                </div>
                                                                <div class="dm-widget-controls">
                                                                    <span class="dm-widget-grip" title="Déplacer"><svg
                                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                            stroke-width="2">
                                                                            <circle cx="9" cy="5" r="1" />
                                                                            <circle cx="15" cy="5" r="1" />
                                                                            <circle cx="9" cy="12" r="1" />
                                                                            <circle cx="15" cy="12" r="1" />
                                                                            <circle cx="9" cy="19" r="1" />
                                                                            <circle cx="15" cy="19" r="1" />
                                                                        </svg></span>
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
                                    </div>

                                    <div class="dm-customize-actions">
                                        <button type="submit" class="dm-btn dm-btn--primary" id="edit-group-btn">
                                            Enregistrer les modifications
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <script>
                                window.preloadedEditIndicators = <?= json_encode($egInds) ?>;
                            </script>
                        <?php endif; ?>

                    </section>
                </section>
            </main>

            <div id="unsaved-bar" class="unsaved-bar" style="display:none;">
                <p>Modifications non enregistrées</p>
                <button id="save-changes-btn" class="dm-btn dm-btn--primary">Enregistrer</button>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/gridstack@10/dist/gridstack-all.js"></script>
            <script src="assets/js/pages/customization-grid.js"></script>

            <script>
                (function () {
                    const tabLabels = document.querySelectorAll('.dm-tab-label');
                    const tabContents = document.querySelectorAll('.dm-tab-content');
                    const layoutActions = document.getElementById('dm-layout-actions');

                    tabLabels.forEach(label => {
                        label.addEventListener('click', () => {
                            if (label.tagName !== 'BUTTON') return;

                            tabLabels.forEach(l => l.classList.remove('active'));
                            tabContents.forEach(c => c.style.display = 'none');

                            label.classList.add('active');
                            const targetId = label.getAttribute('data-target');
                            const targetContent = document.getElementById(targetId);
                            if (targetContent) {
                                targetContent.style.display = 'block';
                            }

                            if (targetId === 'tab-layout') {
                                layoutActions.style.display = 'flex';
                            } else {
                                layoutActions.style.display = 'none';
                            }
                        });
                    });

                    const urlParams = new URLSearchParams(window.location.search);
                    const tabParam = urlParams.get('tab');
                    if (tabParam) {
                        const targetLabel = document.querySelector(`.dm-tab-label[data-target="tab-${tabParam}"]`);
                        if (targetLabel) targetLabel.click();
                    }

                    const existingNames = <?= json_encode(array_values($existingGroupNames)) ?>;
                    const form = document.getElementById('create-group-form');
                    const nameInput = document.getElementById('group_name');
                    const nameError = document.getElementById('name-error');
                    const indicatorsError = document.getElementById('indicators-error');

                    const availableChips = document.querySelectorAll('.indicator-available-chip');
                    const selectedList = document.getElementById('selected-indicators-list');
                    const noIndicatorsMsg = document.getElementById('no-indicators-msg');
                    const hiddenInputsContainer = document.getElementById('hidden-inputs-container');

                    let selectedIndicators = new Map();

                    availableChips.forEach(chip => {
                        const btn = chip.querySelector('.add-indicator-btn');
                        btn.addEventListener('click', () => {
                            const id = chip.getAttribute('data-id');
                            const name = chip.getAttribute('data-name');

                            if (selectedIndicators.has(id)) return;

                            selectedIndicators.set(id, name);
                            chip.style.opacity = '0.5';
                            chip.style.pointerEvents = 'none';
                            chip.querySelector('.add-indicator-btn').style.display = 'none';

                            updateSelectedIndicators();
                        });
                    });

                    function updateSelectedIndicators() {
                        if (selectedIndicators.size === 0) {
                            noIndicatorsMsg.style.display = 'block';
                        } else {
                            noIndicatorsMsg.style.display = 'none';
                        }

                        const chips = selectedList.querySelectorAll('.dm-hidden-chip');
                        chips.forEach(c => c.remove());
                        hiddenInputsContainer.innerHTML = '';

                        selectedIndicators.forEach((name, id) => {
                            const chip = document.createElement('span');
                            chip.className = 'dm-hidden-chip';
                            chip.innerHTML = `${name} <button type="button" class="remove-indicator-btn" data-id="${id}">×</button>`;
                            selectedList.appendChild(chip);

                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'indicators[]';
                            input.value = id;
                            hiddenInputsContainer.appendChild(input);

                            chip.querySelector('.remove-indicator-btn').addEventListener('click', (e) => {
                                const removeId = e.target.getAttribute('data-id');
                                selectedIndicators.delete(removeId);

                                const available = document.querySelector(`.indicator-available-chip[data-id="${removeId}"]`);
                                if (available) {
                                    available.style.opacity = '1';
                                    available.style.pointerEvents = 'auto';
                                    available.querySelector('.add-indicator-btn').style.display = 'inline-block';
                                }

                                updateSelectedIndicators();
                            });
                        });
                    }

                    form.addEventListener('submit', function (e) {
                        let valid = true;
                        nameError.textContent = '';
                        indicatorsError.textContent = '';

                        const name = nameInput.value.trim();
                        if (!name) {
                            nameError.textContent = 'Le nom est obligatoire.';
                            valid = false;
                        } else if (existingNames.map(n => n.toLowerCase()).includes(name.toLowerCase())) {
                            nameError.textContent = 'Ce nom est déjà utilisé.';
                            valid = false;
                        }

                        if (selectedIndicators.size === 0) {
                            indicatorsError.textContent = 'Sélectionnez au moins un indicateur.';
                            valid = false;
                        }

                        if (!valid) e.preventDefault();
                    });

                    /* ── Edit-group tab ── */
                    const editForm = document.getElementById('edit-group-form');
                    if (editForm) {
                        const editGridManager = window._editGridManager || null;
                        const editGridEl = document.getElementById('edit-group-grid');

                        const editAvailableChips = document.querySelectorAll('.edit-indicator-available-chip');
                        const editSelectedList = document.getElementById('edit-selected-indicators-list');
                        const editNoIndicatorsMsg = document.getElementById('edit-no-indicators-msg');
                        const editHiddenInputsContainer = document.getElementById('edit-hidden-inputs-container');
                        const editLayoutData = document.getElementById('edit-layout-data');

                        let editSelectedIndicators = new Map();

                        function updateEditSelectedIndicators(isPreloading = false, addedId = null) {
                            editNoIndicatorsMsg.style.display = editSelectedIndicators.size === 0 ? 'block' : 'none';

                            const chips = editSelectedList.querySelectorAll('.dm-hidden-chip');
                            chips.forEach(c => c.remove());
                            editHiddenInputsContainer.innerHTML = '';

                            editSelectedIndicators.forEach((data, id) => {
                                const chip = document.createElement('span');
                                chip.className = 'dm-hidden-chip';
                                chip.innerHTML = `${data.name} <button type="button" class="remove-indicator-btn" data-id="${id}">×</button>`;
                                editSelectedList.appendChild(chip);

                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'indicators[]';
                                input.value = id;
                                editHiddenInputsContainer.appendChild(input);

                                chip.querySelector('.remove-indicator-btn').addEventListener('click', (e) => {
                                    const removeId = e.target.getAttribute('data-id');
                                    editSelectedIndicators.delete(removeId);

                                    const available = document.querySelector(`.edit-indicator-available-chip[data-id="${removeId}"]`);
                                    if (available) {
                                        available.style.opacity = '1';
                                        available.style.pointerEvents = 'auto';
                                        available.querySelector('.edit-add-indicator-btn').style.display = 'inline-block';
                                    }

                                    if (editGridManager && editGridEl) {
                                        const gridItem = editGridEl.querySelector(`.group-grid-item[data-widget-id="${removeId}"]`);
                                        if (gridItem) editGridManager.grid.removeWidget(gridItem);
                                    }
                                    updateEditSelectedIndicators();
                                });

                                if (!isPreloading && addedId === id && editGridManager && window.DmGrid) {
                                    const el = editGridManager.addWidget({
                                        w: 4, h: 3, minW: 4, minH: 3,
                                        content: window.DmGrid.createWidgetContent(data.name, data.cat)
                                    });
                                    if (el) {
                                        el.dataset.widgetId = id;
                                        el.classList.add('group-grid-item');
                                    }
                                }
                            });
                        }

                        if (window.preloadedEditIndicators) {
                            window.preloadedEditIndicators.forEach(w => {
                                editSelectedIndicators.set(w.id, { name: w.name, cat: w.category });
                                const chip = document.querySelector(`.edit-indicator-available-chip[data-id="${w.id}"]`);
                                if (chip) {
                                    chip.style.opacity = '0.5';
                                    chip.style.pointerEvents = 'none';
                                    chip.querySelector('.edit-add-indicator-btn').style.display = 'none';
                                }
                            });
                            updateEditSelectedIndicators(true);
                        }

                        editAvailableChips.forEach(chip => {
                            const btn = chip.querySelector('.edit-add-indicator-btn');
                            btn.addEventListener('click', () => {
                                const id = chip.getAttribute('data-id');
                                const name = chip.getAttribute('data-name');
                                const cat = chip.getAttribute('data-cat');

                                if (editSelectedIndicators.has(id)) return;

                                editSelectedIndicators.set(id, { name, cat });
                                chip.style.opacity = '0.5';
                                chip.style.pointerEvents = 'none';
                                btn.style.display = 'none';

                                updateEditSelectedIndicators(false, id);
                            });
                        });

                        editForm.addEventListener('submit', function (e) {
                            let valid = true;
                            const nameErr = document.getElementById('edit-name-error');
                            const indErr = document.getElementById('edit-indicators-error');
                            nameErr.textContent = '';
                            indErr.textContent = '';

                            if (!document.getElementById('edit_group_name').value.trim()) {
                                nameErr.textContent = 'Le nom est obligatoire.';
                                valid = false;
                            }

                            if (editSelectedIndicators.size === 0) {
                                indErr.textContent = 'Sélectionnez au moins un indicateur.';
                                valid = false;
                            }

                            if (editGridManager) {
                                editGridManager.updateLayout();
                            }

                            if (!valid) e.preventDefault();
                        });
                    }
                })();
            </script>

        </body>

        </html>
        <?php
    }
}
