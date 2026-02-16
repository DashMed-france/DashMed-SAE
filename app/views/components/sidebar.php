<?php

/**
 * DashMed — Header Component
 *
 * This file defines the header section displayed across all DashMed pages.
 * It may include the current page title, notifications, or user information.
 *
 * @package   DashMed\Views
 * @author    DashMed Team
 * @license   Proprietary
 */

$currentPage = $_GET['page'] ?? 'dashboard';

/**
 * Determines if a page name corresponds to the current page and returns the active ID attribute.
 *
 * @param string $pageName Page name to check.
 * @param string $current  Currently active page.
 * @return string Returns 'id="active"' if the page is active, otherwise an empty string.
 */
$isActive = static function (string $pageName, string $current): string {
    return $pageName === $current ? 'id="active"' : '';
};
$rawPage = $_GET['page'] ?? 'dashboard';
$currentPage = is_string($rawPage) ? $rawPage : 'dashboard';
?>

<link rel="stylesheet" href="assets/css/layout/sidebar.css">

<nav>
    <section class="logo">
        <p><span style="color: var(--blacktext-color);">Dash</span><span style="color: var(--primary-color)">Med</span>
        </p>
    </section>

    <section class="tabs">
        <a href="/?page=dashboard" <?= $isActive('dashboard', $currentPage) ?>>
            <img src="assets/img/icons/dashboard.svg" class="icon" alt="Dashboard">
        </a>
        <a href="/?page=monitoring" <?= $isActive('monitoring', $currentPage) ?>>
            <img src="assets/img/icons/ecg.svg" class="icon" alt="Surveillance ECG">
        </a>
        <a href="/?page=medicalprocedure" <?= $isActive('medicalprocedure', $currentPage) ?>>
            <img src="assets/img/icons/patient-record.svg" class="icon" alt="Dossier patient">
        </a>
        <a href="/?page=patientrecord" <?= $isActive('patientrecord', $currentPage) ?>>
            <img src="assets/img/icons/folder.svg" class="icon" alt="Dossier patient">
        </a>
    </section>

    <section class="login">
        <?php
        $adminStatus = $_SESSION['admin_status'] ?? 0;
        if (is_numeric($adminStatus) && (int) $adminStatus === 1):
            ?>
            <a href="/?page=sysadmin" <?= $isActive('sysadmin', $currentPage) ?>>
                <img src="assets/img/icons/admin.svg" class="icon" alt="Administration">
            </a>
        <?php endif; ?>
        <a href="/?page=logout">
            <img src="assets/img/icons/logout.svg" class="icon" alt="Déconnexion">
        </a>
    </section>
</nav>