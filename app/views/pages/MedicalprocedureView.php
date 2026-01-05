<?php

namespace modules\views\pages;

use modules\models\Consultation;

/**
 * Vue des Procédures Médicales
 *
 * Cette classe gère l'affichage de la liste des consultations et des procédures médicales
 * associées à un patient. Elle présente les informations sous forme de cartes détaillées
 * et offre des fonctionnalités de tri et de filtrage.
 *
 * @package modules\views\pages
 */
class MedicalprocedureView
{
    /**
     * @var array Liste des consultations à afficher.
     */
    private $consultations;

    /**
     * @var array Liste des médecins pour le formulaire.
     */
    private $doctors;

    /**
     * Constructeur de la vue.
     *
     * @param array $consultations Tableau d'objets Consultation à afficher.
     * @param array $doctors Tableau associatif des médecins.
     */
    public function __construct($consultations = [], $doctors = [])
    {
        $this->consultations = $consultations;
        $this->doctors = $doctors;
    }

    /**
     * Génère un identifiant unique pour une consultation basé sur le médecin et la date.
     * Utiliser pour l'attribut ID des éléments HTML.
     *
     * @param object $consultation L'objet consultation.
     * @return string Identifiant formaté (ex: NomMedecin-YYYY-MM-DD).
     */
    function getConsultationId($consultation)
    {
        $doctor = preg_replace('/[^a-zA-Z0-9]/', '-', $consultation->getDoctor());
        $dateObj = \DateTime::createFromFormat('d/m/Y', $consultation->getDate());
        if (!$dateObj) {
            try {
                $dateObj = new \DateTime($consultation->getDate());
            } catch (\Exception $e) {
                $dateObj = null;
            }
        }
        $date = $dateObj ? $dateObj->format('Y-m-d') : $consultation->getDate();
        return $doctor . '-' . $date;
    }

    /**
     * Formate une date pour l'affichage (JJ-MM-AAAA à HH:MM).
     *
     * @param string $dateStr La date au format string.
     * @return string La date formatée ou la chaîne originale en cas d'erreur.
     */
    function formatDate($dateStr)
    {
        try {
            $dateObj = new \DateTime($dateStr);
            return $dateObj->format('d-m-Y à H:i');
        } catch (\Exception $e) {
            return $dateStr;
        }
    }

