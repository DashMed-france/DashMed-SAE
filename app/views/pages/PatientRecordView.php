<?php

namespace modules\views\pages;

class PatientRecordView
{
    private $consultationsPassees;
    private $consultationsFutures;
    private $patientData;
    private $doctors;
    private $msg;

    public function __construct($consultationsPassees = [], $consultationsFutures = [], $patientData = [], $doctors = [], $msg = null)
    {
        $this->consultationsPassees = $consultationsPassees;
        $this->consultationsFutures = $consultationsFutures;
        $this->patientData = $patientData;
        // Ensure doctors is an array, default to empty if null
        $this->doctors = $doctors ?? [];
        $this->msg = $msg;
    }

    public function show(): void
    {
        // Generate CSRF Token
        if (!isset($_SESSION['csrf_patient'])) {
            $_SESSION['csrf_patient'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_patient'];

        // Helper for escaping
        $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        ?>
        <!DOCTYPE html>
        <html lang="fr">

        <head>
            <meta charset="UTF-8">
            <title>Dossier Patient - DashMed</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">

            <!-- Core Styles -->
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/themes/light.css">

            <!-- Page Specific Style -->
            <link rel="stylesheet" href="assets/css/dossierpatient.css">

            <!-- Components -->
            <link rel="stylesheet" href="assets/css/components/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar.css">

            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

            <main class="container nav-space">
                <div class="dashboard-content-container">
                    <?php include dirname(__DIR__) . '/components/searchbar.php'; ?>

                    <!-- Notifications -->
                    <?php if ($this->msg): ?>
                        <div class="message-box <?= $h($this->msg['type']) ?>">
                            <div class="message-content">
                                <?= $h($this->msg['text']) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Patient Header Card -->
                    <header class="patient-header-card">
                        <div class="patient-info-group">
                            <div class="patient-avatar">
                                <img src="assets/img/icons/profile.svg" alt="Avatar Patient" />
                            </div>
                            <div class="patient-identity">
                                <h1>
                                    <?= $h($this->patientData['first_name'] ?? 'Nom') ?>
                                    <strong><?= $h(strtoupper($this->patientData['last_name'] ?? 'Prénom')) ?></strong>
                                </h1>
                                <div class="patient-meta">
                                    <span class="badge-age"><?= $h($this->patientData['age'] ?? 0) ?> ans</span>
                                    <span class="meta-divider">•</span>
                                    <span>Né(e) le
                                        <?= $h(date('d/m/Y', strtotime($this->patientData['birth_date'] ?? 'now'))) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="header-actions">
                            <button class="btn-edit-patient" onclick="openEditModal()" aria-label="Modifier le dossier">
                                <img src="assets/img/icons/edit.svg" alt="" />
                                <span>Modifier</span>
                            </button>
                        </div>
                    </header>

                    <!-- Main Grid Layout -->
                    <div class="patient-grid">

                        <!-- Left Column: Medical Info and Doctors -->
                        <div class="grid-column-left">

                            <!-- Information Card (Admission & History) -->
                            <section class="card-section medical-info-card">
                                <div class="card-header">
                                    <h2>Informations Médicales</h2>
                                </div>
                                <div class="info-row">
                                    <div class="info-block">
                                        <h3>Motif d'admission</h3>
                                        <p class="text-content">
                                            <?= $h($this->patientData['admission_cause'] ?? 'Aucun motif renseigné.') ?>
                                        </p>
                                    </div>
                                    <div class="info-block">
                                        <h3>Antécédents & Allergies</h3>
                                        <div class="text-content history-content">
                                            <?= nl2br($h($this->patientData['medical_history'] ?? 'Aucun antécédent renseigné.')) ?>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Doctors Card -->
                            <section class="card-section doctors-card">
                                <div class="card-header">
                                    <h2>Équipe Médicale</h2>
                                </div>
                                <div class="doctors-list">
                                    <?php if (!empty($this->doctors)): ?>
                                        <?php foreach ($this->doctors as $doctor): ?>
                                            <div class="doctor-item">
                                                <img src="assets/img/icons/profile.svg" alt="Dr. <?= $h($doctor['last_name']) ?>"
                                                    class="doctor-avatar">
                                                <div class="doctor-details">
                                                    <span class="doctor-name">Dr. <?= $h($doctor['first_name']) ?>
                                                        <?= $h($doctor['last_name']) ?></span>
                                                    <span
                                                        class="doctor-specialty"><?= $h($doctor['profession_name'] ?? 'Généraliste') ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <p>Aucun médecin assigné principalment.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </section>

                        </div>

                    </div>
                </div>
            </main>

            <!-- Edit Modal -->
            <div id="patientEditModal" class="modal-overlay" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h2>Modifier le dossier</h2>
                        <button class="btn-close" onclick="closeEditModal()">×</button>
                    </div>
                    <form method="POST" action="/?page=dossierpatient">
                        <input type="hidden" name="csrf" value="<?= $h($csrfToken) ?>">
                        <input type="hidden" name="id_patient" value="<?= $h($this->patientData['id_patient'] ?? '') ?>">

                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="first_name">Prénom</label>
                                    <input type="text" id="first_name" name="first_name" required
                                        value="<?= $h($this->patientData['first_name'] ?? '') ?>" placeholder="Jean">
                                </div>
                                <div class="form-group half">
                                    <label for="last_name">Nom</label>
                                    <input type="text" id="last_name" name="last_name" required
                                        value="<?= $h($this->patientData['last_name'] ?? '') ?>" placeholder="Dupont">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="birth_date">Date de naissance</label>
                                <input type="date" id="birth_date" name="birth_date"
                                    value="<?= $h($this->patientData['birth_date'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
                                <span class="form-hint">L'âge sera recalculé automatiquement.</span>
                            </div>

                            <div class="form-group">
                                <label for="admission_cause">Motif d'admission</label>
                                <textarea id="admission_cause" name="admission_cause" rows="2" required
                                    placeholder="Ex: Douleurs thoraciques..."><?= $h($this->patientData['admission_cause'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="medical_history">Antécédents médicaux</label>
                                <textarea id="medical_history" name="medical_history" rows="3" required
                                    placeholder="Ex: Diabète type 2, hypertension..."><?= $h($this->patientData['medical_history'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" onclick="closeEditModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Scripts -->
            <script>
                // Simple Modal Logic
                const modal = document.getElementById('patientEditModal');

                function openEditModal() {
                    modal.classList.add('active');
                    modal.setAttribute('aria-hidden', 'false');
                }

                function closeEditModal() {
                    modal.classList.remove('active');
                    modal.setAttribute('aria-hidden', 'true');
                }

                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeEditModal();
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && modal.classList.contains('active')) closeEditModal();
                });
            </script>
            <script src="assets/js/pages/dash.js"></script>
        </body>

        </html>
        <?php
    }
}