<?php

declare(strict_types=1);

namespace modules\views\layout;

/**
 * Class Layout
 *
 * Master template that wraps all page views.
 *
 * Provides the common HTML skeleton: <head>, sidebar, main container,
 * global alerts, scroll-to-top, and shared scripts.
 *
 * @package DashMed\Modules\Views\Layout
 * @author DashMed Team
 * @license Proprietary
 */
class Layout
{
    /** @var string Page title */
    private string $title;

    /** @var array<int, string> Extra CSS files for the page */
    private array $cssFiles;

    /** @var array<int, string> Extra JS files for the page */
    private array $jsFiles;

    /** @var string Extra inline styles */
    private string $inlineStyles;

    /** @var bool Whether to include sidebar */
    private bool $showSidebar;

    /** @var bool Whether to include global alerts */
    private bool $showAlerts;

    /**
     * Constructor
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
     *
     * The callback receives no arguments and should output the page-specific HTML.
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
            <script>
                (function () {
                    const theme = localStorage.getItem('theme') || 'light';
                    const root = document.documentElement;
                    const bgColor = theme === 'dark' ? '#020617' : '#f8fafc';
                    root.setAttribute('data-theme', theme);
                    root.style.backgroundColor = bgColor;
                    window.addEventListener('DOMContentLoaded', () => {
                        root.style.backgroundColor = '';
                    });
                })();
            </script>
            <title>DashMed - <?= htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') ?></title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <meta name="author" content="DashMed Team">

            <link rel="stylesheet" href="assets/css/components/loader.css">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/themes/dark.css">
            <link rel="stylesheet" href="assets/css/base/style.css">
            <link rel="stylesheet" href="assets/css/components/skeleton.css">

            <?php if ($this->showSidebar): ?>
                <link rel="stylesheet" href="assets/css/layout/sidebar.css">
            <?php endif; ?>

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

            <div id="dashmed-loader" class="loader-overlay">
                <div class="loader-container">
                    <svg class="loader-svg" viewBox="0 0 100 100">
                        <circle class="loader-circle" cx="50" cy="50" r="45" />
                    </svg>
                    <div class="loader-logo">
                        <svg viewBox="0 0 1024 1024" preserveAspectRatio="xMidYMid meet">
                            <g transform="translate(0,1024) scale(0.1,-0.1)" stroke="none">
                                <path class="loader-logo-d"
                                    d="M1740 5215 l0 -1975 643 0 c614 0 844 7 987 31 198 33 389 99 572 197 166 88 280 174 423 317 287 286 451 631 521 1095 22 143 25 500 5 640 -35 254 -100 479 -191 660 -47 94 -154 261 -208 325 -286 341 -656 565 -1064 644 -180 35 -338 41 -1024 41 l-664 0 0 -1975z m1469 1209 c239 -35 433 -135 598 -307 150 -157 240 -335 288 -566 51 -245 35 -604 -36 -821 -106 -323 -355 -574 -669 -674 -167 -53 -207 -58 -537 -63 l-313 -5 0 1226 0 1226 281 0 c204 0 311 -4 388 -16z" />
                                <path class="loader-logo-m"
                                    d="M4840 6763 l0 -428 29 -80 c16 -44 44 -118 61 -165 50 -136 98 -323 122 -474 26 -170 31 -554 10 -726 -31 -245 -96 -494 -171 -649 l-36 -76 -5 -463 -5 -462 413 0 412 0 0 1241 0 1242 26 -39 c15 -21 127 -181 249 -354 122 -173 353 -503 514 -732 l292 -418 537 767 537 767 3 -1237 2 -1237 398 0 397 0 -3 1975 -2 1975 -384 0 -385 0 -136 -202 c-449 -668 -957 -1413 -963 -1415 -4 -1 -36 41 -72 95 -36 53 -247 363 -470 687 -223 325 -443 645 -489 713 l-83 122 -399 0 -399 0 0 -427z" />
                            </g>
                        </svg>
                    </div>
                </div>
                <div class="loader-text">DASHMED</div>
            </div>

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

            <script src="assets/js/components/loader.js"></script>
            <script src="assets/js/components/skeleton.js"></script>

            <?php foreach ($this->jsFiles as $js): ?>
                <script src="<?= htmlspecialchars($js, ENT_QUOTES, 'UTF-8') ?>"></script>
            <?php endforeach; ?>

        </body>

        </html>
        <?php
    }
}
