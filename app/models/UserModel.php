<?php

declare(strict_types=1);

namespace modules\models;

use PDO;
use PDOException;

/**
 * User Model.
 *
 * Handles all database operations related to users, including authentication,
 * user creation, and retrieval of user information.
 *
 * @package modules\models
 */
class UserModel
{
    /**
     * Database connection instance.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Name of the users table.
     *
     * @var string
     */
    private string $table;

    /**
     * Constructor.
     *
     * Initializes the model with a PDO connection and configures error handling.
     *
     * @param PDO $pdo Database connection.
     * @param string $table Table name (defaults to 'users').
     */
    public function __construct(PDO $pdo, string $table = 'users')
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Retrieves a user by email (including profession label).
     *
     * Performs a LEFT JOIN with the professions table if available to retrieve
     * the profession label alongside user data.
     *
     * @param string $email User email address.
     * @return array|null User data array or null if not found.
     */
    public function getByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        $availableColumns = $this->getTableColumns();

        $select = [
            'u.id_user',
            'u.first_name',
            'u.last_name',
            'u.email',
            'u.password',
            'u.admin_status'
        ];

        foreach (['id_profession', 'birth_date', 'age', 'created_at'] as $opt) {
            if (in_array($opt, $availableColumns, true)) {
                $select[] = "u.$opt";
            }
        }

        $selectClause = implode(", ", $select);
        $params = [':email' => $email];

        $canJoinProf =
            in_array('id_profession', $availableColumns, true)
            && $this->tableExists('professions')
            && $this->tableHasColumn('professions', 'id_profession')
            && $this->tableHasColumn('professions', 'label_profession');

        if ($canJoinProf) {
            $sqlWithJoin = "SELECT $selectClause, p.label_profession AS profession_label
                        FROM {$this->table} AS u
                        LEFT JOIN professions AS p ON p.id_profession = u.id_profession
                        WHERE u.email = :email
                        LIMIT 1";
            try {
                $st = $this->pdo->prepare($sqlWithJoin);
                $st->execute($params);
                $row = $st->fetch();
                if ($row !== false) {
                    return $row;
                }
            } catch (PDOException $e) {
            }
        }

        $sql = "SELECT $selectClause
            FROM {$this->table} AS u
            WHERE u.email = :email
            LIMIT 1";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Retrieves a user by their ID.
     *
     * @param int $id User ID.
     * @return array|null User data array or null if not found.
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id_user = :id_user LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_user' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Verifies user credentials.
     *
     * Checks if the provided email and password match a user in the database.
     * Returns user data (without password) on success, null on failure.
     *
     * @param string $email User email address.
     * @param string $plainPassword Plain text password to verify.
     * @return array|null User data without password, or null if verification fails.
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

    /**
     * Creates a new user and returns their ID.
     *
     * Expected fields:
     *  - first_name, last_name, email, password (required)
     *  - id_profession (int), admin_status (0/1), birth_date (nullable), created_at (optional)
     *
     * @param array $data User data array.
     * @return int The ID of the newly created user.
     * @throws PDOException If insertion fails or returns an invalid ID.
     */
    public function create(array $data): int
    {
        $availableColumns = $this->getTableColumns();
        $fields = ['first_name', 'last_name', 'email', 'password', 'admin_status', 'id_profession'];
        $values = [
            ':first_name' => (string) $data['first_name'],
            ':last_name' => (string) $data['last_name'],
            ':email' => strtolower(trim((string) $data['email'])),
            ':password' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            ':admin_status' => (int) ($data['admin_status'] ?? 0),
            ':id_profession' => $data['id_profession'] ?? null,
        ];
        if (in_array('birth_date', $availableColumns)) {
            $fields[] = 'birth_date';
            $values[':birth_date'] = $data['birth_date'] ?? null;
        }
        if (in_array('created_at', $availableColumns)) {
            $fields[] = 'created_at';
            $values[':created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        }
        $fieldsList = implode(', ', $fields);
        $placeholders = implode(', ', array_map(fn($f) => ":$f", $fields));
        $sql = "INSERT INTO {$this->table} ($fieldsList) VALUES ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        $id = (int) $this->pdo->lastInsertId();
        if ($id <= 0) {
            throw new PDOException('User insertion failed: lastInsertId=0');
        }
        return $id;
    }

    /**
     * Lists users for login selection.
     *
     * @param int $limit Maximum number of users to retrieve (default: 500).
     * @return array Array of user records with id_user, first_name, last_name, email.
     */
    public function listUsersForLogin(int $limit = 500): array
    {
        $sql = "SELECT id_user, first_name, last_name, email
            FROM {$this->table}
            ORDER BY last_name, first_name
            LIMIT :lim";
        $st = $this->pdo->prepare($sql);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets all column names for the current table.
     *
     * @return array List of column names.
     */
    private function getTableColumns(): array
    {
        try {
            $stmt = $this->pdo->query("PRAGMA table_info({$this->table})");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($columns, 'name');
        } catch (PDOException $e) {
            return ['id_user', 'first_name', 'last_name', 'email', 'password', 'admin_status'];
        }
    }

    /**
     * Checks if a table exists in the database.
     *
     * @param string $tableName Table name to check.
     * @return bool True if table exists, false otherwise.
     */
    private function tableExists(string $tableName): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT name FROM sqlite_master WHERE type='table' AND name = :name"
            );
            $stmt->execute([':name' => $tableName]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Checks if a column exists in a table.
     *
     * @param string $tableName Table name.
     * @param string $columnName Column name to check.
     * @return bool True if column exists, false otherwise.
     */
    private function tableHasColumn(string $tableName, string $columnName): bool
    {
        try {
            $stmt = $this->pdo->query("PRAGMA table_info({$tableName})");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $column) {
                if ($column['name'] === $columnName) {
                    return true;
                }
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retrieves the list of all users (for doctor list usage).
     * Ideally, should filter by profession if possible.
     *
     * @return array List of users with id_user, first_name, last_name, email.
     */
    public function getAllDoctors(): array
    {
        $sql = "SELECT id_user, first_name, last_name, email FROM {$this->table} ORDER BY last_name ASC";

        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}