<?php

/**
 * DashMed — Header Component
 *
 * This file defines the header/sidebar section displayed on all DashMed pages.
 * It includes the main navigation links and session-based actions.
 *
 * @package   DashMed\Views
 * @author    DashMed Team
 * @license   Proprietary
 */

$currentPage = $_GET['page'] ?? 'dashboard';

/**
 * Determines if a page name matches the current page and returns the active ID attribute.
 *
 * @param string $pageName Name of the page to check.
 * @param string $current  Currently active page name.
 * @return string Returns 'id="active"' if the page is active, otherwise an empty string.
 */
if (!function_exists('isActive')) {
    function isActive(string $pageName, string $current): string
    {
        return $pageName === $current ? 'id="active"' : '';
    }
}
?>

<link rel="stylesheet" href="assets/css/components/sidebar.css">

<nav>
    <section class="logo">
        <p><span style="color: var(--blacktext-color);">Dash</span><span style="color: var(--primary-color)">Med</span>
        </p>
    </section>

    <section class="tabs">
        <a href="/?page=dashboard" <?= isActive('dashboard', $currentPage) ?>>
            <img src="assets/img/icons/dashboard.svg" class="icon" alt="Dashboard">
        </a>
        <a href="/?page=monitoring" <?= isActive('monitoring', $currentPage) ?>>
            <img src="assets/img/icons/ecg.svg" class="icon" alt="ECG Monitoring">
        </a>
        <a href="/?page=medicalprocedure" <?= isActive('medicalprocedure', $currentPage) ?>>
            <img src="assets/img/icons/patient-record.svg" class="icon" alt="Medical Procedures">
        </a>
        <a href="/?page=patientrecord" <?= isActive('patientrecord', $currentPage) ?>>
            <img src="assets/img/icons/profile.svg" class="icon" alt="Patient Record">
        </a>
    </section>

    <section class="login">
        <?php if (isset($_SESSION['admin_status']) && (int) $_SESSION['admin_status'] === 1): ?>
            <a href="/?page=sysadmin" <?= isActive('sysadmin', $currentPage) ?>>
                <img src="assets/img/icons/admin.svg" class="icon" alt="Administration">
            </a>
        <?php endif; ?>
        <a href="/?page=logout">
            <img src="assets/img/icons/logout.svg" class="icon" alt="Logout">
        </a>
    </section>
</nav>