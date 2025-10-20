<?php
/**
 * DashMed — Modèle Utilisateur
 *
 * Ce modèle gère toutes les opérations de base de données liées aux utilisateurs,
 * incluant la récupération des enregistrements par email, la vérification des identifiants
 * et la création de nouveaux comptes utilisateur.
 *
 * @package   DashMed\Modules\Models
 * @author    DashMed Team
 * @license   Proprietary
 */

/**
 * Gère l'accès aux données pour les utilisateurs.
 *
 * Fournit des méthodes pour :
 *  - Récupérer un utilisateur par email
 *  - Vérifier les identifiants de connexion de manière sécurisée avec hachage de mot de passe
 *  - Créer un nouvel utilisateur avec mot de passe haché
 *
 * @see PDO
 */
namespace modules\models;

use PDO;

class userModel
{
    /**
     * Instance de connexion PDO à la base de données.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Nom de la table où les enregistrements utilisateur sont stockés.
     *
     * @var string
     */
    private string $table;

    /**
     * Constructeur.
     *
     * Initialise le modèle avec une connexion PDO et un nom de table personnalisé optionnel.
     *
     * @param PDO $pdo       Connexion à la base de données.
     * @param string $table  Nom de la table (par défaut 'users').
     */
    public function __construct(PDO $pdo, string $table = 'users')
    {
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Récupère un utilisateur unique par son adresse email.
     *
     * @param string $email  L'email de l'utilisateur.
     * @return array|null    L'enregistrement utilisateur ou null si non trouvé.
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
     * Vérifie les identifiants d'un utilisateur.
     *
     * Vérifie si un utilisateur existe pour l'email donné et vérifie que le mot de passe
     * en clair fourni correspond au mot de passe haché stocké.
     *
     * @param string $email          L'email de l'utilisateur.
     * @param string $plainPassword  Le mot de passe saisi par l'utilisateur.
     * @return array|null            Les données utilisateur sans le mot de passe si valide, sinon null.
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
     * Crée un nouvel enregistrement utilisateur dans la base de données.
     *
     * Hache le mot de passe fourni avant de le stocker et retourne l'ID inséré.
     * Lance une PDOException si l'insertion échoue (par ex. email en double).
     *
     * @param array $data  Tableau associatif contenant :
     *                     - first_name
     *                     - last_name
     *                     - email
     *                     - password
     *                     - profession (optionnel)
     *                     - admin_status (optionnel)
     * @return int  L'ID de l'utilisateur nouvellement créé.
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