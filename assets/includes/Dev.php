<?php

namespace assets\includes;

/**
 * Class Dev | Gestionnaire de Mode Développement
 *
 * Utilities for development vs production mode.
 * Utilitaires pour gérer le mode développement vs production.
 *
 * Handles environment loading and error display configuration.
 * Gère le chargement de l'environnement et la configuration de l'affichage des erreurs.
 *
 * @package DashMed\Assets\Includes
 * @author DashMed Team
 * @license Proprietary
 */

final class Dev
{
    /**
     * Loads environment variables from .env file.
     * Charge les variables d'environnement depuis le fichier .env.
     *
     * Halts execution if .env is missing.
     * Arrête l'exécution si le fichier .env est manquant.
     *
     * @param string|null $path Path to .env file | Chemin vers le fichier .env.
     * @return void
     */
    public static function loadEnv(?string $path = null): void
    {
        $envPath = $path ?? __DIR__ . '/../../.env';

        if (!is_file($envPath) || !is_readable($envPath)) {
            error_log('[Dev] .env introuvable ou illisible à ' . $envPath);

            http_response_code(500);
            if (class_exists('\\modules\\views\\pages\\static\\ErrorView')) {
                (new \modules\views\pages\static\ErrorView())->show(
                    500,
                    message: "Erreur serveur — fichier .env introuvable.",
                    details: Dev::isDebug() ? "Fichier manquant : {$envPath}" : null
                );
            } else {
                echo "Erreur serveur — fichier .env introuvable.";
            }
            exit;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            $lines = [];
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
            $name = trim($name);
            $value = trim($value);

            if ($name !== '') {
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                putenv("$name=$value");
            }
        }

        error_log('[Dev] .env chargé depuis ' . $envPath);
    }

    /**
     * Checks if the application is in development mode.
     * Vérifie si l'application est en mode développement.
     *
     * Based on APP_DEBUG environment variable.
     * Basé sur la variable d'environnement APP_DEBUG.
     *
     * @return bool True if debug mode is on | Vrai si le mode debug est activé.
     */
    public static function isDebug(): bool
    {
        // Reload .env if needed | Recharge le .env si besoin
        if (!isset($_ENV['APP_DEBUG']) && !getenv('APP_DEBUG')) {
            self::loadEnv();
        }

        $envDebug = getenv('APP_DEBUG');
        $debug = $envDebug !== false ? $envDebug : ($_ENV['APP_DEBUG'] ?? '0');
        if (!is_string($debug)) {
            $debug = '0';
        }
        $debug = strtolower(trim($debug));

        return in_array($debug, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Configures PHP error display based on the active mode.
     * Configure l'affichage des erreurs PHP selon le mode actif.
     *
     * Dev: Show all errors. Prod: Hide errors.
     * Dev: Affiche toutes les erreurs. Prod: Masque les erreurs.
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
     * Returns the text representation of the current mode.
     * Retourne une représentation textuelle du mode actuel.
     *
     * @return string "development" or "production".
     */
    public static function getMode(): string
    {
        return self::isDebug() ? 'development' : 'production';
    }

    /**
     * Initializes the full environment configuration.
     * Initialise la configuration d'environnement complète.
     *
     * Loads .env and configures error display.
     * Charge le .env et configure l'affichage des erreurs.
     *
     * @return void
     */
    public static function init(): void
    {
        self::loadEnv();
        self::configurePhpErrorDisplay();
    }
}
