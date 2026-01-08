<?php

namespace modules\views\pages\static;

/**
 * Class HomepageView | Vue Page d'Accueil
 *
 * Displays the main public homepage.
 * Affiche la page d'accueil principale publique.
 *
 * Presents DashMed goals and links to auth.
 * Présente les objectifs de DashMed et les liens vers l'authentification.
 *
 * @package DashMed\Modules\Views\Pages\Static
 * @author DashMed Team
 * @license Proprietary
 */
class HomepageView
{
    /**
     * Renders the homepage HTML.
     * Affiche le contenu HTML de la page d'accueil.
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
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>DashMed — Plateforme de suivi médical</title>
            <meta name="description" content="DashMed est une plateforme
        qui simplifie le suivi médical entre médecins et patients.">
            <meta name="keywords" content="dashmed, suivi médical, santé, patient, médecin, plateforme santé">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
            <link rel="stylesheet" href="assets/css/style.css">
            <link id="theme-style" rel="stylesheet" href="/assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/home.css">
            <link rel="stylesheet" href="assets/css/components/buttons.css">
            <link rel="stylesheet" href="assets/css/components/header.css">
            <link rel="stylesheet" href="assets/css/components/footer.css">
        </head>

        <body>
            <header class="nav-fixed">
                <nav class="nav-pill">
                    <section>
                        <article>
                            <div class="brand">
                                <img src="assets/img/logo.svg" alt="Logo DashMed" class="brand-logo">
                            </div>
                            <button class="brand mobile" onclick="toggleDropdownMenu()">
                                <img src="assets/img/logo.svg" alt="Logo DashMed" class="brand-logo">
                            </button>
                        </article>
                        <article class="nav-links" id="links">
                            <a href="?page=homepage">Accueil</a>
                            <a href="?page=about">A&nbsp;propos</a>
                        </article>
                    </section>
                    <div class="nav-time" id="clock">00:00</div>
                    <section class="nav-actions">
                        <a class="btn btn-primary" href="/?page=login">Connexion</a>
                        <a class="btn btn-secondary" href="/?page=signup">S’inscrire</a>
                    </section>

                </nav>
            </header>
            <main class="container homepage-container">
                <section class="hero">
                    <h1 class="title">
                        <span class="text-dark">Dash</span><span class="text-primary">Med</span>
                    </h1>
                    <p class="subtitle">Gérez facilement vos patients</p>
                    <div class="hero-actions">
                        <a class="btn btn-primary btn-lg" href="/?page=login">Se connecter</a>
                        <a class="btn btn-secondary btn-lg" href="/?page=signup">S’inscrire</a>
                    </div>
                </section>

                <section class="features-grid">
                    <article class="feature-card">
                        <div class="feature-icon">
                            <!-- Icon placeholder or SVG -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <h3>Relation Patient-Médecin</h3>
                        <p>
                            Derrière chaque traitement, il y a une relation entre un patient et son médecin.
                            DashMed a été créé pour renforcer ce lien essentiel, en offrant un espace unique où
                            l’information circule de façon claire et sécurisée.
                        </p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                            </svg>
                        </div>
                        <h3>Gestion Simplifiée</h3>
                        <p>
                            L’idée est simple : permettre aux soignants de se concentrer sur leurs
                            patients plutôt que sur la gestion administrative, et offrir aux
                            patients une meilleure compréhension et un meilleur suivi de leur parcours de soins.
                        </p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="9" y1="21" x2="9" y2="9"></line>
                            </svg>
                        </div>
                        <h3>Tableau de Bord Intuitif</h3>
                        <p>
                            Avec DashMed, chaque consultation, chaque prescription et chaque rappel trouve sa place dans un
                            tableau de bord intuitif. La démarche repose sur la confiance et la transparence : protéger les
                            données de santé tout en fluidifiant les échanges.
                        </p>
                    </article>
                </section>
            </main>
            <footer>
                <svg width="100%" viewBox="0 0 1920 241" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="wave" d="M1920 208.188L1880 191.782C1840 175.375 1760 142.563 1680 131.592C1600
                121.03 1520 131.284 1440 109.751C1360 88.2179 1280 32.8472 1200 11.3142C1120 -10.2189 1040
                0.0349719 960 33.1548C880 65.6595 800 121.03 720 137.129C640 153.842 560 131.284 480 137.129C400
                142.563 320 175.375 240 169.941C160 164.096 79.9999 121.03 39.9999 98.7794L-5.72205e-05
                76.9387V241H39.9999C79.9999 241 160 241 240 241C320 241 400 241 480 241C560 241 640 241 720
                241C800 241 880 241 960 241C1040 241 1120 241 1200 241C1280 241 1360 241 1440 241C1520 241 1600
                241 1680 241C1760 241 1840 241 1880 241H1920V208.188Z" />
                </svg>
                <section>
                    <section class="footer">
                        <article>
                            <p><a href="/?page=legalnotice" class="btn btn-primary">Mentions légales</a></p>
                        </article>
                        <article>
                            <p>© DashMed — <span id="year"></span> · Tous droits réservés</p>
                        </article>
                        <article>
                            <p><a href="/?page=sitemap" class="btn btn-primary">Plan du site</a></p>
                        </article>
                    </section>
                </section>
            </footer>
            <script src="assets/js/pages/static/home.js"></script>
            <script src="assets/js/pages/dash.js"></script>

        </body>

        </html>
        <?php
    }
}
