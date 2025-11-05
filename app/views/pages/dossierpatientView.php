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

        <main class="container nav-space aside-space">
            <section class="dashboard-content-container">
                <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>
                <header class="dp-card dp-header">
                    <div class="dp-patient">
                        <img class="dp-avatar" src="assets/img/icons/default-profile-icon.svg" alt="Photo patient" />
                        <h2 class="dp-name">Marinette Dupain-Cheng - 18ans </h2>
                    </div>
                    <div class="dp-actions">
                        <button class="dp-btn dp-btn-primary" id="btnAjouterConsultation"><img src="assets/img/icons/plus.svg" alt="logo plus" />Ajouter consultation</button>

                        <!-- Popup calendrier -->
                        <div id="popupConsultation" class="popup">
                            <div class="popup-content">
                                <span class="close">&times;</span>
                                <h2>Planifier une consultation</h2>
                                <form id="formConsultation">

                                    <div class="row-date-time">
                                        <div class="col">
                                            <label for="date">Date :</label>
                                            <input type="date" id="date" name="date" required>
                                        </div>
                                        <div class="col">
                                            <label for="heure">Heure :</label>
                                            <input type="time" id="heure" name="heure" required>
                                        </div>
                                    </div>

                                    <label for="motif">Titre :</label>
                                    <input type="text" name="motif" placeholder="Ex : Ischémie critique de la jambe gauche" required></texte>

                                    <label for="motif">Texte :</label>
                                    <textarea id="motif" name="motif" required></textarea>

                                    <label for="medecin">Médecin :</label>
                                    <input type="text" id="medecin" name="medecin" placeholder="Nom du médecin" required>

                                    <button type="submit" class="dp-btn dp-btn-primary">Enregistrer</button>
                                </form>
                            </div>
                        </div>
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
                        <button id="aside-show-btn" onclick="toggleAside()">☰</button>
                        <aside id="aside">
                            <section class="dp-card">
                                <div class="dp-title"><h3>Dernières données</h3></div>
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