document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const gridEl = document.querySelector('.dm-grid');
    const layoutInput = document.getElementById('layout-data');
    const hiddenList = document.getElementById('hidden-widgets-list');
    const hiddenDetails = hiddenList?.closest('details');
    const unsavedBar = document.getElementById('unsaved-bar');
    const form = document.getElementById('customize-form');

    if (!gridEl || !layoutInput) {
        return;
    }

    const GRID_CONFIG = {
        column: 12,
        cellHeight: 100,
        margin: 12,
        animate: true,
        float: false,
        disableOneColumnMode: true,
        draggable: { handle: '.dm-widget-grip' },
        resizable: { handles: 'se' },
        minRow: 1
    };

    const grid = GridStack.init(GRID_CONFIG, gridEl);

    const markChanged = () => {
        if (unsavedBar) {
            unsavedBar.style.display = 'flex';
        }
    };

    const escapeHtml = (text) => {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    const serializeLayout = () => {
        const data = [];

        grid.getGridItems().forEach((el) => {
            const node = el.gridstackNode;
            if (node) {
                data.push({
                    id: el.dataset.widgetId,
                    x: node.x,
                    y: node.y,
                    w: node.w,
                    h: node.h,
                    visible: true
                });
            }
        });

        hiddenList?.querySelectorAll('.dm-hidden-chip').forEach((chip) => {
            data.push({
                id: chip.dataset.widgetId,
                x: 0,
                y: 0,
                w: 4,
                h: 3,
                visible: false
            });
        });

        return JSON.stringify(data);
    };

    const updateLayout = () => {
        layoutInput.value = serializeLayout();
    };

    const createWidgetContent = (name) => {
        const safeName = escapeHtml(name);
        return `<div class="dm-widget">
            <div class="dm-widget-header">
                <div><div class="dm-widget-title">${safeName}</div></div>
                <div class="dm-widget-controls">
                    <span class="dm-widget-grip" title="Déplacer">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="5" r="1"/><circle cx="15" cy="5" r="1"/>
                            <circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/>
                            <circle cx="9" cy="19" r="1"/><circle cx="15" cy="19" r="1"/>
                        </svg>
                    </span>
                    <button type="button" class="dm-widget-hide" title="Masquer">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="dm-widget-body"><div class="dm-widget-value">—</div></div>
        </div>`;
    };

    const hideWidget = (item) => {
        if (!item) {
            return;
        }

        const id = item.dataset.widgetId;
        if (!id) {
            return;
        }

        const name = item.querySelector('.dm-widget-title')?.textContent || id;

        grid.removeWidget(item, true);

        if (hiddenList) {
            const chip = document.createElement('span');
            chip.className = 'dm-hidden-chip';
            chip.dataset.widgetId = id;

            const safeName = escapeHtml(name);
            chip.innerHTML = `${safeName} <button type="button" title="Restaurer">+</button>`;

            chip.querySelector('button').addEventListener('click', () => {
                restoreWidget(id, name);
            });

            hiddenList.appendChild(chip);

            if (hiddenDetails) {
                hiddenDetails.style.display = '';
            }
        }

        markChanged();
        updateLayout();
    };

    const restoreWidget = (id, name) => {
        hiddenList?.querySelector(`[data-widget-id="${id}"]`)?.remove();

        if (hiddenList?.children.length === 0 && hiddenDetails) {
            hiddenDetails.style.display = 'none';
        }

        const el = grid.addWidget({
            w: 4,
            h: 3,
            minW: 4,
            minH: 3,
            content: createWidgetContent(name)
        });

        if (el) {
            el.dataset.widgetId = id;

            el.querySelector('.dm-widget-hide')?.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                hideWidget(el);
            });
        }

        markChanged();
        updateLayout();
    };

    grid.on('change added removed', updateLayout);
    grid.on('change', markChanged);

    document.querySelectorAll('.dm-widget-hide').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            hideWidget(btn.closest('.grid-stack-item'));
        });
    });

    hiddenList?.querySelectorAll('.dm-hidden-chip button').forEach((btn) => {
        btn.addEventListener('click', () => {
            const chip = btn.closest('.dm-hidden-chip');
            const name = chip.textContent.trim().replace(/\s*\+$/, '');
            restoreWidget(chip.dataset.widgetId, name);
        });
    });

    document.getElementById('reset-layout-btn')?.addEventListener('click', (e) => {
        e.preventDefault();
        if (confirm('Réinitialiser la disposition par défaut ?')) {
            document.getElementById('reset-layout').value = '1';
            form?.submit();
        }
    });

    document.getElementById('save-changes-btn')?.addEventListener('click', (e) => {
        e.preventDefault();
        updateLayout();
        form?.submit();
    });

    form?.addEventListener('submit', updateLayout);

    updateLayout();
});
