<?php

/**
 * First PHPUnit tests for DashMed
 * -----------------------------------
 * These tests use an in-memory SQLite database to avoid touching the real database.
 * They demonstrate how to:
 *  - prepare a clean database for each test (setUp)
 *  - execute queries and make assertions
 *  - verify that constraints (like UNIQUE) are respected
 */

use PHPUnit\Framework\TestCase;

/**
 * Database test suite
 *
 * Tests database operations using an in-memory SQLite database
 * for isolation and speed.
 */
class DatabaseTest extends TestCase
{
    /**
     * @var PDO PDO instance for database testing
     */
    private PDO $pdo;

    /**
     * Set up test environment before each test
     *
     * Creates a new in-memory SQLite database with a users table.
     * This is very fast and isolated, perfect for unit testing.
     *
     * @return void
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
     * Basic test to verify that PHPUnit is working correctly
     *
     * This method simply serves as proof that the test framework is properly configured.
     *
     * @return void
     */
    public function test_phpunit_is_running(): void
    {
        $this->assertTrue(true, 'PHPUnit est configuré et fonctionne.');
    }

    /**
     * Test user insertion and retrieval
     *
     * Inserts a user into the database, then retrieves it to verify that the data is correct.
     *
     * @return void
     */
    public function test_can_insert_and_fetch_user(): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO users(first_name, last_name, email, password) VALUES(?, ?, ?, ?)');
        $stmt->execute(['Jean', 'Khül', 'jean.khul@example.com', password_hash('secret', PASSWORD_DEFAULT)]);

        $stmt = $this->pdo->prepare('SELECT id, first_name, last_name, email FROM users WHERE email = ? LIMIT 1');
        $stmt->execute(['jean.khul@example.com']);
        $row = $stmt->fetch();

        $this->assertNotFalse($row, 'L\'utilisateur doit être trouvé.');
        $this->assertSame('Jean', $row['first_name']);
        $this->assertSame('Khül', $row['last_name']);
        $this->assertSame('jean.khul@example.com', $row['email']);
        $this->assertArrayHasKey('id', $row);
    }

    /**
     * Test that the unique constraint on email is properly enforced
     *
     * Attempts to insert two users with the same email, which should throw an exception.
     *
     * @return void
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