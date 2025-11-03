<?php
/**
 * DashMed — Modèle Patient
 *
 * Ce modèle gère toutes les opérations de base de données liées aux Patients,
 * incluant la récupération des enregistrements par email, la vérification des identifiants
 * et la création de nouveaux comptes Patient.
 *
 * @package   DashMed\Modules\Models
 * @author    DashMed Team
 * @license   Proprietary
 */

/**
 * Gère l'accès aux données pour les Patients.
 *
 * Fournit des méthodes pour :
 *  - Créer un nouvel Patient dans la base de donnée
 *
 * @see PDO
 */
namespace modules\models;

use PDO;
use PDOException;

class patientModel
{
    /**
     * Instance de connexion PDO à la base de données.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Nom de la table où les enregistrements patients sont stockés.
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
     * @param string $table  Nom de la table (par défaut 'patients').
     */
    public function __construct(PDO $pdo, string $table = 'patients')
    {
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Crée un nouvel enregistrement patient dans la base de données.
     *
     * Lance une PDOException si l'insertion échoue.
     *
     * @param array $data  Tableau associatif contenant :
     *                     - first_name
     *                     - last_name
     *                     - email
     *                     - password
     *                     - profession (optionnel)
     *                     - admin_status (optionnel)
     * @return int  L'ID du patient nouvellement créé.
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

// TODO le finir