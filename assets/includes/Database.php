<?php

/**
 * Class Database
 *
 * Provides a singleton PDO database connection.
 *
 * Automatically loads configuration from .env file.
 *
 * @package DashMed\Assets\Includes
 * @author DashMed Team
 * @license Proprietary
 *
 * @access public
 */

declare(strict_types=1);

namespace assets\includes;

final class Database
{
    /**
     * @var \PDO|null Cached PDO instance.
     */
    private static ?\PDO $instance = null;

    /**
     * Returns the singleton PDO instance.
     *
     * Loads env variables, validates them, and established connection.
     *
     * @return \PDO Shared PDO instance.
     * @throws \PDOException If connection fails.
     */
    public static function getInstance(): \PDO
    {
        if (self::$instance instanceof \PDO) {
            return self::$instance;
        }

        $envPath = __DIR__ . '/../../.env';
        if (!is_file($envPath) || !is_readable($envPath)) {
            error_log('[Database] .env introuvable ou illisible à ' . $envPath);
            http_response_code(500);
            echo '500 — Erreur serveur (.env manquant).';
            exit;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            error_log('[Database] Impossible de lire le fichier .env');
            http_response_code(500);
            echo '500 — Erreur serveur (lecture .env impossible).';
            exit;
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

        $required = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'];
        foreach ($required as $key) {
            $envValue = $_ENV[$key] ?? null;
            if (!is_string($envValue) || trim($envValue) === '') {
                error_log("[Database] Variable $key manquante ou vide dans .env");
                http_response_code(500);
                echo '500 — Erreur serveur (configuration DB incomplète).';
                exit;
            }
        }

        /** @var string $hostEnv */
        $hostEnv = $_ENV['DB_HOST'];
        /** @var string $nameEnv */
        $nameEnv = $_ENV['DB_NAME'];
        /** @var string $userEnv */
        $userEnv = $_ENV['DB_USER'];
        /** @var string $passEnv */
        $passEnv = $_ENV['DB_PASS'];

        $host = trim($hostEnv);
        $name = trim($nameEnv);
        $user = trim($userEnv);
        $pass = $passEnv;
        $portEnv = $_ENV['DB_PORT'] ?? null;
        $port = is_string($portEnv) && trim($portEnv) !== '' ? trim($portEnv) : null;
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
        if ($port !== null) {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        }

        try {
            return self::connect($dsn, $user, $pass, $host, $name, $port);
        } catch (\PDOException $e) {
            // Fallback strategy: if 'db' host fails (standard in Docker), try '127.0.0.1'
            // This happens when running a local PHP server outside of Docker.
            if ($host === 'db' && (str_contains($e->getMessage(), 'php_network_getaddresses') || str_contains($e->getMessage(), 'Connection refused'))) {
                error_log("[Database] 'db' host unreachable, attempting fallback to 127.0.0.1");
                $fallbackHost = '127.0.0.1';
                $fallbackDsn = "mysql:host={$fallbackHost};dbname={$name};charset={$charset}";
                if ($port !== null) {
                    $fallbackDsn = "mysql:host={$fallbackHost};port={$port};dbname={$name};charset={$charset}";
                }
                
                try {
                    return self::connect($fallbackDsn, $user, $pass, $fallbackHost, $name, $port);
                } catch (\PDOException $fallbackEx) {
                    error_log('[Database] Fallback connection failed: ' . $fallbackEx->getMessage());
                }
            }

            error_log('[Database] Connection failed: ' . $e->getMessage() . " | DSN={$dsn} | user={$user}");
            http_response_code(500);
            echo '500 — Erreur serveur (connexion DB).';
            if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
                echo '<br>Détails : ' . htmlspecialchars($e->getMessage());
            }
            exit;
        }
    }

    /**
     * Establishes a PDO connection and sets shared instance.
     */
    private static function connect(string $dsn, string $user, string $pass, string $host, string $name, ?string $port): \PDO
    {
        $pdo = new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $portInfo = $port !== null ? $port : '(default)';
        error_log("[Database] Connected DSN host={$host} port={$portInfo} db={$name}");

        self::$instance = $pdo;
        return $pdo;
    }

    private function __construct()
    {
    }
    private function __clone()
    {
    }
    public function __wakeup()
    {
    }
}
