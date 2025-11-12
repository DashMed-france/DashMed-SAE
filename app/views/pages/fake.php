<?php

namespace modules\views\pages;

class dossierpatientView
{
    private $consultationsPassees;
    private $consultationsFutures;

    public function __construct($consultationsPassees = [], $consultationsFutures = []) {
        $this->consultationsPassees = $consultationsPassees;
        $this->consultationsFutures = $consultationsFutures;
    }

    public function show(): void
    {
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
            <meta name="description" content="Tableau de bord privé pour les médecins, accessible uniquement aux utilisateurs authentifiés.">
            <link rel="stylesheet" href="/assets/css/dossierpatient.css">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/dash.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/aside/calendar.css">
            <link rel="stylesheet" href="assets/css/components/aside/patient-infos.css">
            <link rel="stylesheet" href="assets/css/components/aside/Evenement.css">
            <link rel="stylesheet" href="assets/css/components/popup.css">
            <link rel="stylesheet" href="assets/css/components/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/components/aside/aside.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>
        <body>

        <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

        <main class="container nav-space ">
            <section class="dashboard-content-container">
                <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>
                <header class="dp-card dp-header">
                    <div class="dp-patient">
                        <img class="dp-avatar" src="assets/img/icons/default-profile-icon.svg" alt="Photo patient" />
                        <h2 class="dp-name">Marinette Dupain-Cheng - 18ans </h2>
                    </div>
                    <button class="dp-btn" aria-label="Paramètres"> <img src="assets/img/icons/edit.svg" alt="logo edit" /></button>
                </header>
                <section class="dp-wrap">
                    <div class="dp-grid">
                        <div class="dp-left">
                            <div class="dp-duo">
                                <section class="dp-card dp-soft-yellow">
                                    <div class="dp-title">Cause d'admission :</div>
                                    <div class="dp-texte">Ischémie critique de la jambe gauche, suite à un accident de la route.
                                        Cette situation a rendu une amputation en urgence nécessaire pour préserver la vie du patient.</div>
                                </section>
                                <section class="dp-card dp-soft-green">
                                    <div class="dp-title">Antécédents médicaux :</div>
                                    <ul class="dp-list">
                                        <li>Allergies :
                                            <ul>
                                                <li>Abricot</li>
                                                <li>Mangue</li>
                                                <li>Pénicillines</li>
                                            </ul>
                                        </li>
                                        <li>Appendicectomie 07/03/2024</li>
                                    </ul>
                                </section>
                            </div>
                            <section class="dp-card dp-soft-lilac">
                                <div class="dp-title"> Médecins :</div>
                                <ul class="dp-docs">
                                    <li class="dp-doc"><img src="assets/img/icons/default-profile-icon.svg" alt="Photo médecins"><div><div class="dp-doc-name">Natalie Kaydi</div><div class="dp-doc-role">Infirmière</div></div></li>
                                    <li class="dp-doc"><img src="assets/img/icons/default-profile-icon.svg" alt="Photo médecins"><div><div class="dp-doc-name">Benoît Midis</div><div class="dp-doc-role">Anesthésiste</div></div></li>
                                    <li class="dp-doc"><img src="assets/img/icons/default-profile-icon.svg" alt="Photo médecins"><div><div class="dp-doc-name">Mila Idrissyi</div><div class="dp-doc-role">Chirurgienne</div></div></li>
                                    <li class="dp-doc"><img src="assets/img/icons/default-profile-icon.svg" alt="Photo médecins"><div><div class="dp-doc-name">Antoine Cedjen</div><div class="dp-doc-role">Chirurgien</div></div></li>
                                    <li class="dp-doc"><img src="assets/img/icons/default-profile-icon.svg" alt="Photo médecins"><div><div class="dp-doc-name">Camilla Nothbr</div><div class="dp-doc-role">Kinésithérapeute</div></div></li>
                                    <li class="dp-doc"><img src="assets/img/icons/default-profile-icon.svg" alt="Photo médecins"><div><div class="dp-doc-name">Sabrina Pokmd</div><div class="dp-doc-role">Infectiologue</div></div></li>
                                    <li class="dp-doc"><img src="assets/img/icons/default-profile-icon.svg" alt="Photo médecins"><div><div class="dp-doc-name">Rosanne Lrheb</div><div class="dp-doc-role">Psychologue</div></div></li>
                                    <li class="dp-doc"><img src="assets/img/icons/default-profile-icon.svg" alt="Photo médecins"><div><div class="dp-doc-name">Arthur Ottoct</div><div class="dp-doc-role">Orthoprothésiste</div></div></li>
                                    <li class="dp-doc"><img src="assets/img/icons/default-profile-icon.svg" alt="Photo médecins"><div><div class="dp-doc-name">Charlie Mepkdjq</div><div class="dp-doc-role">Généraliste</div></div></li>
                                </ul>
                            </section>
                        </div>
                    </div>
                </section>
            </section>
            <script src="assets/js/pages/dash.js"></script>
            <script src="assets/js/pages/popup.js"></script>
        </main>
        </body>
        </html>
        <?php
    }
}