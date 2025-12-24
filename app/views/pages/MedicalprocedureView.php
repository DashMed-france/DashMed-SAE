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
                                       // Ensure Y-m-d format
                                       echo (new \DateTime($d))->format('Y-m-d');
                                   } catch (\Exception $e) {
                                       echo $d;
                                   }
                                   ?>">
                                    <h2 class="TitreDeConsultation">Consultation -
                                        <?php echo htmlspecialchars($consultation->getTitle() ?: $consultation->getType()); ?>
                                    </h2>
                                    <div class="consultation-details">
                                        <p class="consultation-date">
                                            <strong class="TitreDeConsultation">Date :</strong>
                                            <span
                                                class="consultation-date-value"><?php echo htmlspecialchars($this->formatDate($consultation->getDate())); ?></span>
                                        </p>
                                        <p>
                                            <strong class="TitreDeConsultation">Médecin :</strong>
                                            <?php echo htmlspecialchars($consultation->getDoctor()); ?>
                                        </p>
                                        <p>
                                            <strong class="TitreDeConsultation">Type d'événement :</strong>
                                            <?php echo htmlspecialchars($consultation->getType()); ?>
                                        </p>
                                        <p>
                                            <strong class="TitreDeConsultation">Compte rendu:</strong>
                                        </p>
                                        <p>
                                            <?php echo nl2br(htmlspecialchars($consultation->getNote())); ?>
                                        </p>
                                        <p>
                                            <strong class="TitreDeConsultation">Document(s):</strong>
                                            <?php echo htmlspecialchars($consultation->getDocument()); ?>
                                        </p>
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
