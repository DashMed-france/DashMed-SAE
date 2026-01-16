<?php

namespace assets\includes;

/**
 * Mock of the Database class.
 * Mock de la classe Database.
 */
class Database
{
    private static ?\PDO $pdo = null;

    public static function getInstance(): \PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('PDO not initialized. Call Database::setInstance() first.');
        }
        return self::$pdo;
    }

    public static function setInstance(\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}
