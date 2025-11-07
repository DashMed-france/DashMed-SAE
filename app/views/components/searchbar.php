<?php
/**
 * DashMed — Barre de recherche
 *
 * Ce fichier définit la section d’en-tête affichée sur les pages connectées Dashmed
 *
 * @package   DashMed\Views
 * @author    Équipe DashMed
 * @license   Propriétaire
 */


?>
<link rel="stylesheet" href="assets/css/components/searchbar/searchbar.css">
<link rel="stylesheet" href="assets/css/components/searchbar/menu.css">

<form class="searchbar" role="search" action="#" method="get">
    <span class="left-icon" aria-hidden="true">
        <img src="assets/img/icons/glass.svg">
    </span>
    <input type="search" name="q" placeholder="Search..." aria-label="Rechercher"/>
    <div class="actions">
        <button type="button" class="action-btn" aria-label="Notifications">
            <img src="assets/img/icons/bell.svg">
        </button>
        <button type="button" class="action-btn avatar-btn" id="profileBtn" aria-haspopup="true" aria-expanded="false" title="Profil">
            <span class="avatar" aria-hidden="true"></span>
        </button>
        <div class="profile-menu" id="profileMenu" role="menu" >
            <!-- Bouton de changement de mode -->
            <button type="button" class="menu-item mode-switch" id="toggleDark">
                <div class="switch">
                    <div class="thumb">
                        <!-- Soleil (light) -->
                        <svg class="sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="5" fill="#facc15"/>
                            <path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"
                                  stroke="#facc15" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <!-- Lune (dark) -->
                        <svg class="moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path fill="#cbd5e1" d="M21 12.79A9 9 0 0 1 11.21 3 7 7 0 1 0 21 12.79z"/>
                        </svg>
                    </div>
                </div>
                <span id="modeLabel" style="margin-left:8px;">Mode sombre</span>
            </button>

            <link id="theme-style" rel="stylesheet" href="/assets/css/themes/light.css">
            <span id="modeLabel"></span>
            <a class="menu-items" role="menuitem">Personnalisation</a>
            <a class="menu-items" role="menuitem">Profil</a>
        </div>
    </div>
</form>
<script src="assets/js/pages/static/profilmenu.js"></script>
<script src="assets/js/pages/dash.js"></script>

