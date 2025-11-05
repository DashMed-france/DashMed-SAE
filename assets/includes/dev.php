<?php

/**
 * DashMed — Classe de gestion du mode de développement
 *
 * Fournit des méthodes utilitaires pour déterminer si l’application
 * est en mode développement ou en mode production, et ajuste le
 * comportement global (affichage des erreurs, journalisation, etc.)
 * en conséquence.
 *
 * @package   DashMed\Assets\Includes
 * @author    Équipe DashMed
 * @license   Propriétaire
 */

final class dev
{
    /**
     * Charge les variables d’environnement depuis le fichier `.env`.
     *
     * Si le fichier est introuvable ou illisible, la méthode affiche
     * la page d’erreur 500 via la vue `errorView` et interrompt l’exécution.
     *
     *
     * @param string|null $path  Chemin vers le fichier `.env` (par défaut à la racine du projet)
     * @return void
     */
    public static function loadEnv(): void
    {
        $envPath = $path ?? __DIR__ . '/../../.env';

        if (!is_file($envPath) || !is_readable($envPath)) {
            error_log('[Dev] .env introuvable ou illisible à ' . $envPath);

            http_response_code(500);
            (new \modules\views\pages\static\errorView())->show(
                500,
                message: "Erreur serveur — fichier .env introuvable.",
                details: dev::isDebug() ? "Fichier manquant : {$envPath}" : null
            );
            exit;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
            $name  = trim($name);
            $value = trim($value);

            if ($name !== '') {
                $_ENV[$name]    = $value;
                $_SERVER[$name] = $value;
                putenv("$name=$value");
            }
        }

        error_log('[Dev] .env chargé depuis ' . $envPath);
    }

    /**
     * Vérifie si l'application est en mode développement.
     *
     * Cette méthode lit la variable d'environnement `APP_DEBUG`
     * (définie dans le fichier `.env` ou dans l'environnement du serveur).
     * Si `APP_DEBUG` vaut `true`, `1`, `on` ou `yes`, alors l'application
     * est considérée comme en mode développement.
     *
     * @example
     *  if (dev::isDebug()) {
     *      // Exécuter du code spécifique au mode dev
     *  }
     *
     * @return bool True si le mode debug est activé, false sinon.
     */
    public static function isDebug(): bool
    {
        // Recharge le .env si besoin
        if (!isset($_ENV['APP_DEBUG']) && !getenv('APP_DEBUG')) {
            self::loadEnv();
        }

        $debug = getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? '0');
        $debug = strtolower(trim((string)$debug));

        return in_array($debug, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Configure l’affichage des erreurs PHP selon le mode actif.
     *
     * En mode développement :
     *  - Affiche toutes les erreurs (E_ALL)
     *  - Active display_errors et display_startup_errors
     *
     * En mode production :
     *  - Masque les erreurs à l’écran
     *  - Continue de les enregistrer dans les logs si configurés
     *
     * À appeler très tôt dans le cycle de vie, idéalement depuis
     * `public/index.php` avant le routage principal.
     *
     * @return void
     */
    public static function configurePhpErrorDisplay(): void
    {
        if (self::isDebug()) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
        }
    }

    /**
     * Retourne une représentation textuelle du mode actuel.
     *
     * Utile pour les logs, l’administration ou l’affichage
     * d’informations système (par ex. dans une page de statut).
     *
     * @example
     *  echo dev::getMode(); // Affiche "development" ou "production"
     *
     * @return string "development" si debug actif, sinon "production".
     */
    public static function getMode(): string
    {
        return self::isDebug() ? 'development' : 'production';
    }

    /**
     * Initialise la configuration d’environnement complète.
     *
     * Charge la configuration des erreurs PHP, puis peut être
     * étendue ultérieurement pour inclure d’autres aspects (logs,
     * constantes globales, etc.).
     *
     * @example
     *  dev::init();
     *
     * @return void
     */
    public static function init(): void
    {
        self::loadEnv();
        self::configurePhpErrorDisplay();
    }
}
