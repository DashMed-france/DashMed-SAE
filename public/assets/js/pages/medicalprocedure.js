(function() {
    'use strict';

    let sortBtn, sortMenu, sortBtn2, sortMenu2, consultationContainer;

    function parseDateFromText(dateText) {
        const cleanDate = dateText.replace("Date :", "").trim();

        let parts = cleanDate.split('/');
        if (parts.length === 3) {
            return new Date(parts[2], parts[1] - 1, parts[0]);
        }

        parts = cleanDate.split('-');
        if (parts.length === 3) {
            return new Date(parts[0], parts[1] - 1, parts[2]);
        }

        return new Date(cleanDate);
    }

    function toggleMenu(menu, otherMenu) {
        console.log("toggleMenu appelé, état actuel:", menu.style.display);

        if (menu.style.display === "block") {
            menu.style.display = "none";
        } else {
            menu.style.display = "block";
            if (otherMenu) {
                otherMenu.style.display = "none";
            }
        }

        console.log("Nouvel état:", menu.style.display);
    }

    function sortConsultations(order) {
        console.log("Tri demandé:", order);

        const items = Array.from(consultationContainer.querySelectorAll(".consultation"));
        console.log("Nombre d'items à trier:", items.length);

        items.sort((a, b) => {
            const dateTextA = a.querySelector(".consultation-date")?.textContent || "";
            const dateTextB = b.querySelector(".consultation-date")?.textContent || "";
            const dateA = parseDateFromText(dateTextA);
            const dateB = parseDateFromText(dateTextB);
            return order === "asc" ? dateA - dateB : dateB - dateA;
        });

        consultationContainer.innerHTML = '';
        items.forEach(item => consultationContainer.appendChild(item));

        console.log("Tri terminé");
    }

    function filterConsultations(filterType) {
        console.log("Filtre demandé:", filterType);

        const items = Array.from(consultationContainer.querySelectorAll(".consultation"));
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        let countVisible = 0;
        items.forEach(item => {
            const dateText = item.querySelector(".consultation-date")?.textContent || "";
            const itemDate = parseDateFromText(dateText);
            itemDate.setHours(0, 0, 0, 0);

            if (filterType === "all") {
                item.style.display = "block";
                countVisible++;
            } else if (filterType === "future") {
                if (itemDate >= today) {
                    item.style.display = "block";
                    countVisible++;
                } else {
                    item.style.display = "none";
                }
            } else if (filterType === "past") {
                if (itemDate < today) {
                    item.style.display = "block";
                    countVisible++;
                } else {
                    item.style.display = "none";
                }
            }
        });

        console.log("Éléments visibles après filtre:", countVisible + "/" + items.length);
    }

    function attachEvents() {
        console.log("Attachement des événements...");

        sortBtn = document.getElementById("sort-btn");
        sortMenu = document.getElementById("sort-menu");
        sortBtn2 = document.getElementById("sort-btn2");
        sortMenu2 = document.getElementById("sort-menu2");
        consultationContainer = document.querySelector(".consultations-container");

        console.log("Éléments:", {sortBtn, sortMenu, sortBtn2, sortMenu2, consultationContainer});

        if (sortBtn && sortMenu) {
            const newSortBtn = sortBtn.cloneNode(true);
            sortBtn.parentNode.replaceChild(newSortBtn, sortBtn);
            sortBtn = newSortBtn;

            sortBtn.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log(">>> CLIC sur bouton Trier <<<");
                toggleMenu(sortMenu, sortMenu2);
            });
        }

        if (sortBtn2 && sortMenu2) {
            const newSortBtn2 = sortBtn2.cloneNode(true);
            sortBtn2.parentNode.replaceChild(newSortBtn2, sortBtn2);
            sortBtn2 = newSortBtn2;

            sortBtn2.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log(">>> CLIC sur bouton Options <<<");
                toggleMenu(sortMenu2, sortMenu);
            });
        }

        if (sortMenu && consultationContainer) {
            document.querySelectorAll(".sort-option").forEach(btn => {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const order = btn.getAttribute("data-order");
                    sortConsultations(order);
                    sortMenu.style.display = "none";
                });
            });
        }

        if (sortMenu2 && consultationContainer) {
            document.querySelectorAll(".sort-option2").forEach(btn => {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const filterText = btn.textContent.trim().toLowerCase();
                    let filterType = "all";

                    if (filterText.includes("venir")) {
                        filterType = "future";
                    } else if (filterText.includes("passé")) {
                        filterType = "past";
                    }

                    filterConsultations(filterType);
                    sortMenu2.style.display = "none";
                });
            });
        }

        document.addEventListener("click", function(e) {
            if (sortMenu && sortBtn && !sortBtn.contains(e.target) && !sortMenu.contains(e.target)) {
                sortMenu.style.display = "none";
            }
            if (sortMenu2 && sortBtn2 && !sortBtn2.contains(e.target) && !sortMenu2.contains(e.target)) {
                sortMenu2.style.display = "none";
            }
        });
    }

    function init() {
        console.log("Initialisation...");

        consultationContainer = document.querySelector(".consultations-container");

        if (consultationContainer) {
            const items = Array.from(consultationContainer.querySelectorAll(".consultation"));
            console.log("Nombre de consultations trouvées:", items.length);

            if (items.length > 0) {
                items.sort((a, b) => {
                    const dateTextA = a.querySelector(".consultation-date")?.textContent || "";
                    const dateTextB = b.querySelector(".consultation-date")?.textContent || "";
                    const dateA = parseDateFromText(dateTextA);
                    const dateB = parseDateFromText(dateTextB);
                    return dateA - dateB;
                });
                consultationContainer.innerHTML = '';
                items.forEach(item => consultationContainer.appendChild(item));
            }
        }
        attachEvents();

        function scrollToHash() {
            const hash = window.location.hash;
            if(hash) {
                const id = hash.substring(1);
                const elem = document.getElementById(id);
                if(elem) {
                    const offset = document.querySelector('header')?.offsetHeight || 100;
                    const elemPosition = elem.getBoundingClientRect().top + window.scrollY;
                    window.scrollTo({ top: elemPosition - offset, behavior: 'smooth' });
                }
            }
        }
        scrollToHash();
        window.addEventListener('hashchange', scrollToHash);

        console.log("=== Initialisation terminée ===");
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();