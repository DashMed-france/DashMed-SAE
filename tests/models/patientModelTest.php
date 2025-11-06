<?php
declare(strict_types=1);

namespace models;

use modules\models\patientModel;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Class patientModelTest
 *
 * Tests unitaires pour le modèle patientModel (méthode create).
 * Utilise une base SQLite en mémoire pour garantir l'isolation des tests.
 *
 * Remarque : le modèle calcule un hash de mot de passe mais ne l'insère pas
 * (aucune colonne password dans la table patients). Ce test se concentre donc
 * uniquement sur les colonnes réellement écrites par create().
 *
 * @coversDefaultClass \modules\models\patientModel
 */
final class patientModelTest extends TestCase
{
    /**
     * Instance PDO pointant vers une base SQLite en mémoire.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Instance du modèle à tester.
     *
     * @var patientModel
     */
    private patientModel $model;

    /**
     * Prépare un schéma minimal cohérent avec le code de patientModel::create()
     * avant chaque test : création de la table patients et instanciation du modèle.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Base en mémoire + exceptions PDO activées
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Schéma strictement aligné avec l'INSERT du modèle (pas de colonne password ici)
        $this->pdo->exec("
            CREATE TABLE patients (
                id_patient   INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name   TEXT NOT NULL,
                last_name    TEXT NOT NULL,
                email        TEXT NOT NULL UNIQUE,
                birth_date   TEXT NOT NULL,
                weight       REAL NOT NULL,
                height       REAL NOT NULL,
                gender       TEXT NOT NULL,
                status       TEXT NOT NULL,
                description  TEXT NULL,
                room_id      INTEGER NULL,
                created_at   TEXT NOT NULL,
                updated_at   TEXT NULL
            );
        ");

        // Modèle pointant sur la table 'patients'
        $this->model = new patientModel($this->pdo, 'patients');
    }

    /**
     * Fournit un payload valide pour faciliter l'écriture des tests.
     * Les champs description/room_id sont optionnels côté modèle.
     *
     * @return array Données prêtes pour patientModel::create()
     */
    private function validPayload(): array
    {
        return [
            'first_name'  => 'Ada',
            'last_name'   => 'Lovelace',
            'email'       => 'ada@example.test',
            'birth_date'  => '1815-12-10',
            'weight'      => 55.5,
            'height'      => 165.0,
            'gender'      => 'F',
            'status'      => 'stable',
            // Champs optionnels côté modèle
            'description' => 'Patiente historique',
            'room_id'     => 101,
            // Le modèle calcule un hash mais ne l’insère pas (pas de colonne password)
            'password'    => 'secret'
        ];
    }

    /**
     * @covers ::create
     *
     * Vérifie qu'un enregistrement est inséré correctement et que l'ID retourné est > 0.
     * Vérifie aussi la cohérence des valeurs persistées (typage inclus).
     */
    public function testCreateInsertsRowAndReturnsId(): void
    {
        // Act
        $id = $this->model->create($this->validPayload());

        // Assert ID
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        // Lecture de la ligne insérée
        $st = $this->pdo->prepare("SELECT * FROM patients WHERE id_patient = :id");
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        // Assert contenu
        $this->assertNotFalse($row);
        $this->assertSame('Ada', $row['first_name']);
        $this->assertSame('Lovelace', $row['last_name']);
        $this->assertSame('ada@example.test', $row['email']);
        $this->assertSame('1815-12-10', $row['birth_date']);
        $this->assertEquals(55.5, (float)$row['weight']);  // normalisation flottante
        $this->assertEquals(165.0, (float)$row['height']);
        $this->assertSame('F', $row['gender']);
        $this->assertSame('stable', $row['status']);
        $this->assertSame('Patiente historique', $row['description']);
        $this->assertEquals(101, (int)$row['room_id']);

        // created_at est géré par le modèle ; updated_at doit rester NULL
        $this->assertNotEmpty($row['created_at']);
        $this->assertNull($row['updated_at']);
    }

    /**
     * @covers ::create
     *
     * Vérifie que les champs nullable (description, room_id) peuvent être insérés à NULL.
     */
    public function testCreateAllowsNullables(): void
    {
        // Arrange : payload avec champs optionnels à NULL
        $payload = $this->validPayload();
        $payload['description'] = null;
        $payload['room_id'] = null;
        $payload['email'] = 'nullables@example.test'; // éviter la contrainte UNIQUE

        // Act
        $id = $this->model->create($payload);

        // Assert : lecture + vérification des NULL
        $st = $this->pdo->prepare("SELECT description, room_id FROM patients WHERE id_patient = :id");
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        $this->assertNull($row['description']);
        $this->assertNull($row['room_id']);
    }

    /**
     * @covers ::create
     *
     * Vérifie qu'une violation d'unicité sur l'email déclenche bien une PDOException.
     */
    public function testCreateThrowsOnDuplicateEmail(): void
    {
        // Arrange
        $payload = $this->validPayload();
        $payload['email'] = 'dup@example.test';

        // Première insertion OK
        $this->model->create($payload);

        // Deuxième insertion avec le même email -> doit lever PDOException
        $this->expectException(PDOException::class);
        $this->model->create($payload);
    }

    /**
     * @covers ::create
     *
     * Vérifie qu'un champ requis manquant (ex: email) provoque une PDOException.
     */
    public function testCreateFailsWhenRequiredFieldMissing(): void
    {
        // Arrange : on supprime un champ NOT NULL
        $payload = $this->validPayload();
        unset($payload['email']); // NOT NULL au niveau du schéma

        // Assert + Act
        $this->expectException(PDOException::class);
        $this->model->create($payload);
    }
}
