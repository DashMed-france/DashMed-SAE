<?php

namespace modules\views\pages;

class monitoringView
{
    private array $consultationsPassees;
    private array $consultationsFutures;
    private array $metrics;

    public function __construct(array $consultationsPassees = [], array $consultationsFutures = [], array $metrics = [])
    {
        $this->consultationsPassees = $consultationsPassees;
        $this->consultationsFutures = $consultationsFutures;
        $this->metrics = $metrics;
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
            <link rel="stylesheet" href="assets/css/components/modal.css">
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
            <script src="assets/js/component/modal.js" defer></script>
        </head>
        <body>
        <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

        <main class="container">
            <section class="dashboard-content-container">
                <form class="searchbar" role="search" action="#" method="get">
                    <span class="left-icon" aria-hidden="true">
                        <img src="assets/img/icons/glass.svg" alt="">
                    </span>
                    <input type="search" name="q" placeholder="Search..." aria-label="Rechercher"/>
                    <div class="actions">
                        <button type="button" class="action-btn" aria-label="Notifications">
                            <img src="assets/img/icons/bell.svg" alt="">
                        </button>
                        <a href="/?page=profile">
                            <div class="avatar" title="Profil" aria-label="Profil"><img src="" alt=""></div>
                        </a>
                    </div>
                </form>
                <section class="cards-container">
                    <?php if (!empty($this->metrics)): ?>
                        <?php foreach ($this->metrics as $row): ?>
                            <?php
                            $param = htmlspecialchars($row['parameter_id']);
                            $val = htmlspecialchars((string)$row['value']);
                            $crit = !empty($row['alert_flag']) && (int)$row['alert_flag'] === 1;
                            ?>
                            <article class="card<?= $crit ? ' card--alert' : '' ?>"
                                     onclick="openModal('<?= $param ?>', '<?= $val ?>', <?= $crit ? 'true' : 'false' ?>)">
                                <h3><?= $param ?></h3>
                                <p class="value"><?= $val ?></p>
                                <?php if ($crit): ?><p class="tag tag--danger">Valeur critique</p><?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <article class="card">
                            <h3>Aucune donnée</h3>
                            <p class="value">—</p>
                        </article>
                    <?php endif; ?>
                </section>
        </main>
        <div class="modal" id="cardModal">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h2 id="modalTitle"></h2>
                <p id="modalValue"></p>
                <div id="modalDetails"></div>
            </div>
        </div>
        </body>
        </html>
        <?php
    }
}
