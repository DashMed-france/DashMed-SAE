<?php
/**
 * DashMed — Composant d’en-tête
 *
 * Ce fichier définit la section d’en-tête affichée sur l’ensemble des pages de DashMed.
 * Elle peut inclure le titre de la page actuelle, les notifications ou les informations de l’utilisateur.
 *
 * @package   DashMed\Views
 * @author    Équipe DashMed
 * @license   Propriétaire
 */

$currentPage = $_GET['page'] ?? 'dashboard';

/**
 * Détermine si un nom de page correspond à la page actuelle et renvoie l’attribut d’ID actif.
 *
 * @param string $pageName Nom de la page à vérifier.
 * @param string $current  Page actuellement active.
 * @return string Renvoie 'id="active"' si la page est active, sinon une chaîne vide.
 */
function isActive(string $pageName, string $current): string {
    return $pageName === $current ? 'id="active"' : '';
}
?>

<nav>
    <section class="logo">
        <p><span id="dash">Dash</span><span style="color: var(--primary-color)">Med</span></p>
    </section>

    <section class="tabs">
        <a href="/?page=dashboard" <?= isActive('dashboard', $currentPage) ?>>
            <img src="assets/img/icons/dashboard.svg" alt="Dashboard">
        </a>
        <a href="/?page=monitoring" <?= isActive('monitoring', $currentPage) ?>>
            <img src="assets/img/icons/ecg.svg" alt="Surveillance ECG">
        </a>
        <a href="/?page=dossierpatient" <?= isActive('patients', $currentPage) ?>>
            <img src="assets/img/icons/patient-record.svg" alt="Dossier patient">
        </a>
    </section>

    <section class="login">
        <a href="/?page=logout">
            <img src="assets/img/icons/logout.svg" alt="Déconnexion">
        </a>
    </section>
</nav>
