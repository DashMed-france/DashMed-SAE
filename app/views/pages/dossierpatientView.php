<?php

namespace modules\views\pages;

class dossierpatientView
{
    private $consultationsPassees;
    private $consultationsFutures;
    private $patientData;
    private $doctors;
    private $msg;

    public function __construct($consultationsPassees = [], $consultationsFutures = [], $patientData = [], $doctors = [], $msg = null) {
        $this->consultationsPassees = $consultationsPassees;
        $this->consultationsFutures = $consultationsFutures;
        $this->patientData = $patientData;
        $this->doctors = $doctors;
        $this->msg = $msg;
    }

    public function show(): void
    {
        // Génération du token CSRF
        if (!isset($_SESSION['csrf_patient'])) {
            $_SESSION['csrf_patient'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_patient'];

        $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
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
            <link rel="stylesheet" href="assets/css/dossierpatient.css">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/components/popupmodification.css">
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">
            <link rel="stylesheet" href="assets/css/components/aside/calendar.css">
            <link rel="stylesheet" href="assets/css/components/aside/patient-infos.css">
            <link rel="stylesheet" href="assets/css/components/aside/Evenement.css">
            <link rel="stylesheet" href="assets/css/components/aside/doctor-list.css">
            <link rel="stylesheet" href="assets/css/components/aside/aside.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>
        <body>

        <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

        <main class="container nav-space">
            <section class="dashboard-content-container">
                <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>

                <?php if ($this->msg): ?>
                    <div class="message-box <?= $h($this->msg['type']) ?>">
                        <?= $h($this->msg['text']) ?>
                    </div>
                <?php endif; ?>

                <header class="dp-card dp-header">
                    <div class="dp-patient">
                        <img class="dp-avatar" src="assets/img/icons/default-profile-icon.svg" alt="Photo patient" />
                        <h2 class="dp-name">
                            <?= $h($this->patientData['first_name'] ?? 'Patient') ?>
                            <?= $h($this->patientData['last_name'] ?? 'Inconnu') ?> -
                            <?= $h($this->patientData['age'] ?? 0) ?>ans
                        </h2>
                    </div>
                    <button class="dp-btn" aria-label="Modifier les informations" onclick="openEditModal()">
                        <img src="assets/img/icons/edit.svg" alt="logo edit" />
                    </button>
                </header>

                <section class="dp-wrap">
                    <div class="dp-grid">
                        <div class="dp-left">
                            <div class="dp-duo">
                                <section class="dp-card dp-soft-yellow">
                                    <div class="dp-title">Cause d'admission :</div>
                                    <div class="dp-texte" id="admission-text">
                                        <?= $h($this->patientData['admission_cause'] ?? 'Non renseigné') ?>
                                    </div>
                                </section>

                                <section class="dp-card dp-soft-green">
                                    <div class="dp-title">Antécédents médicaux :</div>
                                    <div id="medical-history-text">
                                        <?= nl2br($h($this->patientData['medical_history'] ?? 'Non renseigné')) ?>
                                    </div>
                                </section>
                            </div>
                            <section class="dp-card dp-soft-lilac">
                                <div class="dp-title">Médecins :</div>
                                <ul class="dp-docs">
                                    <?php if (!empty($this->doctors)): ?>
                                        <?php foreach ($this->doctors as $doctor): ?>
                                            <li class="dp-doc">
                                                <img src="assets/img/icons/default-profile-icon.svg" alt="Photo médecin">
                                                <div>
                                                    <div class="dp-doc-name">
                                                        <?= $h($doctor['first_name'] ?? '') ?> <?= $h($doctor['last_name'] ?? '') ?>
                                                    </div>
                                                    <div class="dp-doc-role">
                                                        <?= $h($doctor['profession_name'] ?? 'Médecin') ?>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="dp-doc">
                                            <div>
                                                <div class="dp-doc-name">Aucun médecin assigné</div>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </section>
                        </div>
                    </div>
                </section>
            </section>
        </main>

        <!-- Modal d'édition -->
        <!-- Modal d'édition -->
        <div class="edit-modal" id="editModal">
            <div class="edit-modal-content">
                <h2>Modifier les informations patients</h2>
                <form method="POST" action="/?page=dossierpatient">
                    <input type="hidden" name="csrf" value="<?= $h($csrfToken) ?>">
                    <input type="hidden" name="id_patient" value="<?= $h($this->patientData['id_patient'] ?? 1) ?>">

                    <label for="first_name">Prénom :</label>
                    <input
                            type="text"
                            name="first_name"
                            id="first_name"
                            required
                            value="<?= $h($this->patientData['first_name'] ?? '') ?>"
                            placeholder="Prénom du patient"
                    >

                    <label for="last_name">Nom :</label>
                    <input
                            type="text"
                            name="last_name"
                            id="last_name"
                            required
                            value="<?= $h($this->patientData['last_name'] ?? '') ?>"
                            placeholder="Nom du patient"
                    >

                    <label for="birth_date">Date de naissance :</label>
                    <input
                            type="date"
                            name="birth_date"
                            id="birth_date"
                            value="<?= $h($this->patientData['birth_date'] ?? '') ?>"
                            max="<?= date('Y-m-d') ?>"
                            placeholder="AAAA-MM-JJ"
                    >
                    <small style="font-size: 0.85em; margin-top: -8px;">
                        Âge actuel : <?= $h($this->patientData['age'] ?? 0) ?> ans
                    </small>

                    <label for="admission_cause">Cause d'admission :</label>
                    <textarea
                            name="admission_cause"
                            id="admission_cause"
                            required
                            placeholder="Décrivez la raison de l'admission du patient..."
                    ><?= $h($this->patientData['admission_cause'] ?? '') ?></textarea>

                    <label for="medical_history">Antécédents médicaux :</label>
                    <textarea
                            name="medical_history"
                            id="medical_history"
                            required
                            placeholder="Liste des allergies, opérations antérieures, etc."
                    ><?= $h($this->patientData['medical_history'] ?? '') ?></textarea>

                    <div class="button-group">
                        <button type="button" class="btn-cancel" onclick="closeEditModal()">Annuler</button>
                        <button type="submit" class="btn-save">✓ Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            function openEditModal() {
                document.getElementById('editModal').classList.add('active');
            }

            function closeEditModal() {
                document.getElementById('editModal').classList.remove('active');
            }

            // Fermer la modal en cliquant en dehors
            document.getElementById('editModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeEditModal();
                }
            });

            // Fermer avec la touche Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeEditModal();
                }
            });

            // Calculer et afficher l'âge en temps réel lors de la modification
            const birthDateInput = document.getElementById('birth_date');
            const ageDisplay = birthDateInput.nextElementSibling;

            birthDateInput.addEventListener('change', function() {
                if (this.value) {
                    const birthDate = new Date(this.value);
                    const today = new Date();
                    let age = today.getFullYear() - birthDate.getFullYear();
                    const monthDiff = today.getMonth() - birthDate.getMonth();

                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                        age--;
                    }

                    ageDisplay.textContent = `Âge calculé : ${age} ans`;
                } else {
                    ageDisplay.textContent = 'Âge actuel : <?= $h($this->patientData['age'] ?? 0) ?> ans';
                }
            });
        </script>

        <script src="assets/js/pages/dash.js"></script>
        <script src="assets/js/pages/popup.js"></script>
        <script src="assets/js/pages/.js"></script>

        </body>
        </html>
        <?php
    }
}