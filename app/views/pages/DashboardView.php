<?php

/**
 * DashMed — Vue du tableau de bord
 *
 * Affiche la page principale du tableau de bord pour les utilisateurs authentifiés.
 * Contient les indicateurs clés du patient, une barre de recherche
 * et des composants latéraux tels que la barre latérale et le calendrier.
 *
 * @package   DashMed\Modules\Views
 * @author    Équipe DashMed
 * @license   Propriétaire
 */

namespace modules\views\pages;

/**
 * Vue du Tableau de Bord (Dashboard).
 *
 * Point d'entrée principal pour les médecins authentifiés. Cette vue agrège
 * les indicateurs vitaux, les consultations à venir/passées et la gestion
 * de la sélection des chambres.
 *
 * @package modules\views\pages
 */
class DashboardView
{
    private $consultationsPassees;
    private $consultationsFutures;
    private array $rooms;
    private array $patientMetrics;
    private array $patientData;
    private array $chartTypes;
    private array $userLayout;

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

    function getConsultationId($consultation)
    {
        $doctor = preg_replace('/[^a-zA-Z0-9]/', '-', $consultation->getDoctor());
        $dateObj = \DateTime::createFromFormat('d/m/Y', $consultation->getDate());
        if (!$dateObj) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $consultation->getDate());
        }
        $date = $dateObj ? $dateObj->format('Y-m-d') : $consultation->getDate();
        return $doctor . '-' . $date;
    }

    function formatDate($dateStr)
    {
        try {
            $dateObj = new \DateTime($dateStr);
            return $dateObj->format('d-m-Y à H:i');
        } catch (\Exception $e) {
            return $dateStr;
        }
    }

    /**
     * Génère la structure HTML complète de la page du tableau de bord.
     *
     * Inclut la barre latérale, la barre de recherche supérieure, le panneau d'informations patient,
     * le calendrier et la liste des médecins.
     * Cette vue n'effectue aucune logique métier — elle se limite uniquement au rendu.
     *
     * @return void
     */
    public function show(): void
    {
        $current = $_COOKIE['room_id'] ?? null;
        if ($current !== null && $current !== '' && ctype_digit((string) $current)) {
            $current = (int) $current;
        } else {
            $current = null;
        }

        $h = static function ($v): string {
            return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
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
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/dash.css">
            <link rel="stylesheet" href="assets/css/monitoring.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/components/popup.css">
            <link rel="stylesheet" href="assets/css/components/modal.css">
            <link rel="stylesheet" href="assets/css/components/aside/calendar.css">
            <link rel="stylesheet" href="assets/css/components/aside/patient-infos.css">
            <link rel="stylesheet" href="assets/css/components/aside/events.css">
            <link rel="stylesheet" href="assets/css/components/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/components/aside/aside.css">
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
                    <input type="hidden" id="context-patient-id"
                        value="<?= htmlspecialchars((string) ($this->patientData['id_patient'] ?? '')) ?>">

                    <section class="cards-container cards-grid">
                        <?php
                        $patientMetrics = $this->patientMetrics;
                        $chartTypes = $this->chartTypes;
                        $userLayout = $this->userLayout;
                        $componentPath = dirname(__DIR__) . '/components/MonitoringCards.php';
                        if (file_exists($componentPath)) {
                            include $componentPath;
                        } else {
                            echo '<p>Erreur chargement cartes monitoring.</p>';
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
                        $firstName = !empty($this->patientData['first_name']) ? htmlspecialchars($this->patientData['first_name']) : 'Patient';
                        $lastName = !empty($this->patientData['last_name']) ? htmlspecialchars($this->patientData['last_name']) : 'Inconnu';
                        $age = !empty($this->patientData['birth_date']) ? (date_diff(date_create($this->patientData['birth_date']), date_create('today'))->y . ' ans') : 'Âge inconnu';
                        $admissionCause = !empty($this->patientData['admission_cause']) ? htmlspecialchars($this->patientData['admission_cause']) : 'Aucun motif renseigné';
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
                            <option value="" <?= $current === null ? 'selected' : '' ?>>-- Sélectionnez une chambre --</option>
                            <?php if (!empty($this->rooms)): ?>
                                <?php foreach ($this->rooms as $s):
                                    $room_id = (int) ($s['room_id'] ?? 0);
                                    if ($room_id <= 0)
                                        continue;
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
                            $this->consultationsPassees ?? [],
                            $this->consultationsFutures ?? []
                        );

                        if (!empty($toutesConsultations)):
                            $consultationsAffichees = $toutesConsultations;
                            ?>
                            <section class="evenement" id="consultation-list">
                                <?php foreach ($consultationsAffichees as $consultation):
                                    $dateStr = $consultation->getDate();
                                    try {
                                        $dateObj = new \DateTime($dateStr);
                                        $isoDate = $dateObj->format('Y-m-d');
                                    } catch (\Exception $e) {
                                        $isoDate = $dateStr;
                                    }

                                    $title = method_exists($consultation, 'getTitle') ? $consultation->getTitle() : (method_exists($consultation, 'getEvenementType') ? $consultation->getEvenementType() : 'Consultation');
                                    if (empty($title) && method_exists($consultation, 'getType')) {
                                        $title = $consultation->getType();
                                    }

                                    // Status logic
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
                                    <a href="/?page=medicalprocedure#<?php echo $this->getConsultationId($consultation); ?>"
                                        class="consultation-link" data-date="<?php echo $isoDate; ?>">
                                        <div class="evenement-content">
                                            <div class="date-container <?php if ($isPast)
                                                echo 'has-tooltip'; ?>" <?php if ($isPast)
                                                      echo 'data-tooltip="Consultation déjà effectuée"'; ?>>
                                                <span class="date"><?php echo htmlspecialchars($this->formatDate($dateStr)); ?></span>
                                                <?php if ($isPast): ?><span class="status-dot"></span><?php endif; ?>
                                            </div>
                                            <strong class="title"><?php echo htmlspecialchars($title); ?></strong>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </section>
                        <?php else: ?>
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
                <script src="assets/js/pages/dash.js"></script>
                <script src="assets/js/component/modal/chart.js"></script>
                <script src="assets/js/component/modal/navigation.js"></script>
                <script src="assets/js/component/charts/card-sparklines.js"></script>
                <script src="assets/js/component/modal/modal.js"></script>

                <script>             document.addEventListener('DOMContentLoaded', () => { if (typeof ConsultationManager !== 'undefined') { new ConsultationManager({ containerSelector: '#consultation-list', itemSelector: '.consultation-link', dateAttribute: 'data-date', sortBtnId: 'sort-btn', sortMenuId: 'sort-menu', sortOptionSelector: '.sort-option', filterBtnId: 'sort-btn2', filterMenuId: 'sort-menu2', filterOptionSelector: '.sort-option2' }); } });
                </script>
                <?php include dirname(__DIR__) . '/components/scroll-to-top.php'; ?>
            </main>

            <!-- Système global de notifications médicales -->
            <?php include dirname(__DIR__) . '/components/global-alerts.php'; ?>
        </body>

        </html>
        <?php
    }
}
