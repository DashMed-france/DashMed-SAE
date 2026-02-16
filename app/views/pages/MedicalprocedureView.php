<?php

namespace modules\views\pages;

use modules\models\Entities\Consultation;

/**
 * Class MedicalprocedureView
 *
 * View for displaying patient consultation history.
 *
 * Includes modal for new consultations and filtering options.
 *
 * @package DashMed\Modules\Views\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class MedicalprocedureView
{
    /**
     * @var array<int, \modules\models\Entities\Consultation> Consultations list
     */
    private $consultations;

    /**
     * @var array<int, array{
     *   id_user: int|string,
     *   last_name: string,
     *   first_name: string
     * }> Available doctors list
     */
    private $doctors;

    /**
     * @var bool Is admin flag
     */
    private $isAdmin;

    /**
     * @var int Current user ID
     */
    private $currentUserId;

    /**
     * @var int|null Context patient ID
     */
    private $patientId;

    /**
     * Constructor.
     *
     * Initializes the view with required data.
     *
     * @param array<int, \modules\models\Entities\Consultation> $consultations Consultation objects
     * @param array<int, array{
     *   id_user: int|string,
     *   last_name: string,
     *   first_name: string
     * }> $doctors Doctor list
     * @param bool     $isAdmin       Is admin
     * @param int      $currentUserId Current User ID
     * @param int|null $patientId     Patient ID
     */
    public function __construct(
        $consultations = [],
        $doctors = [],
        $isAdmin = false,
        $currentUserId = 0,
        $patientId = null
    ) {
        $this->consultations = $consultations;
        $this->doctors = $doctors;
        $this->isAdmin = $isAdmin;
        $this->currentUserId = $currentUserId;
        $this->patientId = $patientId;
    }

    /**
     * Generates a unique ID for consultation deep-linking.
     *
     * Format: DoctorName-YYYY-MM-DD
     *
     * @param object $consultation The consultation entity
     * @return string Secure HTML ID
     */


    /**
     * Formats a date for user display.
     *
     * @param string $dateStr Raw date
     * @return string Formatted date
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
     * Renders the final page HTML.
     *
     * Generates complete HTML including sidebar, searchbar, consultation list cards, and interaction modals.
     *
     * @return void
     */
    public function show(): void
    {
        ?>
        <!doctype html>
        <html lang="fr">

        <head>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>DashMed - Dossier Médical</title>
            <meta name="robots" content="noindex, nofollow">
            <meta name="author" content="DashMed Team">
            <meta name="keywords" content="dashboard, santé, médecins, patients, DashMed">
            <meta name="description" content="Tableau de bord privé pour les médecins,
             accessible uniquement aux utilisateurs authentifiés.">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/themes/dark.css">
            <link rel="stylesheet" href="assets/css/base/style.css">
            <link rel="stylesheet" href="assets/css/pages/medical-procedure.css">
            <link rel="stylesheet" href="assets/css/layout/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/pages/consultation.css">
            <link rel="stylesheet" href="assets/css/components/consultation-modal.css">
            <link rel="stylesheet" href="assets/css/layout/aside/aside.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>

            <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

            <main class="container nav-space">

                <section class="dashboard-content-container">
                    <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>
                    <input type="hidden" id="context-patient-id" value="<?= htmlspecialchars((string) $this->patientId) ?>">

                    <div id="button-bar">
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
                                <button class="sort-option2">Consultations à venir</button>
                                <button class="sort-option2">Consultations passées</button>
                                <button class="sort-option2">Toutes mes consultations</button>
                            </div>
                        </div>
                        <button id="btn-add-consultation" class="btn-primary">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Nouvelle Consultation
                        </button>
                    </div>

                    <section class="consultations-container">
                        <?php if (!empty($this->consultations)): ?>
                            <?php foreach ($this->consultations as $consultation):
                                ?>
                                <article class="consultation" id="consultation-<?php echo $consultation->getId(); ?>" data-date="<?php
                                   $d = (string) $consultation->getDate();
                                   try {
                                       echo (new \DateTime($d))->format('Y-m-d');
                                   } catch (\Exception $e) {
                                       echo $d;
                                   }
                                   ?>">
                                    <div class="consultation-header">
                                        <div class="header-left">
                                            <div class="icon-box">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2
                                                    0 0 0 2-2V7.5L14.5 2z">
                                                    </path>
                                                    <polyline points="14 2 14 8 20 8"></polyline>
                                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                                    <line x1="10" y1="9" x2="8" y2="9"></line>
                                                </svg>
                                            </div>
                                            <h2 class="consultation-title">
                                                <?php
                                                $title = $consultation->getTitle();
                                                if (!$title) {
                                                    $title = $consultation->getType();
                                                }
                                                echo htmlspecialchars($title);
                                                ?>
                                            </h2>
                                        </div>
                                        <div class="header-right">
                                            <?php
                                            $canEdit = $this->isAdmin || ((int) $consultation->getDoctorId() ===
                                                (int) $this->currentUserId);

                                            $id = (int) $consultation->getId();
                                            $doctorId = (int) $consultation->getDoctorId();

                                            $doctor = (string) $consultation->getDoctor();

                                            $dateObj = new \DateTime((string) $consultation->getDate());
                                            $dateYmd = $dateObj->format('Y-m-d');
                                            $timeHi = $dateObj->format('H:i');

                                            $type = htmlspecialchars(
                                                (string) $consultation->getType(),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            $title = htmlspecialchars(
                                                (string) $consultation->getTitle(),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            $note = htmlspecialchars(
                                                (string) $consultation->getNote(),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );

                                            $doctorAttr = htmlspecialchars(
                                                $doctor,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );

                                            ?>
                                            <?php
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
                                            <span class="date-badge <?php if ($isPast) {
                                                echo 'has-tooltip';
                                            } ?>" <?php if ($isPast) {
                                                 echo 'data-tooltip="Consultation déjà effectuée"';
                                             } ?>>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                                </svg>
                                                <?php echo htmlspecialchars(
                                                    $this->formatDate(
                                                        $consultation->getDate()
                                                    )
                                                ); ?>
                                                <?php if ($isPast): ?>
                                                    <span class="status-dot"></span>
                                                <?php endif; ?>
                                            </span>
                                            <?php if (
                                                $this->isAdmin || $consultation->getDoctorId() ==
                                                $this->currentUserId
                                            ): ?>
                                                <div class="action-buttons">
                                                    <button class="btn-icon edit-btn" title="Modifier" data-id="<?= $id ?>"
                                                        data-doctor-id="<?= $doctorId ?>" data-doctor="<?= $doctorAttr ?>"
                                                        data-date="<?= $dateYmd ?>" data-time="<?= $timeHi ?>" data-type="<?= $type ?>"
                                                        data-title="<?= $title ?>" data-note="<?= $note ?>">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2
                                                         2 0 0 0 2-2v-7"></path>
                                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4
                                                        1 1-4 9.5-9.5z"></path>
                                                        </svg>
                                                    </button>
                                                    <button class="btn-icon delete-btn" title="Supprimer"
                                                        data-id="<?php echo $consultation->getId(); ?>">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="3 6 5 6 21 6"></polyline>
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3
                                                             0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="consultation-body">
                                        <div class="consultation-meta-grid">
                                            <div class="meta-item">
                                                <span class="meta-label">Médecin</span>
                                                <span class="meta-value doctor-name">Dr.
                                                    <?php echo htmlspecialchars($consultation->getDoctor()); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Type</span>
                                                <span class="meta-value type-badge">
                                                    <?php echo htmlspecialchars($consultation->getType()); ?></span>
                                            </div>
                                        </div>

                                        <div class="consultation-report-section">
                                            <h3 class="report-label">Compte rendu</h3>
                                            <div class="report-content">
                                                <?php
                                                $note = $consultation->getNote();
                                                if (!empty($note)) {
                                                    echo nl2br(htmlspecialchars($note));
                                                } else {
                                                    echo '
                                                        <span style="color: #a1a1a6; font-style: italic;">
                                                            Aucun compte rendu disponible
                                                        </span>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="consultation-footer">
                                        <?php if (
                                            $consultation->getDocument() &&
                                            $consultation->getDocument() !== 'Aucun'
                                        ): ?>
                                            <div class="document-section">
                                                <span class="doc-label">Documents joints :</span>
                                                <span class="doc-link">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4
                                                            4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48">
                                                        </path>
                                                    </svg>
                                                    <?php echo htmlspecialchars($consultation->getDocument()); ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div class="document-section empty">
                                                <span class="doc-placeholder">Aucun document joint</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <article class="consultation">
                                <p>Aucune consultation à afficher</p>
                            </article>
                        <?php endif; ?>
                    </section>
                </section>

                <div id="add-consultation-modal" class="modal-backdrop hidden">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Nouvelle Consultation</h2>
                            <button id="close-modal-btn" class="close-btn">&times;</button>
                        </div>
                        <form id="add-consultation-form" method="POST" action="?page=medicalprocedure">
                            <input type="hidden" id="form-action" name="action" value="add_consultation">
                            <input type="hidden" id="consultation-id" name="id_consultation" value="">

                            <div class="form-group">
                                <label for="doctor-select">Médecin</label>
                                <?php if ($this->isAdmin): ?>
                                    <select id="doctor-select" name="doctor_id" required>
                                        <option value="">Sélectionner un médecin</option>
                                        <?php foreach ($this->doctors as $doc): ?>
                                            <option value="<?php echo htmlspecialchars((string) $doc['id_user']); ?>" <?php
                                                echo ($doc['id_user'] == $this->currentUserId) ? 'selected' : '';
                                                $doctorName = $doc['last_name'] . ' ' . $doc['first_name'];
                                                $doctorName = htmlspecialchars(
                                                    $doctorName,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );
                                                ?>>
                                                Dr. <?= $doctorName ?>

                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <?php
                                    $docName = 'Moi-même';
                                    foreach ($this->doctors as $doc) {
                                        $idUser = (int) $doc['id_user'];
                                        if ($idUser == $this->currentUserId) {
                                            $docName = 'Dr. ' . $doc['last_name'] . ' ' . $doc['first_name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <input type="text" value="<?php echo htmlspecialchars($docName); ?>" disabled
                                        class="form-control-disabled"
                                        style="background-color: #f5f5f7; color: #86868b; cursor: not-allowed;">
                                    <input type="hidden" name="doctor_id" value="<?php echo $this->currentUserId; ?>">
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="consultation-title">Titre / Motif</label>
                                <input type="text" id="consultation-title" name="consultation_title" required
                                    placeholder="Ex: Consultation de suivi">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="consultation-date">Date</label>
                                    <input type="date" id="consultation-date" name="consultation_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="consultation-time">Heure</label>
                                    <input type="time" id="consultation-time" name="consultation_time" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="consultation-type">Type de consultation</label>
                                <select id="consultation-type" name="consultation_type" required>
                                    <option value="Générale">Générale</option>
                                    <option value="Suivi">Suivi</option>
                                    <option value="Bilan">Bilan</option>
                                    <option value="Urgence">Urgence</option>
                                    <option value="Spécialisée">Spécialisée</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="consultation-note">Compte rendu / Notes</label>
                                <textarea id="consultation-note" name="consultation_note" rows="5"
                                    placeholder="Détails de la consultation..."></textarea>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn-secondary" id="cancel-modal-btn">Annuler</button>
                                <button type="submit" class="btn-primary">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>

                <script src="assets/js/consultation-filter.js"></script>
                <script src="assets/js/consultation-modal.js"></script>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        new ConsultationManager({
                            containerSelector: '.consultations-container',
                            itemSelector: '.consultation',
                            dateAttribute: 'data-date',
                            sortBtnId: 'sort-btn',
                            sortMenuId: 'sort-menu',
                            sortOptionSelector: '.sort-option',
                            filterBtnId: 'sort-btn2',
                            filterMenuId: 'sort-menu2',
                            filterOptionSelector: '.sort-option2'
                        });
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
