<?php

namespace modules\views\patient;

/**
 * Class PatientrecordView
 *
 * View for displaying patient record.
 *
 * Handles display of patient info, history, medical team, and consultations.
 *
 * @package DashMed\Modules\Views\Pages
 * @author DashMed Team
 * @license Proprietary
 */
class PatientrecordView
{
    /** @var array<string, mixed> Patient medical/admin data */
    private array $patientData;

    /** @var array<int, array{
     *   id_user: int,
     *   last_name: string,
     *   first_name: string,
     *   profession_name: string
     * }> Doctors assigned to patient */
    private array $doctors;

    /** @var array{type: string, text: string}|null Flash message */
    private ?array $msg;

    /**
     * Constructor.
     *
     * Initializes patient record view.
     *
     * @param array<int, mixed> $consultationsPassees History
     * @param array<int, mixed> $consultationsFutures Appointments
     * @param array<string, mixed> $patientData Patient Data
     * @param array<int, array{
     *   id_user: int,
     *   last_name: string,
     *   first_name: string,
     *   profession_name: string
     * }> $doctors Medical Team
     * @param array{type: string, text: string}|null $msg Flash Message
     */
    public function __construct(
        array $consultationsPassees = [],
        array $consultationsFutures = [],
        array $patientData = [],
        array $doctors = [],
        ?array $msg = null
    ) {
        $this->patientData = $patientData;
        $this->doctors = $doctors;
        $this->msg = $msg;
        unset($consultationsPassees, $consultationsFutures);
    }

