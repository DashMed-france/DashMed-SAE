<?php

/**
 * DashMed — Sign-in Model
 *
 * This model is responsible for managing user registration and retrieval.
 * It interacts directly with the database using PDO and provides methods to:
 *   - Retrieve a user by email
 *   - Insert new user accounts securely
 *
 * @package   DashMed\Modules\Models
 * @author    DashMed Team
 * @license   Proprietary
 */

declare(strict_types=1);

namespace modules\models;

use PDO;
use PDOException;

/**
 * Handles data persistence for user registration (sign-in).
 *
 * Provides CRUD-like functionality, currently supporting:
 *  - Fetching a user by email (read)
 *  - Creating a new user account (create)
 *
 * @see PDO
 */
class signinModel
{
    /**
     * PDO database connection used for executing queries.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Table name where user records are stored.
     *
     * @var string
     */
    private string $table;

    /**
     * Constructor.
     *
     * Initializes the model with a PDO connection and sets the default user table.
     *
     * @param PDO $pdo     Database connection.
     * @param string $table  Optional custom table name (default: 'users').
     */
    public function __construct(PDO $pdo, string $table = 'users')
    {
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo   = $pdo;
        $this->table = $table;
    }

    /**
     * Retrieves a user record by email.
     *
     * @param string $email  Email address to look up.
     * @return array|null    Returns the user record if found, null otherwise.
     */
    public function getByEmail(string $email): ?array
    {
        $sql = "SELECT id_user, first_name, last_name, email, password, profession, admin_status
                FROM {$this->table}
                WHERE email = :email
                LIMIT 1";
        $st = $this->pdo->prepare($sql);
        $st->execute([':email' => $email]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /**
     * Creates a new user record in the database.
     *
     * Hashes the provided password before storing and returns the inserted ID.
     * Throws a PDOException if insertion fails (e.g. duplicate email).
     *
     * @param array $data  Associative array containing:
     *                     - first_name
     *                     - last_name
     *                     - email
     *                     - password
     *                     - profession (optional)
     *                     - admin_status (optional)
     * @return int  The ID of the newly created user.
     * @throws PDOException
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table}
                (first_name, last_name, email, password, profession, admin_status)
                VALUES (:first_name, :last_name, :email, :password, :profession, :admin_status)";
        $st = $this->pdo->prepare($sql);
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);

        try {
            $st->execute([
                ':first_name'   => $data['first_name'],
                ':last_name'    => $data['last_name'],
                ':email'        => $data['email'],
                ':password'     => $hash,
                ':profession'   => $data['profession'] ?? null,
                ':admin_status' => (int)($data['admin_status'] ?? 0),
            ]);
        } catch (PDOException $e) {
            throw $e;
        }

        return (int)$this->pdo->lastInsertId();
    }
}
