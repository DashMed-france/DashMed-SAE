<?php

namespace modules\views\pages;

use modules\models\Consultation;

class MedicalprocedureView
{
    private $consultations;

    public function __construct($consultations = [])
    {
        $this->consultations = $consultations;
    }

    function getConsultationId($consultation)
    {
        $doctor = preg_replace('/[^a-zA-Z0-9]/', '-', $consultation->getDoctor());
        $dateObj = \DateTime::createFromFormat('d/m/Y', $consultation->getDate());
        if (!$dateObj) {
            try {
                $dateObj = new \DateTime($consultation->getDate());
            } catch (\Exception $e) {
                $dateObj = null;
            }
        }
        $date = $dateObj ? $dateObj->format('Y-m-d') : $consultation->getDate();
        return $doctor . '-' . $date;
    }

    function formatDate($dateStr)
    {
        try {
            $dateObj = new \DateTime($dateStr);
            return $dateObj->format('d/m/Y à H:i');
        } catch (\Exception $e) {
            return $dateStr;
        }
    }

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
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/medicalProcedure.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/consultation.css">
            <link rel="stylesheet" href="assets/css/components/aside/aside.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
            <style>
                .consultation-date-value {
                    font-family: inherit;
                    /* Ensure it uses site font */
                    white-space: nowrap;
                    /* Prevent wrapping */
                }
            </style>
        </head>

        <body>

            <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

            <main class="container nav-space">

                <section class="dashboard-content-container">
                    <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>

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
                                <button class="sort-option2">Rendez-vous a venir</button>
                                <button class="sort-option2">Rendez-vous passé</button>
                                <button class="sort-option2">Tout mes rendez-vous</button>
                            </div>
                        </div>
                    </div>

                    <section class="consultations-container">
                        <?php if (!empty($this->consultations)): ?>
                            <?php foreach ($this->consultations as $consultation): ?>
                                <article class="consultation" id="<?php echo $this->getConsultationId($consultation); ?>" data-date="<?php
                                   $d = $consultation->getDate();
                                   try {
                                       echo (new \DateTime($d))->format('Y-m-d');
                                   } catch (\Exception $e) {
                                       echo $d;
                                   }
                                   ?>">
                                    <div class="consultation-header">
                                        <div class="header-left">
                                            <div class="icon-box">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                                                    <polyline points="14 2 14 8 20 8"></polyline>
                                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                                    <line x1="10" y1="9" x2="8" y2="9"></line>
                                                </svg>
                                            </div>
                                            <h2 class="consultation-title">
                                                <?php echo htmlspecialchars($consultation->getTitle() ?: $consultation->getType()); ?>
                                            </h2>
                                        </div>
                                        <div class="header-right">
                                            <span class="date-badge">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                                </svg>
                                                <?php echo htmlspecialchars($this->formatDate($consultation->getDate())); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="consultation-body">
                                        <div class="consultation-meta-grid">
                                            <div class="meta-item">
                                                <span class="meta-label">Médecin</span>
                                                <span class="meta-value doctor-name">Dr. <?php echo htmlspecialchars($consultation->getDoctor()); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Type</span>
                                                <span class="meta-value type-badge"><?php echo htmlspecialchars($consultation->getType()); ?></span>
                                            </div>
                                        </div>

                                        <div class="consultation-report-section">
                                            <h3 class="report-label">Compte rendu</h3>
                                            <div class="report-content">
                                                <?php echo nl2br(htmlspecialchars($consultation->getNote())); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="consultation-footer">
                                        <?php if ($consultation->getDocument() && $consultation->getDocument() !== 'Aucun'): ?>
                                            <div class="document-section">
                                                <span class="doc-label">Documents joints :</span>
                                                <span class="doc-link">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
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
                <script src="assets/js/consultation-filter.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        new ConsultationManager({
                            containerSelector: '.consultations-container',
                            itemSelector: '.consultation', // This is different from Dashboard
                            dateAttribute: 'data-date', // We need to add this to the view!
                            sortBtnId: 'sort-btn',
                            sortMenuId: 'sort-menu',
                            sortOptionSelector: '.sort-option',
                            filterBtnId: 'sort-btn2',
                            filterMenuId: 'sort-menu2',
                            filterOptionSelector: '.sort-option2'
                        });
                    });
                </script>

            </main>
        </body>

        </html>
        <?php
    }
}
