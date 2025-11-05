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
        <div class="profile-menu" id="profileMenu" role="menu" aria-hidden="true">
            <button class="menu-item" id="toggleDark" role="menuitem">Mode sombre</button>
            <a class="menu-item" href="/settings" role="menuitem">Personnalisation</a>
            <a class="menu-item" href="/profile" role="menuitem">Profil</a>
        </div>
    </div>
</form>
<script src="assets/js/pages/static/profilmenu.js"></script>

