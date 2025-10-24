<?php
/**
 * DashMed — Modèle Utilisateur
 */
declare(strict_types=1);

namespace modules\models;

use PDO;
use PDOException;

class userModel
{
    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo, string $table = 'users')
    {
        // Assure des erreurs visibles si la factory ne l’a pas déjà fait
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo   = $pdo;
        $this->table = $table;
    }

    /**
     * Récupère un utilisateur par email.
     */
    public function getByEmail(string $email): ?array
    {
        $sql = "
            SELECT
                id_user,
                first_name,
                last_name,
                email,
                password,
                profession_id,     -- <<< colonne correcte
                admin_status,
                birth_date,
                age,
                created_at
            FROM {$this->table}
            WHERE email = :email
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => strtolower(trim($email))]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Vérifie les identifiants (email + mot de passe).
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
     * Crée un utilisateur et renvoie son id.
     *
     * $data attend :
     *  - first_name, last_name, email, password
     *  - profession_id (int) obligatoire dans ton métier
     *  - admin_status (0/1), birth_date (nullable), created_at (optionnel)
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO {$this->table}
                (first_name, last_name, email, password, admin_status, birth_date, profession_id, created_at)
            VALUES
                (:first_name, :last_name, :email, :password, :admin_status, :birth_date, :profession_id, :created_at)
        ";

        // Utilise bcrypt explicitement (équivaut à PASSWORD_DEFAULT en 7.4, plus explicite)
        $hash = password_hash((string)$data['password'], PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':first_name'    => (string)$data['first_name'],
            ':last_name'     => (string)$data['last_name'],
            ':email'         => strtolower(trim((string)$data['email'])),
            ':password'      => $hash,
            ':admin_status'  => (int)($data['admin_status'] ?? 0),
            ':birth_date'    => $data['birth_date'] ?? null,
            ':profession_id' => (int)$data['profession_id'],
            ':created_at'    => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ]);

        $id = (int)$this->pdo->lastInsertId();
        if ($id <= 0) {
            throw new PDOException('Insertion utilisateur échouée: lastInsertId=0');
        }
        return $id;
    }
}