<?php

declare(strict_types=1);

namespace modules\views\pages\Monitoring;

/**
 * Vue dédiée à la page de Monitoring plein écran.
 */
class MonitoringView
{
    /** @var array */
    private array $patientMetrics;

    /** @var array */
    private array $chartTypes;

    /**
     * @param array $patientMetrics
     * @param array $chartTypes
     */
    public function __construct(array $patientMetrics = [], array $chartTypes = [])
    {
        $this->patientMetrics = $patientMetrics;
        $this->chartTypes = $chartTypes;
    }

    /**
     * Génère et affiche le code HTML de la page de monitoring.
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

            <!-- iziToast CSS (CDN) -->
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css">

            <!-- Styles DashMed -->
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/monitoring.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/components/popup.css">
            <link rel="stylesheet" href="assets/css/components/aside/patient-infos.css">
            <link rel="stylesheet" href="assets/css/components/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/components/modal.css">
            <link rel="stylesheet" href="assets/css/alerts-toast.css">

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
                </section>
            </main>
            <div class="modal" id="cardModal">
                <div class="modal-content">
                    <span class="close-button">&times;</span>
                    <div id="modalDetails"></div>
                </div>
            </div>

            <!-- Scripts existants -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            <script src="assets/js/component/modal/chart.js"></script>
            <script src="assets/js/component/charts/card-sparklines.js"></script>

            <script src="assets/js/component/modal/navigation.js"></script>
            <script src="assets/js/component/modal/modal.js"></script>

            <?php include dirname(__DIR__, 2) . '/components/global-alerts.php'; ?>
        </body>

        </html>
        <?php
    }
}
