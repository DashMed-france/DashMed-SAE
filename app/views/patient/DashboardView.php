<?php

namespace modules\views\patient;

/**
 * Class DashboardView
 *
 * View for the main dashboard.
 *
 * Main entry point for authenticated doctors. Aggregates vital metrics,
 * past/future consultations, and room selection.
 *
 * @package DashMed\Modules\Views\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class DashboardView
{
    /** @var array<int, \modules\models\entities\Consultation> Past consultations list */
    private array $consultationsPassees;

    /** @var array<int, \modules\models\entities\Consultation> Future consultations list */
    private array $consultationsFutures;

    /** @var array<int, array{
     *   room_id: int|string,
     *   first_name?: string
     * }> Rooms list with patients */
    private array $rooms;

    /** @var array<int, \modules\models\entities\Indicator|array<string, mixed>> Selected patient metrics */
    private array $patientMetrics;

    /** @var array<string, mixed> Selected patient data */
    private array $patientData;

    /** @var array<string, mixed> Chart types config */
    private array $chartTypes;

    /** @var array<string, mixed> User layout preferences */
    private array $userLayout;

    /**
     * Constructor.
     *
     * Initializes dashboard with contextual data.
     *
     * @param array<int, \modules\models\entities\Consultation> $consultationsPassees History
     * @param array<int, \modules\models\entities\Consultation> $consultationsFutures Appointments
     * @param array<int, array{
     *   room_id: int|string,
     *   first_name?: string
     * }> $rooms Occupied rooms
     * @param array<int, \modules\models\entities\Indicator|array<string, mixed>> $patientMetrics Health data
     * @param array<string, mixed> $patientData Patient info
     * @param array<string, mixed> $chartTypes Visualizations
     * @param array<string, mixed> $userLayout Layout prefs
     */
    public function __construct(
        array $consultationsPassees = [],
        array $consultationsFutures = [],
        array $rooms = [],
        array $patientMetrics = [],
        array $patientData = [],
        array $chartTypes = [],
        array $userLayout = []
    ) {
        $this->consultationsPassees = $consultationsPassees;
        $this->consultationsFutures = $consultationsFutures;
        $this->rooms = $rooms;
        $this->patientMetrics = $patientMetrics;
        $this->patientData = $patientData;
        $this->chartTypes = $chartTypes;
        $this->userLayout = $userLayout;
    }

    /**
     * Generates consultation ID for DOM.
     *
     * @param mixed $consultation
     * @return string
     */
    private function getConsultationId($consultation)
    {
        if ($consultation instanceof \modules\models\entities\Consultation) {
            return 'consultation-' . $consultation->getId();
        }
        return 'consultation-unknown';
    }

    /**
     * Formats date string.
     *
     * @param string $dateStr
     * @return string
     */
    private function formatDate($dateStr)
    {
        try {
            $dateObj = new \DateTime($dateStr);
            return $dateObj->format('d-m-Y à H:i');
        } catch (\Exception $e) {
            return $dateStr;
        }
    }

    /**
     * Renders the complete dashboard HTML.
     *
     * Includes sidebar, searchbar, patient info, calendar, etc.
     *
     * @return void
     */
    public function show(): void
    {
        $current = $_COOKIE['room_id'] ?? null;
        if ($current !== null && $current !== '' && is_numeric($current)) {
            $current = (int) $current;
        } else {
            $current = null;
        }

        $h = static function ($v): string {
            return htmlspecialchars(is_scalar($v) ? (string) $v : '', ENT_QUOTES, 'UTF-8');
        };
        ?>
        <!DOCTYPE html>
        <html lang="fr">

        <head>
            <meta charset="UTF-8">
            <title>DashMed - Dashboard</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <meta name="author" content="DashMed Team">
            <meta name="keywords" content="dashboard, santé, médecins, patients, DashMed">
            <meta name="description" content="Tableau de bord privé pour les médecins,
             accessible uniquement aux utilisateurs authentifiés.">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/themes/dark.css">
            <link rel="stylesheet" href="assets/css/base/style.css">
            <link rel="stylesheet" href="assets/css/pages/dashboard.css?v=<?= time() ?>">
            <link rel="stylesheet" href="assets/css/pages/monitoring.css">
            <link rel="stylesheet" href="assets/css/layout/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/components/popup.css">
            <link rel="stylesheet" href="assets/css/components/modal.css?v=<?= time() ?>">
            <link rel="stylesheet" href="assets/css/layout/aside/calendar.css">
            <link rel="stylesheet" href="assets/css/layout/aside/patient-info.css">
            <link rel="stylesheet" href="assets/css/layout/aside/events.css">
            <link rel="stylesheet" href="assets/css/layout/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/layout/aside/aside.css">
            <link rel="stylesheet" href="assets/css/components/skeleton.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
            <style>
                .evenement-content {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }
            </style>
        </head>

        <body>

            <?php include dirname(__DIR__) . '/partials/_sidebar.php'; ?>

            <main class="container nav-space aside-space">

                <section class="dashboard-content-container">
                    <?php include dirname(__DIR__) . '/partials/_searchbar.php'; ?>
                    <?php
                    $patientId = $this->patientData['id_patient'] ?? '';

                    if (!is_scalar($patientId)) {
                        $patientId = '';
                    }

                    $patientIdAttr = htmlspecialchars((string) $patientId, ENT_QUOTES, 'UTF-8');
                    ?>

                    <input type="hidden" id="context-patient-id" value="<?= $patientIdAttr ?>">


                    <section class="skeleton-wrapper skeleton-monitoring-grid" id="skeleton-cards"
                        data-skeleton-for="real-cards" data-skeleton-auto data-skeleton-delay="400">
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

                    <section class="cards-container cards-grid" id="real-cards" style="width: 100%; display: none;">
                        <?php
                        $patientMetrics = $this->patientMetrics;
                        $chartTypes = $this->chartTypes;
                        $userLayout = $this->userLayout;
                        $useCustomLayout = true;
                        $componentPath = dirname(__DIR__) . '/partials/_monitoring-cards.php';
                        if (file_exists($componentPath)) {
                            include $componentPath;
                        }
                        ?>
                    </section>
                </section>
                <button id="aside-restore-btn" onclick="toggleDesktopAside()" title="Afficher le menu">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                </button>
                <button id="aside-show-btn" onclick="toggleAside()">☰</button>
                <aside id="aside">
                    <div class="skeleton-wrapper skeleton-aside-section" id="skeleton-aside" data-skeleton-for="real-aside"
                        data-skeleton-auto data-skeleton-delay="350">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                            <div class="skeleton skeleton-circle" style="width: 48px; height: 48px;"></div>
                            <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                                <div class="skeleton skeleton-text skeleton-text--lg" style="width: 70%;"></div>
                                <div class="skeleton skeleton-text skeleton-text--sm" style="width: 40%;"></div>
                            </div>
                        </div>
                        <div class="skeleton skeleton-text" style="width: 90%; height: 12px;"></div>
                        <div class="skeleton skeleton-select"></div>
                        <div style="margin-top: 16px;">
                            <div class="skeleton skeleton-text skeleton-text--lg" style="width: 50%; margin-bottom: 16px;">
                            </div>
                            <?php for ($j = 0; $j < 4; $j++): ?>
                                <div class="skeleton-aside-consultation">
                                    <div class="skeleton skeleton-rect" style="width: 90px; height: 28px;"></div>
                                    <div class="skeleton skeleton-text" style="flex: 1; height: 14px;"></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div id="real-aside" style="display: none;">
                        <section class="patient-infos">
                            <?php
                            $firstName = htmlspecialchars(
                                is_scalar($v = $this->patientData['first_name']) ? (string) $v : 'Patient'
                            );
                            $lastName = htmlspecialchars(
                                is_scalar($v = $this->patientData['last_name']) ? (string) $v : 'Inconnu'
                            );
                            $birthDateStr = is_scalar($v = $this->patientData['birth_date']) ? (string) $v : '';
                            $age = 'Âge inconnu';
                            if ($birthDateStr !== '') {
                                $bDate = date_create($birthDateStr);
                                $nowDate = date_create('today');
                                if ($bDate) {
                                    $age = date_diff($bDate, $nowDate)->y . ' ans';
                                }
                            }
                            $admissionCause = htmlspecialchars(
                                is_scalar(
                                    $v = $this->patientData['admission_cause']
                                ) ?
                                (string) $v : 'Aucun motif renseigné'
                            );
                            ?>
                            <div class="pi-header">
                                <h1><?= $firstName . ' ' . $lastName ?></h1>
                                <span class="pi-age"><?= $age ?></span>
                                <button class="aside-collapse-btn" onclick="toggleDesktopAside()" title="Masquer le menu">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M9 18l6-6-6-6" />
                                    </svg>
                                </button>
                            </div>
                            <p class="pi-cause"><?= $admissionCause ?></p>

                            <select id="id_rooms" name="room" onchange="location.href='/?page=dashboard&room=' + this.value"
                                style="margin-top: 15px; width: 100%; padding: 8px;">
                                <option value="" <?= $current === null ? 'selected' : '' ?>>
                                    -- Sélectionnez une chambre --
                                </option>
                                <?php if (!empty($this->rooms)): ?>
                                    <?php foreach ($this->rooms as $s):
                                        $room_id = (int) $s['room_id'];
                                        if ($room_id <= 0) {
                                            continue;
                                        }
                                        $sel = ($current !== null && $current === $room_id) ? 'selected' : '';
                                        ?>
                                        <option value="<?= $room_id ?>" <?= $sel ?>>Chambre <?= $room_id ?>
                                            (<?= htmlspecialchars($s['first_name'] ?? '') ?>)</option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </section>
                        <div class="consultations-sidebar-wrapper">
                            <div class="consultation-section-header">
                                <h1>Consultations</h1>
                                <div class="filter-buttons-container">
                                    <div id="sort-container">
                                        <button id="sort-btn">Trier ▾</button>
                                        <div id="sort-menu">
                                            <button class="sort-option" data-order="asc">Ordre croissant</button>
                                            <button class="sort-option" data-order="desc">Ordre décroissant</button>
                                        </div>
                                    </div>
                                    <div id="sort-container2">
                                        <button id="sort-btn2">Options ▾</button>
                                        <div id="sort-menu2">
                                            <button class="sort-option2">Rendez-vous à venir</button>
                                            <button class="sort-option2">Rendez-vous passé</button>
                                            <button class="sort-option2">Tout mes rendez-vous</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php
                            $toutesConsultations = array_merge(
                                $this->consultationsPassees,
                                $this->consultationsFutures
                            );

                            if (!empty($toutesConsultations)):
                                $consultationsAffichees = $toutesConsultations;
                                ?>
                                <section class="evenement" id="consultation-list">
                                    <?php foreach ($consultationsAffichees as $consultation):
                                        $dateStr = (string) $consultation->getDate();
                                        try {
                                            $dateObj = new \DateTime($dateStr);
                                            $isoDate = $dateObj->format('Y-m-d');
                                        } catch (\Exception $e) {
                                            $isoDate = $dateStr;
                                        }

                                        $title = $consultation->getTitle();
                                        if (empty($title)) {
                                            $title = $consultation->getEvenementType();
                                        }
                                        $title = (string) ($title ?: 'Consultation');

                                        $isPast = false;
                                        try {
                                            $cDate = new \DateTime($consultation->getDate());
                                            $now = new \DateTime();
                                            if ($cDate < $now) {
                                                $isPast = true;
                                            }
                                        } catch (\Exception $e) {
                                        }
                                        ?>
                                        <a href="/?page=medicalprocedure&id_patient=
                                    <?php echo urlencode((string) $patientId); ?>
                                    #<?php echo $this->getConsultationId($consultation); ?>" class="consultation-link"
                                            data-date="<?php echo $isoDate; ?>">
                                            <div class="evenement-content">
                                                <div class="date-container <?php if ($isPast) {
                                                    echo 'has-tooltip';
                                                } ?>" <?php if ($isPast) {
                                                     echo 'data-tooltip="Consultation déjà effectuée"';
                                                 } ?>>
                                                    <span class="date">
                                                        <?php echo htmlspecialchars($this->formatDate($dateStr)); ?></span>
                                                    <?php if ($isPast):
                                                        ?><span class="status-dot"></span><?php
                                                    endif; ?>
                                                </div>
                                                <strong class="title">
                                                    <?php echo htmlspecialchars((string) $title); ?></strong>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </section>
                            <?php else: ?>
                                <p>Aucune consultation</p>
                            <?php endif; ?>



                            <a href="/?page=medicalprocedure&id_patient=<?php echo urlencode((string) $patientId); ?>"
                                style="text-decoration: none; color: inherit;">
                                <p class="bouton-consultations">Afficher plus de contenu</p>
                            </a>
                            <br>
                        </div>

                    </div>

                </aside>


                <div class="modal" id="cardModal">
                    <div class="modal-content">
                        <span class="close-button">&times;</span>
                        <div id="modalDetails"></div>
                    </div>
                </div>

                <script src="assets/js/consultation-filter.js"></script>
                <script src="assets/js/pages/dash.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/moment.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/locale/fr.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.1/dist/chartjs-adapter-moment.min.js">
                </script>
                <script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8"></script>
                <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js">
                </script>

                <script src="assets/js/component/modal/chart.js?v=<?= time() ?>"></script>
                <script src="assets/js/component/modal/navigation.js"></script>
                <script src="assets/js/component/charts/card-sparklines.js?v=<?= time() ?>"></script>
                <script src="assets/js/component/modal/modal.js"></script>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        if (typeof ConsultationManager !== 'undefined') {
                            new ConsultationManager({
                                containerSelector: '#consultation-list',
                                itemSelector: '.consultation-link',
                                dateAttribute: 'data-date',
                                sortBtnId: 'sort-btn',
                                sortMenuId: 'sort-menu',
                                sortOptionSelector: '.sort-option',
                                filterBtnId: 'sort-btn2',
                                filterMenuId: 'sort-menu2',
                                filterOptionSelector: '.sort-option2'
                            });
                        }

                        const mainGrid = document.getElementById('real-cards');

                        if (mainGrid) {
                            Array.from(mainGrid.querySelectorAll('.card')).forEach(card => {
                                if (!card.classList.contains('card--alert') && !card.classList.contains('card--warn')) {
                                    card.style.display = 'none';
                                } else {
                                    card.style.display = 'flex';
                                }
                            });
                        }

                        setInterval(() => {
                            if (!mainGrid) return;

                            Array.from(mainGrid.querySelectorAll('.card')).forEach(card => {
                                const isAlert = card.classList.contains('card--alert') || card.classList.contains('card--warn');

                                if (card.dataset.animating === 'true') return;

                                if (isAlert) {
                                    if (card.hideTimeout) {
                                        clearTimeout(card.hideTimeout);
                                        card.hideTimeout = null;

                                        const prog = card.querySelector('.hide-progress');
                                        if (prog) prog.remove();
                                    }

                                    if (card.style.display === 'none') {
                                        card.dataset.animating = 'true';
                                        card.style.display = 'flex';

                                        const animation = card.animate([
                                            { transform: 'scale(0.5) translateY(20px)', opacity: 0 },
                                            { transform: 'scale(1) translateY(0)', opacity: 1 }
                                        ], {
                                            duration: 400,
                                            easing: 'cubic-bezier(0.34, 1.56, 0.64, 1)'
                                        });

                                        animation.onfinish = () => { card.dataset.animating = 'false'; };
                                    }
                                } else if (!isAlert && card.style.display !== 'none' && !card.hideTimeout) {
                                    card.style.position = 'relative';
                                    const w = card.offsetWidth;
                                    const h = card.offsetHeight;
                                    const inset = 2;
                                    const rx = 12;
                                    const rw = w - inset * 2;
                                    const rh = h - inset * 2;
                                    const perimeter = 2 * (rw + rh) - (8 - 2 * Math.PI) * rx;

                                    const ns = 'http://www.w3.org/2000/svg';
                                    const svg = document.createElementNS(ns, 'svg');
                                    svg.classList.add('hide-progress');
                                    svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
                                    svg.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;z-index:10;pointer-events:none;';

                                    const rect = document.createElementNS(ns, 'rect');
                                    rect.setAttribute('x', inset);
                                    rect.setAttribute('y', inset);
                                    rect.setAttribute('width', rw);
                                    rect.setAttribute('height', rh);
                                    rect.setAttribute('rx', rx);
                                    rect.setAttribute('ry', rx);
                                    rect.setAttribute('fill', 'none');
                                    rect.setAttribute('stroke', 'var(--color-critical, #EF4444)');
                                    rect.setAttribute('stroke-width', '3');
                                    rect.setAttribute('stroke-dasharray', perimeter);
                                    rect.setAttribute('stroke-dashoffset', '0');
                                    rect.setAttribute('stroke-linecap', 'round');

                                    svg.appendChild(rect);
                                    card.appendChild(svg);

                                    rect.animate([
                                        { strokeDashoffset: 0 },
                                        { strokeDashoffset: perimeter }
                                    ], { duration: 2500, easing: 'linear' });

                                    card.hideTimeout = setTimeout(() => {
                                        card.dataset.animating = 'true';

                                        const progToRemove = card.querySelector('.hide-progress');
                                        if (progToRemove) progToRemove.remove();

                                        const animation = card.animate([
                                            { transform: 'scale(1)', opacity: 1 },
                                            { transform: 'scale(0.5)', opacity: 0 }
                                        ], {
                                            duration: 300,
                                            easing: 'cubic-bezier(0.4, 0, 0.2, 1)'
                                        });

                                        animation.onfinish = () => {
                                            card.style.display = 'none';
                                            card.dataset.animating = 'false';
                                            card.hideTimeout = null;
                                        };
                                    }, 2500);
                                }
                            });

                        }, 1000);
                    });
                </script>
                <?php include dirname(__DIR__) . '/partials/_scroll-to-top.php'; ?>
                <script src="assets/js/components/skeleton.js"></script>
            </main>

            <?php include dirname(__DIR__) . '/partials/_global-alerts.php'; ?>
        </body>

        </html>
        <?php
    }
}
