<?php

/**
 * DashMed — Database Connection Helper
 *
 * This class provides a unique instance (singleton) of a PDO connection to the MySQL database.
 * It automatically reads configuration variables from the `.env` file located
 * two levels above this file, and ensures all required parameters are loaded.
 *
 * @package   DashMed\assets\includes
 * @author    DashMed Team
 * @license   Proprietary
 */

/**
 * Database Connection Singleton.
 *
 * Responsibilities:
 * - Load database credentials from a `.env` file.
 * - Verify that all necessary environment variables are defined.
 * - Establish and cache a reusable PDO connection throughout the application.
 *
 * Usage example:
 * ```php
 * $pdo = Database::getInstance();
 * ```
 */
final class Database
{
    /**
     * Cached PDO instance shared across all database calls.
     *
     * @var PDO|null
     */
    private static ?PDO $instance = null;

    /**
     * Returns a unique (singleton) instance of PDO.
     *
     * If the instance has not yet been created, this method loads environment
     * variables, validates them, constructs the DSN, and establishes a connection
     * with error handling.
     *
     * @return PDO  The shared PDO instance.
     * @throws PDOException If the connection fails.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $envPath = __DIR__ . '/../../.env';
        if (!is_file($envPath) || !is_readable($envPath)) {
            error_log('[Database] .env not found or unreadable at ' . $envPath);
            http_response_code(500);
            echo '500 — Server Error (.env missing).';
            exit;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
            $name  = trim($name);
            $value = trim($value);

            if ($name !== '') {
                $_ENV[$name]    = $value;
                $_SERVER[$name] = $value;
                putenv("$name=$value");
            }
        }

        $required = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $_ENV) || trim((string)$_ENV[$key]) === '') {
                error_log("[Database] Variable $key missing or empty in .env");
                http_response_code(500);
                echo '500 — Server Error (incomplete DB configuration).';
                exit;
            }
        }

        $host    = trim((string)$_ENV['DB_HOST']);
        $name    = trim((string)$_ENV['DB_NAME']);
        $user    = trim((string)$_ENV['DB_USER']);
        $pass    = (string)$_ENV['DB_PASS'];
        $port    = isset($_ENV['DB_PORT']) &&
        trim((string)$_ENV['DB_PORT']) !== '' ? trim((string)$_ENV['DB_PORT']) : null;
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
        if ($port !== null) {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        }

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            $portInfo = $port !== null ? $port : '(default)';
            error_log("[Database] Connected DSN host={$host} port={$portInfo} db={$name}");

            self::$instance = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            error_log('[Database] Connection failed: ' . $e->getMessage() . " | DSN={$dsn} | user={$user}");
            http_response_code(500);
            echo '500 — Server Error (DB connection).';
            exit;
        }
    }
}