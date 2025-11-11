<?php

namespace modules\views\pages;

use modules\models\consultation;

class MedicalprocedureView
{
    private $consultations;

    // Constructeur pour recevoir un tableau de consultations
    public function __construct($consultations = [])
    {
        $this->consultations = $consultations;
    }

    public function show(): void
    {
        ?>
        <!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width" />
            <title>DashMed - Dossier Médicals</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <meta name="author" content="DashMed Team">
            <meta name="keywords" content="dashboard, santé, médecins, patients, DashMed">
            <meta name="description" content="Tableau de bord privé pour les médecins,
             accessible uniquement aux utilisateurs authentifiés.">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <meta name="description" content="Tableau de bord privé pour les médecins, accessible uniquement aux utilisateurs authentifiés.">
            <link id="theme-style" rel="stylesheet" href="/assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/dash.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/consultation.css">
            <link rel="stylesheet" href="assets/css/components/aside/aside.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>
        <body>

        <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

        <main class="container nav-space">

            <section class="dashboard-content-container">
                <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>

                <section class="consultations-container">
                    <?php if (!empty($this->consultations)) : ?>
                        <?php foreach ($this->consultations as $consultation) : ?>
                            <article class="consultation">
                                <h2 class="TitreDeConsultation">
                                    Consultation - <?php echo htmlspecialchars($consultation->getEvenementType()); ?>
                                </h2>
                                <div class="consultation-details">
                                    <p class="consultation-date">
                                        <strong class="TitreDeConsultation">
                                        Date :</strong> <?php echo htmlspecialchars($consultation->getDate()); ?>
                                    </p>
                                    <p>
                                        <strong class="TitreDeConsultation">Médecin :</strong>
                                        <?php echo htmlspecialchars($consultation->getDoctor()); ?>
                                    </p>
                                    <p>
                                        <strong class="TitreDeConsultation">Type d'événement :</strong>
                                        <?php echo htmlspecialchars($consultation->getEvenementType()); ?>
                                    </p>
                                    <p>
                                        <strong class="TitreDeConsultation">Notes :</strong>
                                    </p>
                                    <p>
                                        <?php echo nl2br(htmlspecialchars($consultation->getNote())); ?>
                                    </p>
                                    <p>
                                        <strong class="TitreDeConsultation">Document :</strong>
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
            <script src="assets/js/pages/dash.js"></script>
        </main>
        </body>
        </html>
        <?php
    }
}