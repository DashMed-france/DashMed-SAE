<?php

/**
 * Class DatabaseTest | Tests de la Base de Données
 *
 * PHPUnit tests using in-memory SQLite database.
 * Tests PHPUnit utilisant une base de données SQLite en mémoire.
 *
 * Demonstrates isolation and basic CRUD operations.
 * Démontre l'isolation et les opérations CRUD de base.
 *
 * @package Tests
 * @author DashMed Team
 */

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    /** @var PDO Database connection | Connexion à la base de données */
    private PDO $pdo;

    /**
     * Sets up the test environment.
     * Prépare l'environnement de test.
     *
     * Creates a fresh in-memory SQLite database for each test.
     * Crée une nouvelle base SQLite en mémoire pour chaque test.
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name  TEXT NOT NULL,
                email      TEXT NOT NULL UNIQUE,
                password   TEXT NOT NULL
            )'
        );
    }

    /**
     * Verifies that PHPUnit is running correctly.
     * Vérifie que PHPUnit s'exécute correctement.
     */
    public function test_phpunit_is_running(): void
    {
        $this->assertTrue(true, 'PHPUnit est configuré et fonctionne.');
    }

    /**
     * Tests inserting and fetching a user.
     * Teste l'insertion et la récupération d'un utilisateur.
     */
    public function test_can_insert_and_fetch_user(): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO users(first_name, last_name, email, password) VALUES(?, ?, ?, ?)');
        $stmt->execute(['Jean', 'Khül', 'jean.khul@example.com', password_hash('secret', PASSWORD_DEFAULT)]);

        $stmt = $this->pdo->prepare('SELECT id, first_name, last_name, email FROM users WHERE email = ? LIMIT 1');
        $stmt->execute(['jean.khul@example.com']);
        $row = $stmt->fetch();

        $this->assertNotFalse($row, 'User must be found | L\'utilisateur doit être trouvé.');
        $this->assertSame('Jean', $row['first_name']);
        $this->assertSame('Khül', $row['last_name']);
        $this->assertSame('jean.khul@example.com', $row['email']);
        $this->assertArrayHasKey('id', $row);
    }

    /**
     * Tests unique email constraint.
     * Teste la contrainte d'unicité de l'email.
     */
    public function test_unique_email_is_enforced(): void
    {
        $this->pdo->prepare('INSERT INTO users(first_name, last_name, email, password) VALUES(?, ?, ?, ?)')
            ->execute(['Alan', 'Turing', 'alan@example.com', 'x']);

        $this->expectException(PDOException::class);
        $this->pdo->prepare('INSERT INTO users(first_name, last_name, email, password) VALUES(?, ?, ?, ?)')
            ->execute(['Another', 'Person', 'alan@example.com', 'y']);
    }
}
