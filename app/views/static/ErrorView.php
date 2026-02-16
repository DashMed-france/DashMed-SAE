<?php

namespace modules\views\static;

/**
 * Class ErrorView
 *
 * Displays error pages with elegant interface.
 *
 * Shows error details if in debug mode.
 *
 * @package DashMed\Modules\Views\Pages\Static
 * @author DashMed Team
 * @license Proprietary
 */
class ErrorView
{
    /**
     * Renders the error page HTML.
     *
     * @param int         $code    HTTP Status Code
     * @param string|null $title   Error title
     * @param string|null $message User message
     * @param string|null $details Technical details
     * @return void
     */
    public function show(
        int $code,
        ?string $title = null,
        ?string $message = null,
        ?string $details = null
    ): void {
        http_response_code($code);

        $titles = [
            400 => 'Requête invalide',
            401 => 'Authentification requise',
            403 => 'Accès interdit',
            404 => 'Page introuvable',
            405 => 'Méthode non autorisée',
            408 => 'Délai dépassé',
            409 => 'Conflit',
            422 => 'Données invalides',
            429 => 'Trop de requêtes',
            500 => 'Erreur interne du serveur',
            503 => 'Service indisponible',
        ];

        $messages = [
            400 => "La requête n’a pas pu être comprise ou était incomplète.",
            401 => "Vous devez être connecté pour accéder à cette ressource.",
            403 => "Vous n’avez pas les autorisations nécessaires.",
            404 => "La page demandée n’existe pas.",
            405 => "La méthode HTTP utilisée n’est pas supportée.",
            408 => "La requête a expiré. Veuillez réessayer.",
            409 => "Conflit avec l’état actuel de la ressource.",
            422 => "Les données fournies ne sont pas valides.",
            429 => "Trop de tentatives, merci d’attendre avant de réessayer.",
            500 => "Une erreur inattendue est survenue.",
            503 => "Le service est temporairement indisponible.",
        ];

        $title = $title ?? ($titles[$code] ?? 'Erreur');
        $message = $message ?? ($messages[$code] ?? 'Une erreur est survenue.');
        $hasDetails = !empty($details);
        ?>
        <!doctype html>
        <html lang="fr">

        <head>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title><?= htmlspecialchars((string) $code) ?> — <?= htmlspecialchars($title) ?></title>
            <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
            <link rel="stylesheet" href="assets/css/base/style.css">
            <link rel="stylesheet" href="assets/css/themes/light.css">
            <link rel="stylesheet" href="assets/css/components/buttons.css">
            <link rel="stylesheet" href="assets/css/pages/error.css">
        </head>

        <body>
            <svg width="100%" viewBox="0 0 1920 241" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g transform="scale(-1,-1) translate(-1920,-241)">
                    <path class="wave" d="M1920 208.188L1880 191.782C1840 175.375 1760 142.563 1680 131.592C1600
                        121.03 1520 131.284
                        1440 109.751C1360 88.2179 1280 32.8472 1200 11.3142C1120 -10.2189 1040 0.0349719 960
                        33.1548C880 65.6595 800 121.03 720 137.129C640 153.842 560 131.284 480 137.129C400
                        142.563 320 175.375 240 169.941C160 164.096 79.9999 121.03 39.9999 98.7794L-5.72205e-05
                        76.9387V241H39.9999C79.9999 241 160 241 240 241C320 241 400 241 480 241C560 241 640 241
                        720 241C800 241 880 241 960 241C1040 241 1120 241 1200 241C1280 241 1360 241 1440 241C1520
                        241 1600 241 1680 241C1760 241 1840 241 1880 241H1920V208.188Z" fill="#275AFE" />
                </g>
            </svg>

            <main>
                <h1><?= htmlspecialchars((string) $code) ?> — <?= htmlspecialchars($title) ?></h1>
                <p><?= htmlspecialchars($message) ?></p>

                <div class="buttons">
                    <a class="pos" href="/?page=homepage">Retour</a>
                    <?php if ($hasDetails): ?>
                        <button class="neg" id="details-btn" onclick="toggleDetails()" aria-expanded="false">
                            Afficher les détails techniques
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($hasDetails): ?>
                    <section id="error-details" class="details" aria-hidden="true">
                        <?= nl2br(htmlspecialchars($details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
                    </section>
                    <script src="assets/js/pages/static/error.js">
                    </script>
                <?php endif; ?>
            </main>
            <svg width="100%" viewBox="0 0 1920 241" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path class="wave" style="transform: translateY(+20px)" d="M1920 208.188L1880 191.782C1840 175.375
                    1760 142.563 1680 131.592C1600 121.03 1520
                    131.284 1440 109.751C1360 88.2179 1280 32.8472
                    1200 11.3142C1120 -10.2189 1040 0.0349719 960
                    33.1548C880 65.6595 800 121.03 720 137.129C640
                    153.842 560 131.284 480 137.129C400 142.563 320
                    175.375 240 169.941C160 164.096 79.9999 121.03
                    39.9999 98.7794L-5.72205e-05 76.9387V241H39.9999C79.9999
                    241 160 241 240 241C320 241 400 241 480 241C560 241 640 241
                    720 241C800 241 880 241 960 241C1040 241 1120 241 1200 241C1280
                    241 1360 241 1440 241C1520 241 1600 241 1680 241C1760 241 1840 241 1880 241H1920V208.188Z"
                    fill="#275AFE" />
            </svg>
        </body>

        </html>
        <?php
    }
}
