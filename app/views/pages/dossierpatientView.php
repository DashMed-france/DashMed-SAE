<?php

namespace modules\views\pages;

class dossierpatientView
{
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
                <header class="dp-card dp-header">
                    <div class="dp-patient">
                        <img class="dp-avatar" src="assets/img/icons/default-profile-icon.svg" alt="Photo patient" />
                        <div class="dp-id">
                            <h2 class="dp-name">Marinette Dupain-Cheng - 18ans </h2>
                        </div>
                    </div>
                    <div class="dp-actions">
                        <button class="dp-btn dp-btn-primary"><img src="assets/img/icons/plus.svg" alt="logo plus" />Ajouter consultation</button>
                        <button class="dp-btn dp-btn-ghost" aria-label="Paramètres"> <img src="assets/img/icons/settings.svg" alt="logo settings" /></button>
                    </div>
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
                                    <div class="dp-title">Antécédent médicaux :</div>
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
                        <button id="aside-show-btn" onclick="toggleAside()">☰</button>
                        <aside id="aside">
                            <section class="dp-card">
                                <div class="dp-title"><h3>Dernières donnée</h3></div>
                                <ul class="dp-vitals">
                                    <li>SpO₂ : 95%</li>
                                    <li>PAS / PAD : 140/90 mmHg</li>
                                    <li>Fréquence cardiaque (FC) : 80 bpm</li>
                                    <li>Fréquence respiratoire (FR) : 18 c/min</li>
                                    <li>Température : 37°C</li>
                                    <li>Température : 37°C</li>
                                    <li>PVC : 5 mmHg</li>
                                    <li>PIC : 12 mmHg</li>
                                </ul>
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
                    </div>
                </section>
            </section>
            <script src="assets/js/dash.js"></script>
        </main>
        </body>
        </html>
        <?php
    }
}