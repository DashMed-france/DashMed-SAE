<?php

namespace modules\views\pages;

/**
 * Class DashboardView | Vue Tableau de Bord
 *
 * View for the main dashboard.
 * Vue du tableau de bord principal.
 *
 * Main entry point for authenticated doctors. Aggregates vital metrics,
 * past/future consultations, and room selection.
 * Point d'entrée principal pour les médecins authentifiés. Cette vue agrège
 * les indicateurs vitaux, les consultations à venir/passées et la gestion
 * de la sélection des chambres.
 *
 * @package DashMed\Modules\Views\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class DashboardView
{
    /** @var array<int, \modules\models\Consultation> Past consultations list | Liste des consultations terminées */
    private array $consultationsPassees;

    /** @var array<int, \modules\models\Consultation> Future consultations list | Liste des consultations futures */
    private array $consultationsFutures;

    /** @var array<int, array{
     *   room_id: int|string,
     *   first_name?: string
     * }> Rooms list with patients | Liste des chambres avec patients associés */
    private array $rooms;

    /** @var array<int, array<string, mixed>> Selected patient metrics | Métriques vitales du patient sélectionné */
    private array $patientMetrics;

    /** @var array<string, mixed> Selected patient data | Données administratives du patient sélectionné */
    private array $patientData;

    /** @var array<string, mixed> Chart types config | Configuration des types de graphiques */
    private array $chartTypes;

    /** @var array<string, mixed> User layout preferences | Préférences d'affichage de l'utilisateur */
    private array $userLayout;

    /**
     * Constructor.
     * Constructeur.
     *
     * Initializes dashboard with contextual data.
     * Initialise le tableau de bord avec l'ensemble des données contextuelles.
     *
     * @param array<int, \modules\models\Consultation> $consultationsPassees History | Historique des consultations.
     * @param array<int, \modules\models\Consultation> $consultationsFutures Appointments | Rendez-vous à venir.
     * @param array<int, array{
     *   room_id: int|string,
     *   first_name?: string
     * }> $rooms Occupied rooms | Liste des chambres occupées.
     * @param array<int, array<string, mixed>> $patientMetrics Health data | Données de santé temps réel/historique.
     * @param array<string, mixed> $patientData Patient info | Infos patient (identité, âge, motif).
     * @param array<string, mixed> $chartTypes Visualizations | Configuration des visualisations.
     * @param array<string, mixed> $userLayout Layout prefs | Préférences de mise en page.
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
     * Génère l'ID de consultation pour le DOM.
     *
     * @param mixed $consultation
     * @return string
     */
    private function getConsultationId($consultation)
    {
        if ($consultation instanceof \modules\models\Consultation) {
            return 'consultation-' . $consultation->getId();
        }
        return 'consultation-unknown';
    }

    /**
     * Formats date string.
     * Formate une chaîne de date.
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
     * Génère la structure HTML complète de la page du tableau de bord.
     *
     * Includes sidebar, searchbar, patient info, calendar, etc.
     * Inclut la barre latérale, la barre de recherche supérieure, le panneau d'informations patient,
     * le calendrier et la liste des médecins.
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
            <link rel="stylesheet" href="assets/css/pages/dashboard.css">
            <link rel="stylesheet" href="assets/css/pages/monitoring.css">
            <link rel="stylesheet" href="assets/css/layout/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/components/popup.css">
            <link rel="stylesheet" href="assets/css/components/modal.css">
            <link rel="stylesheet" href="assets/css/layout/aside/calendar.css">
            <link rel="stylesheet" href="assets/css/layout/aside/patient-info.css">
            <link rel="stylesheet" href="assets/css/layout/aside/events.css">
            <link rel="stylesheet" href="assets/css/layout/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/layout/aside/aside.css">
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

            <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

            <main class="container nav-space aside-space">

                <section class="dashboard-content-container">
                    <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>
                    <?php
                    $patientId = $this->patientData['id_patient'] ?? '';

                    if (!is_scalar($patientId)) {
                        $patientId = '';
                    }

                    $patientIdAttr = htmlspecialchars((string) $patientId, ENT_QUOTES, 'UTF-8');
                    ?>

                    <input
                            type="hidden"
                            id="context-patient-id"
                            value="<?= $patientIdAttr ?>"
                    >


                    <?php
                    $patientMetrics = $this->patientMetrics;
                    $chartTypes = $this->chartTypes;
                    $userLayout = $this->userLayout;

                    $priorityMetrics = [];
                    foreach ($patientMetrics as $row) {
                        $viewData = is_array($row['view_data'] ?? null) ? $row['view_data'] : [];
                        $cardClass = $viewData['card_class'] ?? '';
                        $isPriority = ($cardClass === 'card--alert' || $cardClass === 'card--warn');
                        if ($isPriority) {
                            $priorityMetrics[] = $row;
                        }
                    }
                    ?>

                    <?php if (!empty($priorityMetrics)) : ?>
                        <?php
                        $criticalCount = 0;
                        $warningCount = 0;
                        foreach ($priorityMetrics as $pm) {
                            $vd = is_array($pm['view_data'] ?? null) ? $pm['view_data'] : [];
                            $pClass = $vd['card_class'] ?? '';
                            if ($pClass === 'card--alert') {
                                $criticalCount++;
                            } elseif ($pClass === 'card--warn') {
                                $warningCount++;
                            }
                        }
                        $totalAlerts = count($priorityMetrics);
                        ?>
                        <section class="critical-zone" id="priority-zone">
                            <div class="critical-zone-header">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2
                                        2 0 0 0-3.42 0z" />
                                    <line x1="12" y1="9" x2="12" y2="13" />
                                    <line x1="12" y1="17" x2="12.01" y2="17" />
                                </svg>
                                <h2>Alertes prioritaires</h2>
                                <div class="alert-badges">
                                    <?php if ($criticalCount > 0) : ?>
                                        <span class="alert-badge alert-badge--critical"><?= $criticalCount ?>
                                            critique<?= $criticalCount > 1 ? 's' : '' ?></span>
                                    <?php endif; ?>
                                    <?php if ($warningCount > 0) : ?>
                                        <span class="alert-badge alert-badge--warning"><?= $warningCount ?>
                                            alerte<?= $warningCount > 1 ? 's' : '' ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <section class="cards-container cards-grid priority-grid">
                                <?php
                                $idPrefix = 'crit-';
                                $useCustomSize = true;
                                $patientMetrics = $priorityMetrics;
                                $componentPath = dirname(__DIR__) . '/components/monitoring-cards.php';
                                if (file_exists($componentPath)) {
                                    include $componentPath;
                                }
                                $idPrefix = '';
                                $useCustomSize = false;
                                ?>
                            </section>
                        </section>
                        <hr class="zone-separator">
                    <?php endif; ?>

                    <section class="cards-container cards-grid">
                        <?php
                        $normalMetrics = [];
                        foreach ($this->patientMetrics as $row) {
                            $forceShown = !empty($row['force_shown']);
                            if (!$forceShown) {
                                $normalMetrics[] = $row;
                            }
                        }
                        $patientMetrics = $normalMetrics;
                        $useCustomLayout = true;
                        $componentPath = dirname(__DIR__) . '/components/monitoring-cards.php';
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
                            <?php if (!empty($this->rooms)) : ?>
                                <?php foreach ($this->rooms as $s) :
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

                        if (!empty($toutesConsultations)) :
                            $consultationsAffichees = $toutesConsultations;
                            ?>
                            <section class="evenement" id="consultation-list">
                                <?php foreach ($consultationsAffichees as $consultation) :
                                    // instanceof logic removed as array is strictly typed now
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
                                    <a href="/?page=medicalprocedure#
                                    <?php echo $this->getConsultationId($consultation); ?>"
                                        class="consultation-link" data-date="<?php echo $isoDate; ?>">
                                        <div class="evenement-content">
                                            <div class="date-container <?php if ($isPast) {
                                                echo 'has-tooltip';
                                                                       } ?>" <?php if ($isPast) {
                      echo 'data-tooltip="Consultation déjà effectuée"';
                                                                       } ?>>
                                                <span class="date">
                                                    <?php echo htmlspecialchars($this->formatDate($dateStr)); ?></span>
                                                <?php if ($isPast) :
                                                    ?><span class="status-dot"></span><?php
                                                endif; ?>
                                            </div>
                                            <strong class="title">
                                                <?php echo htmlspecialchars((string) $title); ?></strong>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </section>
                        <?php else : ?>
                            <p>Aucune consultation</p>
                        <?php endif; ?>



                        <a href="/?page=medicalprocedure" style="text-decoration: none; color: inherit;">
                            <p class="bouton-consultations">Afficher plus de contenu</p>
                        </a>
                        <br>
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
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                <script src="assets/js/component/modal/chart.js"></script>
                <script src="assets/js/component/modal/navigation.js"></script>
                <script src="assets/js/component/charts/card-sparklines.js"></script>
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
                    });
                </script>
                <?php include dirname(__DIR__) . '/components/scroll-to-top.php'; ?>
            </main>

            <?php include dirname(__DIR__) . '/components/global-alerts.php'; ?>
        </body>

        </html>
        <?php
    }
}
