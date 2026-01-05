<?php

declare(strict_types=1);

namespace modules\models;

use PDO;
use PDOException;

class UserModel
{
    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo, string $table = 'users')
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Récupère un utilisateur par email (+ libellé de profession).
     */
    public function getByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        $availableColumns = $this->getTableColumns();

        // Colonnes sûres et minimales
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

        // Teste si on PEUT joindre la table professions en toute sécurité
        $canJoinProf =
            in_array('id_profession', $availableColumns, true)
            && $this->tableExists('professions')
            && $this->tableHasColumn('professions', 'id_profession')
            && $this->tableHasColumn('professions', 'label_profession');

        // 1) Tentative avec JOIN (si possible)
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
                // sinon on tente la requête simple
            } catch (PDOException $e) {
                // fallback silencieux
            }
        }

        // 2) Requête simple, infaillible (pas de JOIN)
        $sql = "SELECT $selectClause
            FROM {$this->table} AS u
            WHERE u.email = :email
            LIMIT 1";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();

        return $row !== false ? $row : null;
    }

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
     * Crée un utilisateur et renvoie son id.
     *
     * Champs attendus :
     *  - first_name, last_name, email, password (obligatoires)
     *  - id_profession (int), admin_status (0/1), birth_date (nullable), created_at (optionnel)
     */
    public function create(array $data): int
    {
        // Get available columns to build dynamic INSERT
        $availableColumns = $this->getTableColumns();
        // Required fields
        $fields = ['first_name', 'last_name', 'email', 'password', 'admin_status', 'id_profession'];
        $values = [
            ':first_name' => (string) $data['first_name'],
            ':last_name' => (string) $data['last_name'],
            ':email' => strtolower(trim((string) $data['email'])),
            ':password' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            ':admin_status' => (int) ($data['admin_status'] ?? 0),
            ':id_profession' => $data['id_profession'] ?? null,
        ];
        // Add optional fields if they exist in the table
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
            throw new PDOException('Insertion utilisateur échouée: lastInsertId=0');
        }
        return $id;
    }

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
     * Get all column names for the current table
     */
    private function getTableColumns(): array
    {
        try {
            $stmt = $this->pdo->query("PRAGMA table_info({$this->table})");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($columns, 'name');
        } catch (PDOException $e) {
            // Fallback: assume standard columns exist
            return ['id_user', 'first_name', 'last_name', 'email', 'password', 'admin_status'];
        }
    }

    /**
     * Vérifie si une table existe dans la base de données.
     *
     * @param string $tableName Nom de la table à vérifier
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
     * Vérifie si une colonne existe dans une table.
     *
     * @param string $tableName Nom de la table
     * @param string $columnName Nom de la colonne à vérifier
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
     * Récupère la liste de tous les utilisateurs (pour usage liste médecins).
     * Idéalement, filtrer par profession si possible.
     */
    public function getAllDoctors(): array
    {
        // Si la colonne id_profession existe, on pourrait filtrer.
        // Pour l'instant, on retourne tous les utilisateurs triés par nom.
        $sql = "SELECT id_user, first_name, last_name, email FROM {$this->table} ORDER BY last_name ASC";

        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
