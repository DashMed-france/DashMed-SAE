<?php

namespace models;

use PHPUnit\Framework\TestCase;
use modules\models\loginModel;

use PDO;            // PDO intégré à PHP
use PDOException;

/**
 * Tests unitaires pour la classe loginModel.
 * ----------------------------------------
 * Ces tests valident les fonctionnalités de connexion et de recherche d'utilisateur par email.
 * Ils utilisent une base de données SQLite en mémoire pour garantir un environnement propre et isolé.
 */
class loginModelTest extends TestCase
{
    private \PDO $pdo;
    private loginModel $model;

    /**
     * Méthode exécutée avant chaque test.
     * Ici, on crée une nouvelle base de données SQLite en mémoire et la table 'users'.
     * Cela est très rapide et isole chaque test, assurant un état initial connu et propre.
     */
    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE users (
                id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                profession TEXT,
                admin_status INTEGER DEFAULT 0
            )
        ");

        $this->model = new loginModel($this->pdo);
    }


    /**
     * Test de base pour vérifier que le constructeur de loginModel fonctionne.
     * On s'assure que l'objet est bien créé sans erreur.
     */
    public function testConstructor(): void
    {
        $model = new loginModel($this->pdo);
        $this->assertInstanceOf(loginModel::class, $model);
    }

    /**
     * Teste le constructeur lorsque l'on spécifie un nom de table différent.
     * Cela vérifie la flexibilité du modèle à opérer sur différentes tables d'utilisateurs.
     */
    public function testConstructorWithCustomTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE custom_users (
                id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                profession TEXT,
                admin_status INTEGER DEFAULT 0
            )
        ");

        $model = new loginModel($this->pdo, 'custom_users');
        $this->assertInstanceOf(loginModel::class, $model);
    }

    /**
     * Teste la méthode 'getByEmail' dans le cas idéal : l'utilisateur existe.
     * On vérifie que toutes les données de l'utilisateur sont correctement récupérées.
     */
    public function testGetByEmailReturnsUserWhenExists(): void
    {
        $hashedPassword = password_hash('testpassword', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, profession, admin_status)
            VALUES ('John', 'Doe', 'john.doe@example.com', '{$hashedPassword}', 'Doctor', 1)
        ");

        $user = $this->model->getByEmail('john.doe@example.com');

        $this->assertNotNull($user);
        $this->assertIsArray($user);
        $this->assertEquals('John', $user['first_name']);
        $this->assertEquals('Doe', $user['last_name']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals('Doctor', $user['profession']);
        $this->assertEquals(1, $user['admin_status']);
        $this->assertArrayHasKey('password', $user);
    }

    /**
     * Teste 'getByEmail' lorsque l'email n'est associé à aucun utilisateur.
     * On s'attend à ce que la méthode retourne 'null'.
     */
    public function testGetByEmailReturnsNullWhenNotExists(): void
    {
        $user = $this->model->getByEmail('nonexistent@example.com');
        $this->assertNull($user);
    }

    /**
     * Teste 'getByEmail' avec une chaîne d'email vide.
     * Le modèle doit gérer cette entrée en retournant 'null'.
     */
    public function testGetByEmailWithEmptyEmail(): void
    {
        $user = $this->model->getByEmail('');
        $this->assertNull($user);
    }

    /**
     * Teste la méthode 'verifyCredentials' avec un email et un mot de passe valides.
     * C'est le test du scénario de connexion réussi.
     */
    public function testVerifyCredentialsReturnsUserWhenValid(): void
    {
        $plainPassword = 'securePassword123';
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, profession, admin_status)
            VALUES ('Jane', 'Smith', 'jane.smith@example.com', '{$hashedPassword}', 'Nurse', 0)
        ");

        $user = $this->model->verifyCredentials('jane.smith@example.com', $plainPassword);

        $this->assertNotNull($user);
        $this->assertIsArray($user);
        $this->assertEquals('Jane', $user['first_name']);
        $this->assertEquals('Smith', $user['last_name']);
        $this->assertEquals('jane.smith@example.com', $user['email']);
        $this->assertEquals('Nurse', $user['profession']);
        $this->assertEquals(0, $user['admin_status']);
        $this->assertArrayNotHasKey('password', $user);
    }

    /**
     * Teste 'verifyCredentials' lorsque l'email est valide mais que le mot de passe est incorrect.
     * Le modèle doit refuser la connexion et retourner 'null'.
     */
    public function testVerifyCredentialsReturnsNullWhenPasswordIncorrect(): void
    {
        $hashedPassword = password_hash('correctPassword', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, profession, admin_status)
            VALUES ('Bob', 'Johnson', 'bob.johnson@example.com', '{$hashedPassword}', 'Admin', 1)
        ");

        $user = $this->model->verifyCredentials('bob.johnson@example.com', 'wrongPassword');
        $this->assertNull($user);
    }

    /**
     * Teste 'verifyCredentials' lorsque l'email n'existe pas.
     * Le modèle doit retourner 'null'.
     */
    public function testVerifyCredentialsReturnsNullWhenEmailNotExists(): void
    {
        $user = $this->model->verifyCredentials('nonexistent@example.com', 'anyPassword');
        $this->assertNull($user);
    }

    /**
     * Teste 'verifyCredentials' avec un mot de passe vide.
     * Le modèle doit empêcher la connexion et retourner 'null'.
     */
    public function testVerifyCredentialsWithEmptyPassword(): void
    {
        $hashedPassword = password_hash('testpassword', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, profession, admin_status)
            VALUES ('Test', 'User', 'test@example.com', '{$hashedPassword}', 'Doctor', 0)
        ");

        $user = $this->model->verifyCredentials('test@example.com', '');
        $this->assertNull($user);
    }

    /**
     * Teste que 'getByEmail' retourne bien UN SEUL utilisateur, même si plusieurs existent dans la table.
     * Cela confirme que la requête SQL utilise une clause LIMIT 1 ou un mécanisme similaire.
     */
    public function testGetByEmailReturnsOnlyOneUser(): void
    {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, profession, admin_status)
            VALUES 
                ('User', 'One', 'user1@example.com', '{$hashedPassword}', 'Doctor', 0),
                ('User', 'Two', 'user2@example.com', '{$hashedPassword}', 'Nurse', 1)
        ");

        $user = $this->model->getByEmail('user1@example.com');
        $this->assertNotNull($user);
        $this->assertEquals('user1@example.com', $user['email']);
        $this->assertEquals('User', $user['first_name']);
        $this->assertEquals('One', $user['last_name']);
    }

    /**
     * Teste que les données d'utilisateur sont bien récupérées sous forme de tableau associatif.
     * C'est à dire que les cléfs sont les noms des colonnes ('first_name'), et non des indices numériques (0).
     */
    public function testPdoFetchModeIsAssociative(): void
    {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, profession, admin_status)
            VALUES ('Test', 'User', 'test@example.com', '{$hashedPassword}', 'Doctor', 0)
        ");

        $user = $this->model->getByEmail('test@example.com');

        $this->assertIsArray($user);
        $this->assertArrayHasKey('first_name', $user);
        $this->assertArrayNotHasKey(0, $user);
    }
}