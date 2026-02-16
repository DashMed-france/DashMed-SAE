<?php

declare(strict_types=1);

namespace modules\views\patient;

/**
 * Class MonitoringView
 *
 * Full-screen monitoring page view.
 *
 * Displays vital sign monitoring cards in large format.
 *
 * @package DashMed\Modules\Views\Pages\Monitoring
 * @author DashMed Team
 * @license Proprietary
 */
class MonitoringView
{
    /**
     * @var array<int, array<string, mixed>> Patient metrics ready for display
     */
    private array $patientMetrics;

    /** @var array<string, string> Available chart types [code => label] */
    private array $chartTypes;

    /**
     * Constructor.
     *
     * @param array<int, array<string, mixed>> $patientMetrics Processed metrics
     * @param array<string, string> $chartTypes Available charts
     */
    public function __construct(array $patientMetrics = [], array $chartTypes = [])
    {
        $this->patientMetrics = $patientMetrics;
        $this->chartTypes = $chartTypes;
    }

    /**
     * Renders the monitoring page HTML.
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

            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css">

            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/base/style.css">
            <link rel="stylesheet" href="assets/css/pages/monitoring.css">
            <link rel="stylesheet" href="assets/css/layout/sidebar.css">

            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/components/popup.css">
            <link rel="stylesheet" href="assets/css/layout/aside/patient-info.css">
            <link rel="stylesheet" href="assets/css/layout/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/components/modal.css">
            <link rel="stylesheet" href="assets/css/components/alerts-toast.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <?php include dirname(__DIR__) . '/partials/_sidebar.php'; ?>

            <main class="container">
                <section class="dashboard-content-container">

                    <?php include dirname(__DIR__) . '/partials/_searchbar.php'; ?>

                    <section class="cards-container">
                        <?php
                        $patientMetrics = $this->patientMetrics;
                        $chartTypes = $this->chartTypes;
                        include dirname(__DIR__) . '/partials/_monitoring-cards.php';
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
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            <script src="assets/js/component/modal/chart.js"></script>
            <script src="assets/js/component/charts/card-sparklines.js"></script>

            <script src="assets/js/component/modal/navigation.js"></script>
            <script src="assets/js/component/modal/modal.js"></script>

            <?php include dirname(__DIR__) . '/partials/_global-alerts.php'; ?>
        </body>

        </html>
        <?php
    }
}
