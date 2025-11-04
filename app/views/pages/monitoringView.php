<?php

namespace modules\views\pages;

class monitoringView
{
    private $consultationsPassees;
    private $consultationsFutures;

    public function __construct($consultationsPassees = [], $consultationsFutures = []) {
        $this->consultationsPassees = $consultationsPassees;
        $this->consultationsFutures = $consultationsFutures;
    }


    public function show(): void{
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
            <link rel="stylesheet" href="assets/css/monitoring.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/card2.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/components/popup.css">
            <link rel="stylesheet" href="assets/css/components/aside/calendar.css">
            <link rel="stylesheet" href="assets/css/components/aside/patient-infos.css">
            <link rel="stylesheet" href="assets/css/components/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/components/aside/aside.css">
            <link rel="stylesheet" href="assets/css/components/aside/Evenement.css">
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
                        <h3>Fréquence respiratoire mesurée</h3>
                        <p class="value">20</p>
                    </article>
                    <article class="card">
                        <h3>Fréquence respiratoire spontanée</h3>
                        <p class="value">14</p>
                    </article>
                    <article class="card">
                        <h3>Fréquence respiratoire regle sur le ventilateur</h3>
                        <p class="value">14</p>
                    </article>

                    <article class="card">
                        <h3>Fréquence respiratoire mesurée sur la capnographie</h3>
                        <p class="value">16</p>
                    </article>

                    <article class="card">
                        <h3>Fraction inspiré en oxygène reglée sur le ventilateur</h3>
                        <p class="value">28</p>
                    </article>

                    <article class="card">
                        <h3>Fraction inspiré en oxygène esurée</h3>
                        <p class="value">56</p>
                    </article>

                    <article class="card">
                        <h3>Fraction expirée de CO2 mesurée</h3>
                        <p class="value">38ml/kg</p>
                    </article>

<!--                    ici-->
                    <article class="card">
                        <h3>Volume courant reglée sur le ventilateur</h3>
                        <p class="value">32ml/kg</p>
                    </article>

                    <article class="card">
                        <h3>Volume courant mesurée</h3>
                        <p class="value">42ml/kg</p>
                    </article>

                    <article class="card">
                        <h3>Volume minute mesurée</h3>
                        <p class="value">38ml/kg</p>
                    </article>

                    <article class="card">
                        <h3>Volume minute mesurée</h3>
                        <p class="value">36ml/kg</p>
                    </article>

                    <article class="card">
                        <h3>Volume minute spontané mesurée</h3>
                        <p class="value">123</p>
                    </article>

                    <article class="card">
                        <h3>Pression expiratoire positive reglée</h3>
                        <p class="value">78</p>
                    </article>

                    <article class="card">
                        <h3>Pression expiratoire positive mesurée</h3>
                        <p class="value">38</p>
                    </article>

                    <article class="card">
                        <h3>Temps inspiratoire reglé</h3>
                        <p class="value">14</p>
                    </article>

                    <article class="card">
                        <h3>Temps inspiratoire mesurée</h3>
                        <p class="value">46</p>
                    </article>

                    <article class="card">
                        <h3>Temps expiration mesurée</h3>
                        <p class="value">55</p>
                    </article>

                    <article class="card">
                        <h3>temps Inspi/ Temps expiratoire reglé</h3>
                        <p class="value">?</p>
                    </article>

                    <article class="card">
                        <h3>temps Inspi/ Temps expiratoire reglé</h3>
                        <p class="value">?</p>
                    </article>

                    <article class="card">
                        <h3>Pression des voies aerienne moyenne mesurée</h3>
                        <p class="value">148</p>
                    </article>

                    <article class="card">
                        <h3>Pression fes voies aérienne maximales</h3>
                        <p class="value">89</p>
                    </article>

                    <article class="card">
                        <h3>Pression de plateau</h3>
                        <p class="value">26</p>
                    </article>

                    <article class="card">
                        <h3>Aide inspiratoire reglée</h3>
                        <p class="value">?</p>
                    </article>

                    <article class="card">
                        <h3>Mode ventilatoire reglé</h3>
                        <p class="value">?</p>
                    </article>

                    <article class="card">
                        <h3>Saturation pulsée en O2</h3>
                        <p class="value">88</p>
                    </article>

                    <article class="card">
                        <h3>Volumen courant expiré mesuré</h3>
                        <p class="value">?</p>
                    </article>


                    <!--                    <article class="card2">-->
<!--                        <div class="card-header">-->
<!--                            <div class="card-header-left">-->
<!--                                <button class="favoris1">-->
<!--                                    <img src="assets/img/icons/heart.svg" alt="icon de favoris">-->
<!--                                </button>-->
<!--                                <h3>titre du truc</h3>-->
<!--                            </div>-->
<!--                            <div class="card-header-right">-->
<!--                                <button class="favoris">-->
<!--                                    <img src="assets/img/icons/courbe-graph_1.svg" alt="icon de graphique">-->
<!--                                </button>-->
<!--                                <button class="favoris">-->
<!--                                    <img src="assets/img/icons/tube-graph.svg" alt="icon de graphique">-->
<!--                                </button>-->
<!--                                <button class="favoris">-->
<!--                                    <img src="assets/img/icons/etoiles-graph_1.svg" alt="icon de graphique">-->
<!--                                </button>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                        <div class="carre-noir"></div>-->
<!--                    </article>-->

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
                    <h1>Consultations effectuées</h1>
                    <?php if (!empty($this->consultationsPassees)): ?>
                        <?php
                        $dernieresConsultations = array_slice($this->consultationsPassees, -3);
                        $index = 0;
                        foreach ($dernieresConsultations as $consultation):
                            $classeEvenement = ($index % 2 == 0) ? 'evenement1' : 'evenement';
                            $classeDate = ($index % 2 == 0) ? 'date1' : 'date';
                            $index++;
                            ?>
                            <section class="<?php echo $classeEvenement; ?>">
                                <div class="evenement-content">
                                    <div class="bloc bloc-gauche">
                                        <p class="<?php echo $classeDate; ?>">
                                            <?php echo htmlspecialchars($consultation->getDate()); ?>
                                            <strong><?php echo htmlspecialchars($consultation->getEvenementType()); ?></strong>
                                        </p>
                                    </div>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Aucune consultation effectuée</p>
                    <?php endif; ?>

                    <a href="/?page=medicalprocedure" style="text-decoration: none; color: inherit;">
                        <p class="bouton-consultations">Afficher plus de contenu</p>
                    </a>
                </div>
                <div>
                    <h1>Consultations futures</h1>
                    <?php if (!empty($this->consultationsFutures)): ?>
                        <?php
                        $prochainesConsultations = array_slice($this->consultationsFutures, 0, 3);
                        $index = 0;
                        foreach ($prochainesConsultations as $consultation):
                            $classeEvenement = ($index % 2 == 0) ? 'evenement1' : 'evenement';
                            $classeDate = ($index % 2 == 0) ? 'date1' : 'date';
                            $index++;
                            ?>
                            <section class="<?php echo $classeEvenement; ?>">
                                <div class="evenement-content">
                                    <div class="bloc bloc-gauche">
                                        <p class="<?php echo $classeDate; ?>">
                                            <?php echo htmlspecialchars($consultation->getDate()); ?>
                                            <strong><?php echo htmlspecialchars($consultation->getEvenementType()); ?></strong>
                                        </p>
                                    </div>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Aucune consultation future</p>
                    <?php endif; ?>
                    <br>
                </div>
            </aside>
        </main>

        <script src="assets/js/auth/popup-cards.js"></script>
        </body>
        </html>
        <?php
    }
}