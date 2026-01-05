/**
 * ConsultationFilter.js
 * 
 * Logic reusable for sorting and filtering consultation lists.
 * Handles dropdown toggles, date sorting, and status filtering (Past/Future).
 */

class ConsultationManager {
    constructor(config) {
        this.container = document.querySelector(config.containerSelector);
        this.itemSelector = config.itemSelector;
        this.dateAttribute = config.dateAttribute || 'data-date';

        // Sort Elements
        this.sortBtn = document.getElementById(config.sortBtnId);
        this.sortMenu = document.getElementById(config.sortMenuId);
        this.sortOptions = document.querySelectorAll(config.sortOptionSelector);

        // Filter Elements
        this.filterBtn = document.getElementById(config.filterBtnId);
        this.filterMenu = document.getElementById(config.filterMenuId);
        this.filterOptions = document.querySelectorAll(config.filterOptionSelector);

        // State
        this.currentSort = 'desc'; // Default newest first
        this.currentFilter = 'all';

        this.init();
    }

    init() {
        if (!this.container) return;

        // Initialize Sort Dropdown
        if (this.sortBtn && this.sortMenu) {
            this.setupDropdown(this.sortBtn, this.sortMenu);
            this.sortOptions.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const order = btn.getAttribute('data-order');
                    this.applySort(order);
                    this.closeAllMenus();
                });
            });
        }

        // Initialize Filter Dropdown
        if (this.filterBtn && this.filterMenu) {
            this.setupDropdown(this.filterBtn, this.filterMenu);
            this.filterOptions.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    // Determine filter from text content or data attribute
                    const text = btn.textContent.toLowerCase();
                    let filter = 'all';
                    if (text.includes('venir')) filter = 'future';
                    if (text.includes('passé')) filter = 'past';

                    this.applyFilter(filter);
                    this.closeAllMenus();
                });
            });
        }

        // Initial Sort (optional: enforce desc on load)
        this.applySort('desc');

        // Click Outside to close
        document.addEventListener('click', (e) => this.handleOutsideClick(e));
    }

    setupDropdown(btn, menu) {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            const isOpen = menu.style.display === 'block';
            this.closeAllMenus();
            if (!isOpen) {
                menu.style.display = 'block';
            }
        });
    }

    closeAllMenus() {
        if (this.sortMenu) this.sortMenu.style.display = 'none';
        if (this.filterMenu) this.filterMenu.style.display = 'none';
    }

    handleOutsideClick(e) {
        if (this.sortBtn && this.sortMenu) {
            if (!this.sortBtn.contains(e.target) && !this.sortMenu.contains(e.target)) {
                this.sortMenu.style.display = 'none';
            }
        }
        if (this.filterBtn && this.filterMenu) {
            if (!this.filterBtn.contains(e.target) && !this.filterMenu.contains(e.target)) {
                this.filterMenu.style.display = 'none';
            }
        }
    }

    /* Logic */

    parseDate(dateStr) {
        if (!dateStr) return new Date(0); // Fallback to epoch
        // Expecting YYYY-MM-DD
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            return new Date(parts[0], parts[1] - 1, parts[2]);
        }
        return new Date(dateStr);
    }

    getItems() {
        return Array.from(this.container.querySelectorAll(this.itemSelector));
    }

    applySort(order) {
        this.currentSort = order;
        const items = this.getItems();

        items.sort((a, b) => {
            const dateA = this.parseDate(a.getAttribute(this.dateAttribute));
            const dateB = this.parseDate(b.getAttribute(this.dateAttribute));
            return order === 'asc' ? dateA - dateB : dateB - dateA;
        });

        // Re-append to container
        this.container.innerHTML = '';
        items.forEach(item => this.container.appendChild(item));
    }

    applyFilter(filterType) {
        this.currentFilter = filterType;
        const items = this.getItems();
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        let count = 0;
        items.forEach(item => {
            const dateStr = item.getAttribute(this.dateAttribute);
            const itemDate = this.parseDate(dateStr);

            let show = true;
            if (filterType === 'future') {
                show = itemDate >= today;
            } else if (filterType === 'past') {
                show = itemDate < today;
            }

            item.style.display = show ? '' : 'none';
            if (show) count++;
        });

        // Optional: Handle "No results" case if specific UI needed
        console.log(`Filter '${filterType}' applied. ${count} items visible.`);
    }
}