    /**
     * Renders the patient record page HTML.
     *
     * Includes CSRF token generation if not present.
     *
     * @return void
     */
    public function show(): void
    {
        if (!isset($_SESSION['csrf_patient'])) {
            $_SESSION['csrf_patient'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_patient'];

        $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        ?>
        <!DOCTYPE html>
        <html lang="fr">

        <head>
            <meta charset="UTF-8">
            <title>Dossier Patient - DashMed</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">

            <link rel="stylesheet" href="assets/css/base/style.css">
            <link rel="stylesheet" href="assets/css/themes/light.css">

            <link rel="stylesheet" href="assets/css/pages/patient-record.css">

            <link rel="stylesheet" href="assets/css/layout/sidebar.css">
            <link rel="stylesheet" href="assets/css/components/searchbar/searchbar.css">

            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
        </head>

        <body>
            <?php include dirname(__DIR__) . '/partials/_sidebar.php'; ?>

            <main class="container nav-space">
                <div class="dashboard-content-container">
                    <?php include dirname(__DIR__) . '/partials/_searchbar.php'; ?>
                    <input type="hidden" id="context-patient-id"
                           value="<?= $h($this->patientData['id_patient'] ?? '') ?>">

                    <?php
                    $msg = $this->msg;
                    $text = $msg['text'] ?? null;
                    $type = $msg['type'] ?? 'info';
                    ?>

                    <?php if (is_string($text) && $text !== '') : ?>
                        <div class="message-box <?= $h($type) ?>">
                            <div class="message-content">
                                <?= $h($text) ?>
                            </div>
                        </div>
                    <?php endif; ?>


                    <header class="patient-header-card">
                        <div class="patient-info-group">
                            <div class="patient-avatar">
                                <img src="assets/img/icons/profile.svg" alt="Avatar Patient" />
                            </div>
                            <div class="patient-identity">
                                <?php
                                $firstName = $this->patientData['first_name'] ?? 'Nom';
                                $lastName = $this->patientData['last_name'] ?? 'Inconnu';

                                $firstName = is_scalar($firstName) ? (string) $firstName : 'Nom';
                                $lastName = is_scalar($lastName) ? (string) $lastName : 'Inconnu';

                                $lastName = strtoupper($lastName);
                                ?>


                                <h1>
                                    <?= $h($firstName) ?>
                                    <strong><?= $h($lastName) ?></strong>
                                </h1>

                                <div class="patient-meta">
                                    <span class="badge-age"><?= $h($this->patientData['age'] ?? 0) ?> ans</span>
                                    <span class="meta-divider">•</span>
                                    <span>Né(e) le
                                        <?= $h(date(
                                            'd/m/Y',
                                            is_string($this->patientData['birth_date'] ?? null)
                                            ? (strtotime((string) $this->patientData['birth_date']) ?: time())
                                            : time()
                                        )) ?>
                                    </span>
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

                    <div class="patient-grid">

                        <div class="grid-column-left">

                            <section class="card-section medical-info-card">
                                <div class="card-header">
                                    <h2>Informations Médicales</h2>
                                </div>
                                <div class="info-row">
                                    <div class="info-block">
                                        <h3>Motif d'admission</h3>
                                        <p class="text-content">
                                            <?= $h(
                                                $this->patientData['admission_cause'] ?? 'Aucun motif renseigné.'
                                            ) ?>
                                        </p>
                                    </div>
                                    <div class="info-block">
                                        <h3>Antécédents & Allergies</h3>
                                        <div class="text-content history-content">
                                            <?= nl2br($h($this->patientData['medical_history'] ??
                                                'Aucun antécédent renseigné.')) ?>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="card-section doctors-card">
                                <div class="card-header">
                                    <h2>Équipe Médicale</h2>
                                </div>
                                <div class="doctors-list">
                                    <?php if (!empty($this->doctors)) : ?>
                                        <?php foreach ($this->doctors as $doctor) : ?>
                                            <div class="doctor-item" id="doctor-<?= $h($doctor['id_user']) ?>">
                                                <img src="assets/img/icons/profile.svg"
                                                     alt="Dr. <?= $h($doctor['last_name']) ?>"
                                                    class="doctor-avatar">
                                                <div class="doctor-details">
                                                    <span class="doctor-name">Dr. <?= $h($doctor['first_name']) ?>
                                                        <?= $h($doctor['last_name']) ?></span>
                                                    <span class="doctor-specialty">
                                                        <?= $h($doctor['profession_name']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <div class="empty-state">
                                            <p>Aucun médecin assigné à ce patient.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </section>

                        </div>
                    </div>
                </div>
            </main>

            <div id="patientEditModal" class="modal-overlay" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h2>Modifier le dossier</h2>
                        <button class="btn-close" onclick="closeEditModal()">×</button>
                    </div>
                    <form method="POST" action="/?page=dossierpatient">
                        <input type="hidden" name="csrf" value="<?= $h($csrfToken) ?>">
                        <input type="hidden" name="id_patient"
                               value="<?= $h($this->patientData['id_patient'] ?? '') ?>">

                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="first_name">Prénom</label>
                                    <input type="text" id="first_name" name="first_name"
                                           required value="<?= $h($this->patientData['first_name'] ??
                                            '') ?>" placeholder="Jean">
                                </div>
                                <div class="form-group half">
                                    <label for="last_name">Nom</label>
                                    <input type="text" id="last_name" name="last_name"
                                           required value="<?= $h($this->patientData['last_name'] ??
                                            '') ?>" placeholder="Dupont">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="birth_date">Date de naissance</label>
                                <input type="date" id="birth_date" name="birth_date"
                                    value="<?= $h($this->patientData['birth_date'] ?? '') ?>"
                                       max="<?= date('Y-m-d') ?>">
                                <span class="form-hint">L'âge sera recalculé automatiquement.</span>
                            </div>

                            <div class="form-group">
                                <label for="admission_cause">Motif d'admission</label>
                                <textarea id="admission_cause" name="admission_cause" rows="2" required
                                    placeholder="Motif de l'hospitalisation...">
                                                            <?= $h($this->patientData['admission_cause'] ?? '') ?>
                                                        </textarea>
                            </div>

                            <div class="form-group">
                                <label for="medical_history">Antécédents médicaux</label>
                                <textarea id="medical_history" name="medical_history" rows="3" required
                                    placeholder="Antécédents, allergies, traitements chroniques...">
                                                            <?= $h($this->patientData['medical_history'] ?? '') ?>
                                </textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" onclick="closeEditModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php include dirname(__DIR__) . '/partials/_global-alerts.php'; ?>
            <script src="assets/js/pages/dash.js"></script>
            <script src="assets/js/pages/dossier_patient.js"></script>
        </body>

        </html>
        <?php
    }
}
