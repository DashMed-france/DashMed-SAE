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
        <a href="/?page=profile">
            <div class="avatar" title="Profil" aria-label="Profil"><img src="" alt=""></div>
        </a>
    </div>
</form>

