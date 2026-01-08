<?php

/**
 * Composant global d'alertes médicales iziToast
 *
 * À inclure dans TOUTES les vues pour avoir les notifications partout.
 * Usage: <?php include __DIR__ . '/path/to/global-alerts.php'; ?>
 */

?>

<!-- iziToast CSS (CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css">
<link rel="stylesheet" href="assets/css/alerts-toast.css">

<!-- iziToast JS (CDN) - Chargé avant le script global -->
<script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>

<!-- Système global de notifications DashMed -->
<script src="assets/js/alerts-global.js"></script>