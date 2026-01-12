<?php

namespace modules\views\pages\static;

/**
 * Class AboutView | Vue À Propos
 *
 * Displays the "About" page of DashMed.
 * Affiche la page "À propos" de DashMed.
 *
 * Presents the platform, objectives, and team.
 * Présente la plateforme, ses objectifs et l'équipe.
 *
 * @package DashMed\Modules\Views\Pages\Static
 * @author DashMed Team
 * @license Proprietary
 */
class AboutView
{
    /**
     * Renders the About page HTML.
     * Affiche le contenu HTML de la page à propos.
     *
     * Includes navigation, hero section, and footer.
     * Inclut la navigation, la section hero et le pied de page.
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
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>À propos - DashMed</title>
            <link rel="stylesheet" href="assets/css/components/info-card.css">
            <link rel="stylesheet" href="assets/css/pages/about.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
            <link rel="stylesheet" href="assets/css/base/style.css">
            <link id="theme-style" rel="stylesheet" href="/assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/pages/landing.css">
            <link rel="stylesheet" href="assets/css/components/buttons.css">
            <link rel="stylesheet" href="assets/css/layout/header.css">
            <link rel="stylesheet" href="assets/css/layout/footer.css">
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
            <main class="apropos-container">
                <h1>À propos de <span id="dash">Dash</span><span style="color: var(--primary-color)">Med</span></h1>
                <p class="subtitle">
                    Une solution pensée pour faciliter la vie des soignants et améliorer le suivi des patients.
                </p>
                <div class="apropos-grid">
                    <div class="apropos-card">
                        <h3>Notre mission</h3>
                        <p>
                            Simplifier le quotidien médical en centralisant toutes les informations nécessaires
                            à la gestion des consultations, prescriptions
                            et suivis patients dans un espace unique et sécurisé.
                        </p>
                    </div>
                    <div class="apropos-card">
                        <h3>Notre vision</h3>
                        <p>
                            Offrir une plateforme claire, moderne et accessible où le partage d’informations entre
                            soignants et patients se fait de manière fluide, transparente et confidentielle.
                        </p>
                    </div>
                    <div class="apropos-card">
                        <h3>Nos valeurs</h3>
                        <ul>
                            <li><strong>Sécurité :</strong> protéger les données de santé avant tout.</li>
                            <li><strong>Accessibilité :</strong> rendre la technologie intuitive et fluide.</li>
                            <li><strong>Transparence :</strong> favoriser la confiance et la clarté.</li>
                        </ul>
                    </div>
                    <div class="apropos-card">
                        <h3>Notre équipe</h3>
                        <p>
                            DashMed a été imaginé par une équipe d’étudiants passionnés d’informatique et
                            de santé numérique,
                            avec un objectif commun : rendre le numérique plus humain et utile au quotidien.
                        </p>
                    </div>
                </div>
            </main>
            <footer>
                <svg width="100%" height="calc(auto-2px)" viewBox="0 0 1920 241" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="wave" d="M1920 208.188L1880 191.782C1840 175.375
                1760 142.563 1680 131.592C1600 121.03 1520 131.284 1440 109.751C1360
                88.2179 1280 32.8472 1200 11.3142C1120 -10.2189 1040 0.0349719 960 33.1548C880
                65.6595 800 121.03 720 137.129C640 153.842 560 131.284 480 137.129C400 142.563
                320 175.375 240 169.941C160 164.096 79.9999 121.03 39.9999 98.7794L-5.72205e-05
                76.9387V241H39.9999C79.9999 241 160 241 240 241C320 241 400 241 480 241C560 241
                640 241 720 241C800 241 880 241 960 241C1040 241 1120 241 1200 241C1280 241 1360
                241 1440 241C1520 241 1600 241 1680 241C1760 241 1840 241 1880 241H1920V208.188Z" fill="#275AFE" />
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
