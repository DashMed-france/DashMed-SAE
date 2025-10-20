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
 * Affiche l’interface du tableau de bord de la plateforme DashMed.
 *
 * Responsabilités :
 *  - Inclure les composants de mise en page nécessaires (barre latérale, infos patient, etc.)
 *  - Afficher les cartes liées à la santé (rythme cardiaque, O₂, tension, température)
 *  - Rendre les sections de recherche et de calendrier pour un accès rapide
 *
 * @see /assets/js/dash.js
 */

class dashboardView
{
    /**
     * Génère la structure HTML complète de la page du tableau de bord.
     *
     * Inclut la barre latérale, la barre de recherche supérieure, le panneau d’informations patient,
     * le calendrier et la liste des médecins.
     * Cette vue n’effectue aucune logique métier — elle se limite uniquement au rendu.
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
            <title>DashMed - Dashboard</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <meta name="author" content="DashMed Team">
            <meta name="keywords" content="dashboard, santé, médecins, patients, DashMed">
            <meta name="description" content="Tableau de bord privé pour les médecins, accessible uniquement aux utilisateurs authentifiés.">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/dash.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/components/aside/calendar.css">
            <link rel="stylesheet" href="assets/css/components/aside/patient-infos.css">
            <link rel="stylesheet" href="assets/css/components/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/components/aside/aside.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>
        <body>

        <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

        <main class="container nav-space aside-space">

            <section class="dashboard-content-container">
                <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>

                <section class="cards-container">
                    <article class="card">
                        <h3>Fréquence cardiaque</h3>
                        <p class="value">72 bpm</p>
                    </article>

                    <article class="card">
                        <h3>Saturation O₂</h3>
                        <p class="value">98 %</p>
                    </article>

                    <article class="card">
                        <h3>Tension artérielle</h3>
                        <p class="value">118/76 mmHg</p>
                    </article>

                    <article class="card">
                        <h3>Température</h3>
                        <p class="value">36,7 °C</p>
                    </article>
                </section>
            </section>
            <button id="aside-show-btn" onclick="toggleAside()">☰</button>
            <aside id="aside">
                <section class="patient-infos">
                    <h1>Marinette dupain-cheng</h1>
                    <p>18 ans</p>
                    <p>Complications post-opératoires: Suite à une amputation de la jambe gauche</p>
                </section>
                <section class="calendar">
                    <article class="current-month">
                        <div class="selection-month">
                            <button id="prev" type="button" aria-label="Mois précédent">‹</button>
                            <div>
                                <span id="month"></span>
                                <span id="year"></span>
                            </div>
                            <button id="next" type="button" aria-label="Mois suivant">›</button>
                        </div>
                        <div class="day-list">
                            <span>lun</span>
                            <span>mar</span>
                            <span>mer</span>
                            <span>jeu</span>
                            <span>ven</span>
                            <span>sam</span>
                            <span>dim</span>
                        </div>
                    </article>
                    <article id="days"></article>
                </section>
                <section class="doctor-list">
                    <article>
                        <img src="assets/img/icons/default-profile-icon.svg" alt="photo de profil">
                        <h1>Dr Alpes</h1>
                    </article>
                    <article>
                        <img src="assets/img/icons/default-profile-icon.svg" alt="photo de profil">
                        <h1>Dr Alpes</h1>
                    </article>
                    <article>
                        <img src="assets/img/icons/default-profile-icon.svg" alt="photo de profil">
                        <h1>Dr Alpes</h1>
                    </article>
                </section>
            </aside>
            <script src="assets/js/dash.js"></script>
        </main>
        </body>
        </html>
        <?php
    }
}