document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('custom-grid');
    const sortedInput = document.getElementById('sorted-ids');
    const unsavedBar = document.getElementById('unsaved-bar');

    if (!grid || !sortedInput) return;

    let draggedItem = null;

    function updateSortedInput() {
        const ids = Array.from(grid.querySelectorAll('.custom-card'))
            .map(card => card.getAttribute('data-id'));
        sortedInput.value = ids.join(',');

        if (unsavedBar) {
            unsavedBar.style.display = 'flex';
        }
    }

    updateSortedInput();

    grid.addEventListener('dragstart', (e) => {
        draggedItem = e.target.closest('.custom-card');
        if (draggedItem) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', draggedItem.innerHTML);
            draggedItem.classList.add('dragging');
        }
    });

    grid.addEventListener('dragend', (e) => {
        if (draggedItem) {
            draggedItem.classList.remove('dragging');
            draggedItem = null;

            document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));

            updateSortedInput();
        }
    });

    grid.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const target = e.target.closest('.custom-card');
        if (target && target !== draggedItem) {
            target.classList.add('drag-over');

            const rect = target.getBoundingClientRect();
            const midX = rect.left + rect.width / 2;

            const items = [...grid.children];
            const draggedIndex = items.indexOf(draggedItem);
            const targetIndex = items.indexOf(target);

            if (draggedIndex < targetIndex) {
                grid.insertBefore(draggedItem, target.nextSibling);
            } else {
                grid.insertBefore(draggedItem, target);
            }
        }
    });

    grid.addEventListener('dragleave', (e) => {
        const target = e.target.closest('.custom-card');
        if (target) {
            target.classList.remove('drag-over');
        }
    });

    grid.addEventListener('drop', (e) => {
        e.stopPropagation();
        return false;
    });
});
