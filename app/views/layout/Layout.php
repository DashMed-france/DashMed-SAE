<?php

declare(strict_types=1);

namespace modules\views\layout;

/**
 * Class Layout | Gabarit Principal
 *
 * Master template that wraps all page views.
 * Gabarit principal qui encadre toutes les vues de page.
 *
 * Provides the common HTML skeleton: <head>, sidebar, main container,
 * global alerts, scroll-to-top, and shared scripts.
 * Fournit le squelette HTML commun : <head>, barre latérale, conteneur principal,
 * alertes globales, scroll-to-top et scripts partagés.
 *
 * @package DashMed\Modules\Views\Layout
 * @author DashMed Team
 * @license Proprietary
 */
class Layout
{
    /** @var string Page title | Titre de la page */
    private string $title;

    /** @var array<int, string> Extra CSS files for the page | Fichiers CSS spécifiques à la page */
    private array $cssFiles;

    /** @var array<int, string> Extra JS files for the page | Fichiers JS spécifiques à la page */
    private array $jsFiles;

    /** @var string Extra inline styles | Styles en ligne supplémentaires */
    private string $inlineStyles;

    /** @var bool Whether to include sidebar | Inclure la barre latérale */
    private bool $showSidebar;

    /** @var bool Whether to include global alerts | Inclure les alertes globales */
    private bool $showAlerts;

    /**
     * Constructor | Constructeur
     *
     * @param string $title Page title
     * @param array<int, string> $cssFiles Additional CSS files
     * @param array<int, string> $jsFiles Additional JS files
     * @param string $inlineStyles Inline <style> content
     * @param bool $showSidebar Show sidebar (default: true)
     * @param bool $showAlerts Show global alerts (default: true)
     */
    public function __construct(
        string $title = 'DashMed',
        array $cssFiles = [],
        array $jsFiles = [],
        string $inlineStyles = '',
        bool $showSidebar = true,
        bool $showAlerts = true
    ) {
        $this->title = $title;
        $this->cssFiles = $cssFiles;
        $this->jsFiles = $jsFiles;
        $this->inlineStyles = $inlineStyles;
        $this->showSidebar = $showSidebar;
        $this->showAlerts = $showAlerts;
    }

    /**
     * Renders the layout with the given content callback.
     * Affiche le layout avec le contenu fourni par le callback.
     *
     * The callback receives no arguments and should output the page-specific HTML.
     * Le callback ne reçoit aucun argument et doit afficher le HTML spécifique à la page.
     *
     * @param callable(): void $contentCallback Function that outputs the main page content
     * @return void
     */
    public function render(callable $contentCallback): void
    {
        $partialsDir = dirname(__DIR__) . '/partials';
        ?>
        <!DOCTYPE html>
        <html lang="fr">

        <head>
            <meta charset="UTF-8">
            <title>
                <?= htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') ?>
            </title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <meta name="author" content="DashMed Team">

            <!-- Base CSS -->
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/themes/dark.css">
            <link rel="stylesheet" href="assets/css/base/style.css">

            <?php if ($this->showSidebar): ?>
                <link rel="stylesheet" href="assets/css/layout/sidebar.css">
            <?php endif; ?>

            <!-- Page-specific CSS -->
            <?php foreach ($this->cssFiles as $css): ?>
                <link rel="stylesheet" href="<?= htmlspecialchars($css, ENT_QUOTES, 'UTF-8') ?>">
            <?php endforeach; ?>

            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">

            <?php if ($this->inlineStyles !== ''): ?>
                <style>
                    <?= $this->inlineStyles ?>
                </style>
            <?php endif; ?>
        </head>

        <body>

            <?php if ($this->showSidebar && file_exists($partialsDir . '/_sidebar.php')): ?>
                <?php include $partialsDir . '/_sidebar.php'; ?>
            <?php endif; ?>

            <?php $contentCallback(); ?>

            <?php if ($this->showAlerts && file_exists($partialsDir . '/_global-alerts.php')): ?>
                <?php include $partialsDir . '/_global-alerts.php'; ?>
            <?php endif; ?>

            <?php if (file_exists($partialsDir . '/_scroll-to-top.php')): ?>
                <?php include $partialsDir . '/_scroll-to-top.php'; ?>
            <?php endif; ?>

            <!-- Page-specific JS -->
            <?php foreach ($this->jsFiles as $js): ?>
                <script src="<?= htmlspecialchars($js, ENT_QUOTES, 'UTF-8') ?>"></script>
            <?php endforeach; ?>

        </body>

        </html>
        <?php
    }
}
