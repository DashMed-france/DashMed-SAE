<?php

declare(strict_types=1);

/**
 * DashMed — Assistant de connexion à la base de données
 *
 * Cette classe fournit une instance unique (singleton) de connexion PDO à la base de données MySQL.
 * Elle lit automatiquement les variables de configuration depuis le fichier `.env` situé
 * deux niveaux au-dessus de ce fichier, et s’assure que tous les paramètres requis sont chargés.
 *
 * @package   DashMed\assets\includes
 * @author    Équipe DashMed
 * @license   Propriétaire
 */

/**
 * Singleton de connexion à la base de données.
 *
 * Responsabilités :
 *  - Charger les identifiants de la base de données depuis un fichier `.env`.
 *  - Vérifier que toutes les variables d’environnement nécessaires sont définies.
 *  - Établir et mettre en cache une connexion PDO réutilisable dans toute l’application.
 *
 * Exemple d’utilisation :
 * ```php
 * $pdo = Database::getInstance();
 * ```
 */

final class Database
{
    /**
     * Instance PDO mise en cache et partagée entre tous les appels à la base de données.
     *
     * @var PDO|null
     */
    private static ?PDO $instance = null;

    /**
     * Retourne une instance unique (singleton) de PDO.
     *
     * Si l’instance n’a pas encore été créée, cette méthode charge les variables
     * d’environnement, les valide, construit le DSN et établit une connexion
     * avec gestion des erreurs.
     *
     * @return PDO  L’instance PDO partagée.
     * @throws PDOException Si la connexion échoue.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance instanceof PDO) {
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
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $portInfo = $port !== null ? $port : '(default)';
            error_log("[Database] Connected DSN host={$host} port={$portInfo} db={$name}");

            self::$instance = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            error_log('[Database] Connection failed: ' . $e->getMessage() . " | DSN={$dsn} | user={$user}");
            http_response_code(500);
            echo '500 — Erreur serveur (connexion DB).';
            exit;
        }
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
