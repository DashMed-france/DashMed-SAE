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
 * Affiche l'interface du tableau de bord de la plateforme DashMed.
 *
 * Responsabilités :
 *  - Inclure les composants de mise en page nécessaires (barre latérale, infos patient, etc.)
 *  - Afficher les cartes liées à la santé (rythme cardiaque, O₂, tension, température)
 *  - Rendre les sections de recherche et de calendrier pour un accès rapide
 *
 * @see /assets/js/dash.js
 */

class DashboardView
{
    private $consultationsPassees;
    private $consultationsFutures;
    private array $rooms;

    public function __construct(array $consultationsPassees = [], array $consultationsFutures = [], array $rooms = [])
    {
        $this->consultationsPassees = $consultationsPassees;
        $this->consultationsFutures = $consultationsFutures;
        $this->rooms = $rooms;
    }

    function getConsultationId($consultation)
    {
        $doctor = preg_replace('/[^a-zA-Z0-9]/', '-', $consultation->getDoctor());
        $dateObj = \DateTime::createFromFormat('d/m/Y', $consultation->getDate());
        if(!$dateObj){
            $dateObj = \DateTime::createFromFormat('Y-m-d', $consultation->getDate());
        }
        $date = $dateObj ? $dateObj->format('Y-m-d') : $consultation->getDate();
        return $doctor . '-' . $date;
    }

    /**
     * Génère la structure HTML complète de la page du tableau de bord.
     *
     * Inclut la barre latérale, la barre de recherche supérieure, le panneau d'informations patient,
     * le calendrier et la liste des médecins.
     * Cette vue n'effectue aucune logique métier — elle se limite uniquement au rendu.
     *
     * @return void
     */
    public function show(): void
    {

        $current = $_COOKIE['room_id'] ?? null;
        if ($current !== null && $current !== '' && ctype_digit((string)$current)) {
            $current = (int)$current;
        } else {
            $current = null;
        }


        $h = static function ($v): string {
            return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
        }
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
            <meta name="description" content="Tableau de bord privé pour les médecins,
             accessible uniquement aux utilisateurs authentifiés.">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/dash.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/components/aside/calendar.css">
            <link rel="stylesheet" href="assets/css/components/aside/patient-infos.css">
            <link rel="stylesheet" href="assets/css/components/aside/events.css">
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

                    <select id="id_rooms" name="room" onchange="location.href='/?page=dashboard&room=' + this.value">
                        <option value="" <?= $current === null ? 'selected' : '' ?>>-- Sélectionnez une chambre --</option>

                        <?php foreach ($this->rooms as $s):
                            $id = (int)($s['room_id'] ?? 0);
                            if ($id <= 0) continue;
                            $sel = ($current !== null && $current === $id) ? 'selected' : '';
                            ?>
                            <option value="<?= $id ?>" <?= $sel ?>>Chambre <?= $id ?></option>
                        <?php endforeach; ?>
                    </select>

                </section>
                <div>
                    <h1>
                        Consultations
                        <div id="sort-container">
                            <button id="sort-btn">Trier ▾</button>
                            <div id="sort-menu">
                                <button class="sort-option" data-order="asc">Ordre croissant</button>
                                <button class="sort-option" data-order="desc">Ordre décroissant</button>
                            </div>
                        </div>
                    </h1>

                    <?php
                    $toutesConsultations = array_merge(
                            $this->consultationsPassees ?? [],
                            $this->consultationsFutures ?? []
                    );

                    if (!empty($toutesConsultations)):
                        $consultationsAffichees = array_slice($toutesConsultations, -7);
                        ?>
                        <section class="evenement" id="consultation-list">
                            <?php foreach ($consultationsAffichees as $consultation): ?>
                                <a
                                        href="/?page=medicalprocedure#<?php echo $this->getConsultationId($consultation); ?>"
                                        class="consultation-link"
                                        data-date="<?php
                                        $dateObj = \DateTime::createFromFormat('d/m/Y', $consultation->getDate())
                                                ?: \DateTime::createFromFormat('Y-m-d', $consultation->getDate());
                                        echo $dateObj ? $dateObj->format('Y-m-d') : $consultation->getDate();
                                        ?>"
                                >
                                    <div class="evenement-content">
                                        <span class="date"><?php echo htmlspecialchars($consultation->getDate()); ?></span>
                                        <strong><?php echo htmlspecialchars($consultation->getEvenementType()); ?></strong>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </section>
                    <?php else: ?>
                        <p>Aucune consultation</p>
                    <?php endif; ?>



                    <a href="/?page=medicalprocedure" style="text-decoration: none; color: inherit;">
                        <p class="bouton-consultations">Afficher plus de contenu</p>
                    </a>
                    <br>
                </div>
            </aside>
            <script src="assets/js/pages/dash.js"></script>
        </main>
        </body>
        </html>
        <?php
    }
}