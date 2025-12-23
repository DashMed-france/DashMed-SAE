<?php

namespace modules\views\pages\Monitoring;

class MonitoringView
{
    private array $patientMetrics;

    public function __construct(array $patientMetrics = [])
    {
        $this->patientMetrics = $patientMetrics;
    }

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
            <script src="assets/js/component/modal/navigation.js"></script>
            <script src="assets/js/component/modal/modal.js"></script>
        </body>

        </html>
        <?php
    }
}