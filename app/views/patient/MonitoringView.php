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
     * @var array<int, \modules\models\entities\Indicator> Patient metrics ready for display
     */
    private array $patientMetrics;

    /** @var array<string, string> Available chart types [code => label] */
    private array $chartTypes;

    /** @var int|null Context patient ID */
    private ?int $patientId;

    /**
     * Constructor.
     *
     * @param array<int, \modules\models\entities\Indicator> $patientMetrics Processed metrics
     * @param array<string, string> $chartTypes Available charts
     * @param int|null $patientId Patient ID for search context
     */
    public function __construct(array $patientMetrics = [], array $chartTypes = [], ?int $patientId = null)
    {
        $this->patientMetrics = $patientMetrics;
        $this->chartTypes = $chartTypes;
        $this->patientId = $patientId;
    }

    /**
     * Renders the monitoring page HTML.
     *
     * @return void
     */
    public function show(): void
    {
        $layout = new \modules\views\layout\Layout(
            title: 'Monitoring',
            cssFiles: [
                'https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css',
                'assets/css/pages/monitoring.css',
                'assets/css/components/card.css',
                'assets/css/components/popup.css',
                'assets/css/layout/aside/patient-info.css',
                'assets/css/layout/aside/doctor-list.css',
                'assets/css/components/modal.css',
                'assets/css/components/alerts-toast.css',
            ],
            jsFiles: [
                'https://cdn.jsdelivr.net/npm/chart.js',
                'assets/js/component/modal/chart.js',
                'assets/js/component/charts/card-sparklines.js',
                'assets/js/component/modal/navigation.js',
                'assets/js/component/modal/modal.js',
            ],
            showSidebar: true,
            showAlerts: true
        );

        $layout->render(function () {
            ?>

            <main class="container">
                <section class="dashboard-content-container">

                    <?php include dirname(__DIR__) . '/partials/_searchbar.php'; ?>

                    <input type="hidden" id="context-patient-id" value="<?= htmlspecialchars((string) $this->patientId) ?>">

                    <section class="skeleton-wrapper skeleton-monitoring-grid" id="skeleton-monitoring"
                        data-skeleton-for="real-monitoring" data-skeleton-auto data-skeleton-delay="400">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="skeleton-card">
                                <div class="skeleton-card-header">
                                    <div class="skeleton skeleton-text" style="width: 55%; height: 16px;"></div>
                                    <div class="skeleton skeleton-text" style="width: 25%; height: 14px;"></div>
                                </div>
                                <div class="skeleton-card-body">
                                    <div class="skeleton skeleton-text skeleton-text--xl"></div>
                                </div>
                                <div class="skeleton skeleton-card-chart"></div>
                            </div>
                        <?php endfor; ?>
                    </section>

                    <section class="cards-container" id="real-monitoring" style="display: none;">
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

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const hash = window.location.hash;
                    if (!hash.startsWith('#indicateurs-')) return;

                    const target = document.querySelector(hash);
                    if (!target) return;
                });
            </script>

            <?php
        });
    }
}