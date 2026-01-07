<?php

namespace modules\views\pages\Monitoring;

/**
 * View dedicated to the full-screen Monitoring page.
 *
 * Displays large-format vital signs monitoring cards.
 * Uses the shared `monitoring-cards.php` component to render cards and charts.
 */
class MonitoringView
{
    /** @var array Patient metrics data ready for display */
    private array $patientMetrics;

    /** @var array List of available chart types [code => label] */
    private array $chartTypes;

    /**
     * Monitoring view constructor.
     *
     * @param array $patientMetrics Processed metrics array (values, statuses, histories).
     * @param array $chartTypes Associative array of available chart types for the configuration menu.
     */
    public function __construct(array $patientMetrics = [], array $chartTypes = [])
    {
        $this->patientMetrics = $patientMetrics;
        $this->chartTypes = $chartTypes;
    }

    /**
     * Generates and displays the monitoring page HTML.
     *
     * @return void
     */
    public function show(): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="fr">

        <head>
            <meta charset="UTF-8">
            <title>DashMed - Monitoring</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/monitoring.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">

            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/components/popup.css">
            <link rel="stylesheet" href="assets/css/components/aside/patient-infos.css">
            <link rel="stylesheet" href="assets/css/components/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/components/modal.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
        <?php include dirname(__DIR__, 2) . '/components/sidebar.php'; ?>

        <main class="container">
            <section class="dashboard-content-container">

                <?php include dirname(__DIR__, 2) . '/components/searchbar.php'; ?>

                <section class="cards-container">
                    <?php
                    $patientMetrics = $this->patientMetrics;
                    $chartTypes = $this->chartTypes;
                    include dirname(__DIR__, 2) . '/components/monitoring-cards.php';
                    ?>
                </section>
        </main>
        <div class="modal" id="cardModal">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <div id="modalDetails"></div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script src="assets/js/component/modal/chart.js"></script>
        <script src="assets/js/component/charts/card-sparklines.js"></script>

        <script src="assets/js/component/modal/navigation.js"></script>
        <script src="assets/js/component/modal/modal.js"></script>
        </body>

        </html>
        <?php
    }
}