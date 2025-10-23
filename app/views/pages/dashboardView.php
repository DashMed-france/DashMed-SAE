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
            <link rel="stylesheet" href="assets/css/components/aside/Evenement.css">
            <link rel="stylesheet" href="assets/css/components/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/components/aside/aside.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>
        <body>

        <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

        <main class="container">

            <section class="dashboard-content-container">
                <form class="searchbar" role="search" action="#" method="get">
                    <span class="left-icon" aria-hidden="true">
                        <img src="assets/img/icons/glass.svg">
                    </span>
                    <input type="search" name="q" placeholder="Search..." aria-label="Rechercher"/>
                    <div class="actions">
                        <button type="button" class="action-btn" aria-label="Notifications">
                            <img src="assets/img/icons/bell.svg">
                        </button>
                        <a href="/?page=profile">
                            <div class="avatar" title="Profil" aria-label="Profil"><img src="" alt=""></div>
                        </a>
                    </div>
                </form>

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
               <div>
                   <h1>Rendez-vous passer</h1>
                   <section class="evenement">
                       <div class="evenement-content">
                       <div class="bloc bloc-gauche">
                           <h1>Radio du genoux</h1>
                           <p class="date">08/10/2025</p>
                           <p>Complications post-opératoires: Suite à une amputation de la jambe gauche</p>
                       </div>
                       <div class="bloc bloc-droite">
                           <section class="doctor-list">
                               <article>
                                   <img src="assets/img/icons/default-profile-icon.svg" alt="photo de profil">
                                   <h1>Dr Alpes</h1>
                                   <h2>Radiologue</h2>
                               </article>
                           </section>
                       </div>
                       </div>

                   </section>
                   <section class="evenement">
                       <div class="evenement-content">
                           <div class="bloc bloc-gauche">
                               <h1>Radio du genoux</h1>
                               <p class="date">08/10/2025</p>
                               <p>Complications post-opératoires: Suite à une amputation de la jambe gauche</p>
                           </div>
                           <div class="bloc bloc-droite">
                               <section class="doctor-list">
                                   <article>
                                       <img src="assets/img/icons/default-profile-icon.svg" alt="photo de profil">
                                       <h1>Dr Alpes</h1>
                                       <h2>Radiologue</h2>
                                   </article>
                               </section>
                           </div>
                       </div>

                   </section>
                   <section class="evenement">
                       <div class="evenement-content">
                           <div class="bloc bloc-gauche">
                               <h1>Radio du genoux</h1>
                               <p class="date">08/10/2025</p>
                               <p>Complications post-opératoires: Suite à une amputation de la jambe gauche</p>
                           </div>
                           <div class="bloc bloc-droite">
                               <section class="doctor-list">
                                   <article>
                                       <img src="assets/img/icons/default-profile-icon.svg" alt="photo de profil">
                                       <h1>Dr Alpes</h1>
                                       <h2>Radiologue</h2>
                                   </article>
                                   <article>
                                       <img src="assets/img/icons/default-profile-icon.svg" alt="photo de profil">
                                       <h1>Dr Alpes</h1>
                                       <h2>Radiologue</h2>
                                   </article>
                               </section>
                           </div>
                       </div>

                   </section>
               </div>
                <div>
                    <h1>Rendez-vous a venir</h1>
                    <section class="evenement">
                        <div class="evenement-content">
                            <div class="bloc bloc-gauche">
                                <h1>Radio du genoux</h1>
                                <p class="date">25/10/2025</p>
                                <p>Complications post-opératoires: Suite à une amputation de la jambe gauche</p>
                            </div>
                            <div class="bloc bloc-droite">
                                <section class="doctor-list">
                                    <article>
                                        <img src="assets/img/icons/default-profile-icon.svg" alt="photo de profil">
                                        <h1>Dr Alpes</h1>
                                        <h2>Radiologue</h2>
                                    </article>
                                </section>
                            </div>
                        </div>

                    </section>
                    <section class="evenement">
                        <div class="evenement-content">
                            <div class="bloc bloc-gauche">
                                <h1>Radio du genoux</h1>
                                <p class="date">25/10/2025</p>
                                <p>Complications post-opératoires: Suite à une amputation de la jambe gauche</p>
                            </div>
                            <div class="bloc bloc-droite">
                                <section class="doctor-list">
                                    <article>
                                        <img src="assets/img/icons/default-profile-icon.svg" alt="photo de profil">
                                        <h1>Dr Alpes</h1>
                                        <h2>Radiologue</h2>
                                    </article>
                                </section>
                            </div>
                        </div>

                    </section>
                    <section class="evenement">
                        <div class="evenement-content">
                            <div class="bloc bloc-gauche">
                                <h1>Radio du genoux</h1>
                                <p class="date">25/10/2025</p>
                                <p>Complications post-opératoires: Suite à une amputation de la jambe gauche</p>
                            </div>
                            <div class="bloc bloc-droite">
                                <section class="doctor-list">
                                    <article>
                                        <img src="assets/img/icons/default-profile-icon.svg" alt="photo de profil">
                                        <h1>Dr Alpes</h1>
                                        <h2>Radiologue</h2>
                                    </article>
                                </section>
                            </div>
                        </div>

                    </section>
                </div>
            </aside>

            <script src="assets/js/dash.js"></script>
        </main>
        </body>
        </html>
        <?php
    }
}