    /**
     * Affiche le contenu complet de la page HTML.
     *
     * Génère l'entête, la barre latérale, la barre de recherche et la liste des consultations.
     * Inclut également les scripts nécessaires pour le filtrage interactif.
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
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>DashMed - Dossier Médical</title>
            <meta name="robots" content="noindex, nofollow">
            <meta name="author" content="DashMed Team">
            <meta name="keywords" content="dashboard, santé, médecins, patients, DashMed">
            <meta name="description" content="Tableau de bord privé pour les médecins,
             accessible uniquement aux utilisateurs authentifiés.">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/medicalProcedure.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/card.css">
            <link rel="stylesheet" href="assets/css/consultation.css">
            <link rel="stylesheet" href="assets/css/components/consultation-modal.css">
            <link rel="stylesheet" href="assets/css/components/aside/aside.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
            <style>
                .consultation-date-value {
                    font-family: inherit;
                    white-space: nowrap;
                }
            </style>
        </head>

        <body>

            <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

            <main class="container nav-space">

                <section class="dashboard-content-container">
                    <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>

                    <div id="button-bar">
                        <div id="sort-container">
                            <button id="sort-btn">Trier ▾</button>
                            <div id="sort-menu">
                                <button class="sort-option" data-order="asc">Ordre croissant</button>
                                <button class="sort-option" data-order="desc">Ordre décroissant</button>
                            </div>
                        </div>
                        <div id="sort-container2">
                            <button id="sort-btn2">Options ▾</button>
                            <div id="sort-menu2">
                                <button class="sort-option2">Rendez-vous a venir</button>
                                <button class="sort-option2">Rendez-vous passé</button>
                                <button class="sort-option2">Tout mes rendez-vous</button>
                            </div>
                        </div>
                        <button id="btn-add-consultation" class="btn-primary">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Nouvelle Consultation
                        </button>
                    </div>

                    <section class="consultations-container">
                        <?php if (!empty($this->consultations)): ?>
                            <?php foreach ($this->consultations as $consultation): ?>
                                <article class="consultation" id="<?php echo $this->getConsultationId($consultation); ?>" data-date="<?php
                                   $d = $consultation->getDate();
                                   try {
                                       echo (new \DateTime($d))->format('Y-m-d');
                                   } catch (\Exception $e) {
                                       echo $d;
                                   }
                                   ?>">
                                    <div class="consultation-header">
                                        <div class="header-left">
                                            <div class="icon-box">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z">
                                                    </path>
                                                    <polyline points="14 2 14 8 20 8"></polyline>
                                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                                    <line x1="10" y1="9" x2="8" y2="9"></line>
                                                </svg>
                                            </div>
                                            <h2 class="consultation-title">
                                                <?php echo htmlspecialchars($consultation->getTitle() ?: $consultation->getType()); ?>
                                            </h2>
                                        </div>
                                        <div class="header-right">
                                            <?php
                                            $isPast = false;
                                            try {
                                                $cDate = new \DateTime($consultation->getDate());
                                                $now = new \DateTime();
                                                if ($cDate < $now) {
                                                    $isPast = true;
                                                }
                                            } catch (\Exception $e) {
                                            }
                                            ?>
                                            <span class="date-badge <?php if ($isPast)
                                                echo 'has-tooltip'; ?>" <?php if ($isPast)
                                                      echo 'data-tooltip="Consultation déjà effectuée"'; ?>>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                                </svg>
                                                <?php echo htmlspecialchars($this->formatDate($consultation->getDate())); ?>
                                                <?php if ($isPast): ?>
                                                    <span class="status-dot"></span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="consultation-body">
                                        <div class="consultation-meta-grid">
                                            <div class="meta-item">
                                                <span class="meta-label">Médecin</span>
                                                <span class="meta-value doctor-name">Dr.
                                                    <?php echo htmlspecialchars($consultation->getDoctor()); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Type</span>
                                                <span
                                                    class="meta-value type-badge"><?php echo htmlspecialchars($consultation->getType()); ?></span>
                                            </div>
                                        </div>

                                        <div class="consultation-report-section">
                                            <h3 class="report-label">Compte rendu</h3>
                                            <div class="report-content">
                                                <?php
                                                $note = $consultation->getNote();
                                                if (!empty($note)) {
                                                    echo nl2br(htmlspecialchars($note));
                                                } else {
                                                    echo '<span style="color: #a1a1a6; font-style: italic;">Aucun compte rendu disponible</span>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="consultation-footer">
                                        <?php if ($consultation->getDocument() && $consultation->getDocument() !== 'Aucun'): ?>
                                            <div class="document-section">
                                                <span class="doc-label">Documents joints :</span>
                                                <span class="doc-link">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path
                                                            d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48">
                                                        </path>
                                                    </svg>
                                                    <?php echo htmlspecialchars($consultation->getDocument()); ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div class="document-section empty">
                                                <span class="doc-placeholder">Aucun document joint</span>
                                            </div>
                                        <?php endif; ?>
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
        
        <!-- Modal Ajout Consultation -->
        <div id="add-consultation-modal" class="modal-backdrop hidden">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Nouvelle Consultation</h2>
                    <button id="close-modal-btn" class="close-btn">&times;</button>
                </div>
                <form id="add-consultation-form" method="POST" action="?page=medicalprocedure">
                    <input type="hidden" name="action" value="add_consultation">
                    
                    <div class="form-group">
                        <label for="doctor-select">Médecin</label>
                        <select id="doctor-select" name="doctor_id" required>
                            <option value="">Sélectionner un médecin</option>
                            <?php foreach ($this->doctors as $doc): ?>
                                <option value="<?php echo htmlspecialchars($doc['id_user']); ?>">
                                    Dr. <?php echo htmlspecialchars($doc['last_name'] . ' ' . $doc['first_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="consultation-title">Titre / Motif</label>
                        <input type="text" id="consultation-title" name="consultation_title" required placeholder="Ex: Consultation de suivi">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="consultation-date">Date</label>
                            <input type="date" id="consultation-date" name="consultation_date" required>
                        </div>
                        <div class="form-group">
                            <label for="consultation-time">Heure</label>
                            <input type="time" id="consultation-time" name="consultation_time" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="consultation-type">Type de consultation</label>
                        <select id="consultation-type" name="consultation_type" required>
                            <option value="Générale">Générale</option>
                            <option value="Suivi">Suivi</option>
                            <option value="Urgence">Urgence</option>
                            <option value="Spécialisée">Spécialisée</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="consultation-note">Compte rendu / Notes</label>
                        <textarea id="consultation-note" name="consultation_note" rows="5" placeholder="Détails de la consultation..."></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" id="cancel-modal-btn">Annuler</button>
                        <button type="submit" class="btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

                <script src="assets/js/consultation-filter.js"></script>
                <script src="assets/js/consultation-modal.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        new ConsultationManager({
                            containerSelector: '.consultations-container',
                            itemSelector: '.consultation',
                            dateAttribute: 'data-date',
                            sortBtnId: 'sort-btn',
                            sortMenuId: 'sort-menu',
                            sortOptionSelector: '.sort-option',
                            filterBtnId: 'sort-btn2',
                            filterMenuId: 'sort-menu2',
                            filterOptionSelector: '.sort-option2'
                        });
                    });
                </script>

            </main>
        </body>

        </html>
        <?php
    }
}
