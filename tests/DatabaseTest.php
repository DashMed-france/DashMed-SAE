<file name=0 path=DatabaseTest.php><?php

/**
 * Premiers tests PHPUnit pour DashMed
 * -----------------------------------
 * Ces tests utilisent une base de données SQLite en mémoire afin de ne pas toucher à la vraie base de données.
 * Ils montrent comment :
 *  - préparer une base de données propre pour chaque test (setUp)
 *  - exécuter des requêtes et faire des assertions
 *  - vérifier que les contraintes (comme UNIQUE) sont respectées
 */

use PHPUnit\Framework\TestCase;
use PDO;            // PDO intégré à PHP
use PDOException;   // Pour gérer les exceptions attendues

class DatabaseTest extends TestCase
{
    private PDO $pdo;

    /**
     * Méthode exécutée avant chaque test.
     * Ici, on crée une nouvelle base de données SQLite en mémoire.
     * Cela est très rapide et isolé, parfait pour les tests unitaires.
     */
    protected function setUp(): void
    {
        // `sqlite::memory:` crée une base de données temporaire qui existe uniquement pendant le test
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Schéma minimal similaire à une table `users` que vous pourriez avoir dans DashMed
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
     * Test de base pour vérifier que PHPUnit fonctionne correctement.
     * Cette méthode sert simplement de preuve que le framework de test est bien configuré.
     */
    public function test_phpunit_is_running(): void
    {
        $this->assertTrue(true, 'PHPUnit est configuré et fonctionne.');
    }

    /**
     * Test d'insertion et de récupération d'un utilisateur.
     * On insère un utilisateur dans la base, puis on le récupère pour vérifier que les données sont correctes.
     */
    public function test_can_insert_and_fetch_user(): void
    {
        // Préparation : insertion d'un utilisateur
        $stmt = $this->pdo->prepare('INSERT INTO users(first_name, last_name, email, password) VALUES(?, ?, ?, ?)');
        $stmt->execute(['Jean', 'Khül', 'jean.khul@example.com', password_hash('secret', PASSWORD_DEFAULT)]);

        // Action : récupération par email
        $stmt = $this->pdo->prepare('SELECT id, first_name, last_name, email FROM users WHERE email = ? LIMIT 1');
        $stmt->execute(['jean.khul@example.com']);
        $row = $stmt->fetch();

        // Vérification : l'utilisateur récupéré correspond bien aux données insérées
        $this->assertNotFalse($row, 'L\'utilisateur doit être trouvé.');
        $this->assertSame('Jean', $row['first_name']);
        $this->assertSame('Khül', $row['last_name']);
        $this->assertSame('jean.khul@example.com', $row['email']);
        $this->assertArrayHasKey('id', $row);
    }

    /**
     * Test pour démontrer que la contrainte d'unicité sur l'email est bien appliquée.
     * On essaie d'insérer deux utilisateurs avec le même email, ce qui doit provoquer une exception.
     */
    public function test_unique_email_is_enforced(): void
    {
        $this->pdo->prepare('INSERT INTO users(first_name, last_name, email, password) VALUES(?, ?, ?, ?)')
                  ->execute(['Alan', 'Turing', 'alan@example.com', 'x']);

        // On s'attend à une exception PDOException lors de l'insertion d'un email en double à cause de la contrainte UNIQUE
        $this->expectException(PDOException::class);
        $this->pdo->prepare('INSERT INTO users(first_name, last_name, email, password) VALUES(?, ?, ?, ?)')
                  ->execute(['Another', 'Person', 'alan@example.com', 'y']);
    }
}
