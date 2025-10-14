<?php
/**
 * DashMed — Login Model
 *
 * This model handles all database operations related to user authentication,
 * including fetching user records by email and verifying credentials.
 *
 * @package   DashMed\Modules\Models
 * @author    DashMed Team
 * @license   Proprietary
 */

/**
 * Handles data access for user authentication.
 *
 * Provides methods to:
 *  - Retrieve a user by email
 *  - Verify login credentials securely using password hashing
 *
 * @see PDO
 */
namespace modules\models;

use PDO;

class loginModel
{
    /**
     * PDO database connection instance.
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
     * Initializes the model with a PDO connection and optional custom table name.
     *
     * @param PDO $pdo    Database connection.
     * @param string $table  Table name (defaults to 'users').
     */
    public function __construct(PDO $pdo, string $table = 'users')
    {
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Retrieves a single user by their email address.
     *
     * @param string $email  The user's email.
     * @return array|null    The user record or null if not found.
     */
    public function getByEmail(string $email): ?array
    {
        $sql = "SELECT id_user, first_name, last_name, email, password, profession, admin_status
                FROM {$this->table}
                WHERE email = :email
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Verifies a user's credentials.
     *
     * Checks if a user exists for the given email and verifies that the provided
     * plain-text password matches the stored hashed password.
     *
     * @param string $email          The user's email.
     * @param string $plainPassword  The password entered by the user.
     * @return array|null            The user data without the password if valid, otherwise null.
     */
    public function verifyCredentials(string $email, string $plainPassword): ?array
    {
        $user = $this->getByEmail($email);
        if (!$user) {
            return null;
        }
        if (!password_verify($plainPassword, $user['password'])) {
            return null;
        }
        unset($user['password']);
        return $user;
    }
}
