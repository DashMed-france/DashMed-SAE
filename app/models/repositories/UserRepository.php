<?php

declare(strict_types=1);

namespace modules\models\repositories;

use modules\models\base\BaseRepository;
use modules\models\entities\User;
use PDO;
use PDOException;

/**
 * Class UserRepository
 *
 * Manages user accounts (doctors/staff).
 *
 * @package DashMed\Modules\Models\Repositories
 * @author DashMed Team
 * @license Proprietary
 */
class UserRepository extends BaseRepository
{
    private string $table = 'users';

    /**
     * Retrieves a user by email.
     *
     * @param string $email
     * @return User|null User entity or null
     */
    public function getByEmail(string $email): ?User
    {
        $email = strtolower(trim($email));

        $sql = "SELECT u.*, p.label_profession AS profession_label
                FROM {$this->table} AS u
                LEFT JOIN professions AS p ON p.id_profession = u.id_profession
                WHERE u.email = :email
                LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return $this->mapRowToUser($row);
            }
        } catch (PDOException $e) {
            $sqlFallback = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
            $stmt = $this->pdo->prepare($sqlFallback);
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row)
                return $this->mapRowToUser($row);
        }

        return null;
    }

    /**
     * Retrieves a user by ID.
     *
     * @param int $id User ID
     * @return User|null
     */
    public function getById(int $id): ?User
    {
        $sql = "SELECT u.*, p.label_profession AS profession_label
                FROM {$this->table} AS u
                LEFT JOIN professions AS p ON p.id_profession = u.id_profession
                WHERE u.id_user = :id
                LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->mapRowToUser($row) : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Verifies user credentials.
     *
     * @param string $email
     * @param string $plainPassword
     * @return User|null User entity if valid, null otherwise
     */
    public function verifyCredentials(string $email, string $plainPassword): ?User
    {
        $user = $this->getByEmail($email);
        if (!$user || !$user->getPassword()) {
            return null;
        }

        if (password_verify($plainPassword, $user->getPassword())) {
            return $user;
        }

        return null;
    }

    /**
     * Creates a new user.
     *
     * @param array $data Raw data array
     * @return int New User ID
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (first_name, last_name, email, password, admin_status, id_profession, created_at)
                VALUES (:first_name, :last_name, :email, :password, :admin_status, :id_profession, :created_at)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':email' => strtolower(trim($data['email'])),
                ':password' => password_hash($data['password'], PASSWORD_BCRYPT),
                ':admin_status' => (int) ($data['admin_status'] ?? 0),
                ':id_profession' => $data['id_profession'] ?? null,
                ':created_at' => date('Y-m-d H:i:s')
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lists users for login (returns array for compatibility/performance).
     * 
     * @return array
     */
    public function listUsersForLogin(int $limit = 500): array
    {
        $sql = "SELECT id_user, first_name, last_name, email
                FROM {$this->table}
                ORDER BY last_name, first_name
                LIMIT :lim";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets all doctors.
     * 
     * @return array
     */
    public function getAllDoctors(): array
    {
        $sql = "SELECT id_user, first_name, last_name, email FROM {$this->table} ORDER BY last_name ASC";
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (PDOException $e) {
            return [];
        }
    }

    protected function mapRowToUser(array $row): User
    {
        return new User(
            (int) $row['id_user'],
            (string) $row['first_name'],
            (string) $row['last_name'],
            (string) $row['email'],
            (int) ($row['admin_status'] ?? 0),
            $row['password'] ?? null,
            isset($row['id_profession']) ? (int) $row['id_profession'] : null,
            $row['profession_label'] ?? null
        );
    }
}
