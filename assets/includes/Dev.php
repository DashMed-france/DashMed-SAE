<?php

/**
 * DashMed — Development Mode Management Class
 *
 * Provides utility methods to determine whether the application
 * is in development or production mode, and adjusts global behavior
 * (error reporting, logging, etc.) accordingly.
 *
 * @package   DashMed\Assets\Includes
 * @author    DashMed Team
 * @license   Proprietary
 */
final class Dev
{
    /**
     * Loads environment variables from the `.env` file.
     *
     * If the file is missing or unreadable, the method displays
     * the 500 error page via the `errorView` and terminates execution.
     *
     * @param string|null $path Path to the `.env` file (defaults to project root)
     * @return void
     */
    public static function loadEnv(): void
    {
        $envPath = $path ?? __DIR__ . '/../../.env';

        if (!is_file($envPath) || !is_readable($envPath)) {
            error_log('[Dev] .env not found or unreadable at ' . $envPath);

            http_response_code(500);
            (new \modules\views\pages\static\ErrorView())->show(
                500,
                message: "Server Error — .env file not found.",
                details: Dev::isDebug() ? "Missing file: {$envPath}" : null
            );
            exit;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

        error_log('[Dev] .env loaded from ' . $envPath);
    }

    /**
     * Checks if the application is in development mode.
     *
     * This method reads the `APP_DEBUG` environment variable
     * (defined in the `.env` file or in the server environment).
     * If `APP_DEBUG` is `true`, `1`, `on`, or `yes`, then the application
     * is considered to be in development mode.
     *
     * @example
     * if (dev::isDebug()) {
     * // Execute dev-specific code
     * }
     *
     * @return bool True if debug mode is active, false otherwise.
     */
    public static function isDebug(): bool
    {
        if (!isset($_ENV['APP_DEBUG']) && !getenv('APP_DEBUG')) {
            self::loadEnv();
        }

        $debug = getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? '0');
        $debug = strtolower(trim((string) $debug));

        return in_array($debug, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Configures PHP error reporting based on the active mode.
     *
     * In development mode:
     * - Reports all errors (E_ALL)
     * - Enables display_errors and display_startup_errors
     *
     * In production mode:
     * - Hides errors from the screen
     * - Continues to record them in logs if configured
     *
     * Should be called very early in the lifecycle, ideally from
     * `public/index.php` before main routing.
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
     * Returns a textual representation of the current mode.
     *
     * Useful for logs, administration, or displaying system
     * information (e.g., in a status page).
     *
     * @example
     * echo dev::getMode(); // Returns "development" or "production"
     *
     * @return string "development" if debug is active, otherwise "production".
     */
    public static function getMode(): string
    {
        return self::isDebug() ? 'development' : 'production';
    }

    /**
     * Initializes the full environment configuration.
     *
     * Loads PHP error configuration, and can be extended later
     * to include other aspects (logs, global constants, etc.).
     *
     * @example
     * dev::init();
     *
     * @return void
     */
    public static function init(): void
    {
        self::loadEnv();
        self::configurePhpErrorDisplay();
    }
}