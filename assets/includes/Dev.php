<?php

namespace assets\includes;

/**
 * Class Dev
 *
 * Utilities for development vs production mode.
 *
 * Handles environment loading and error display configuration.
 *
 * @package DashMed\Assets\Includes
 * @author DashMed Team
 * @license Proprietary
 *
 * @access public
 */

final class Dev
{
    /**
     * Loads environment variables from .env file.
     *
     * Halts execution if .env is missing.
     *
     * @param string|null $path Path to .env file.
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
     *
     * Based on APP_DEBUG environment variable.
     *
     * @return bool True if debug mode is on.
     */
    public static function isDebug(): bool
    {
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
     *
     * Dev: Show all errors. Prod: Hide errors.
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
     *
     * @return string "development" or "production".
     */
    public static function getMode(): string
    {
        return self::isDebug() ? 'development' : 'production';
    }

    /**
     * Initializes the full environment configuration.
     *
     * Loads .env and configures error display.
     *
     * @return void
     */
    public static function init(): void
    {
        self::loadEnv();
        self::configurePhpErrorDisplay();
    }
}
