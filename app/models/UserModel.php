<?php

declare(strict_types=1);

namespace modules\models;

use PDO;
use PDOException;

/**
 * Class UserModel | Classe UserModel
 *
 * Manages user accounts (doctors/staff).
 * Gère les comptes utilisateurs (médecins/personnel).
 *
 * @package DashMed\Modules\Models
 * @author DashMed Team
 * @license Proprietary
 */
class UserModel
{
    /** @var PDO Database connection | Connexion BDD */
    private PDO $pdo;

    /** @var string Table name | Nom de la table */
    private string $table;

    /**
     * Constructor | Constructeur
     *
     * @param PDO $pdo
     * @param string $table
     */
    public function __construct(PDO $pdo, string $table = 'users')
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Retrieves a user by email.
     * Récupère un utilisateur par email.
     *
     * @param string $email
     * @return array|null User data or null | Données utilisateur ou null
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
     * Retrieves a user by ID.
     * Récupère un utilisateur par ID.
     *
     * @param int $id User ID | ID utilisateur
     * @return array|null
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
     * Vérifie les identifiants de l'utilisateur.
     *
     * @param string $email
     * @param string $plainPassword
     * @return array|null User data without password if valid, null otherwise | Données utilisateur sans mdp si valide, sinon null
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
     * Creates a new user.
     * Crée un nouvel utilisateur.
     *
     * @param array $data
     * @return int New User ID | ID du nouvel utilisateur
     * @throws PDOException
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
            throw new PDOException('User insertion failed: lastInsertId=0 | Insertion utilisateur échouée');
        }
        return $id;
    }

    /**
     * Lists users (simplified) for login selection.
     * Liste les utilisateurs (simplifié) pour la sélection de connexion.
     *
     * @param int $limit
     * @return array
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
     * Récupère tous les noms de colonnes de la table actuelle.
     *
     * @return array
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
     * Checks if a table exists.
     * Vérifie si une table existe.
     *
     * @param string $tableName
     * @return bool
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
     * Checks if a table has a specific column.
     * Vérifie si une table possède une colonne spécifique.
     *
     * @param string $tableName
     * @param string $columnName
     * @return bool
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
     * Gets all doctors.
     * Récupère tous les médecins.
     *
     * @return array
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
