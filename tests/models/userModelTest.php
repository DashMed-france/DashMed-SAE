<?php

namespace models;

use PHPUnit\Framework\TestCase;
use modules\models\userModel;
use modules\controllers\auth\SignupController;
use PDO;
use PDOException;

require_once __DIR__ . '/../../app/models/userModel.php';
require_once __DIR__ . '/../../app/controllers/auth/SignupController.php';


/**
 * Class userModelTest
 *
 * Tests unitaires pour le modèle userModel.
 * Utilise une base SQLite en mémoire pour l'isolation et la fiabilité des tests.
 *
 * @coversDefaultClass \modules\models\userModel
 */
class userModelTest extends TestCase
{
    /**
     * PDO instance pour la base de données SQLite en mémoire.
     *
     * @var PDO|null
     */
    private ?PDO $pdo = null;

    /**
     * Instance du modèle à tester.
     *
     * @var userModel|null
     */
    private ?userModel $model = null;

    /**
     * Configure l'environnement avant chaque test.
     * Crée une base SQLite en mémoire et la table users.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Create professions table first
        $this->pdo->exec("
            CREATE TABLE professions (
                id_profession INTEGER PRIMARY KEY AUTOINCREMENT,
                label_profession TEXT NOT NULL
            )
        ");

        // Create users table with profession_id instead of profession
        $this->pdo->exec("
            CREATE TABLE users (
                id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                profession_id INTEGER,
                admin_status INTEGER DEFAULT 0,
                birth_date TEXT,
                age INTEGER,
                created_at TEXT,
                FOREIGN KEY (profession_id) REFERENCES professions(id_profession)
            )
        ");

        // Insert some default professions for testing
        $this->pdo->exec("
            INSERT INTO professions (label_profession) VALUES 
                ('Doctor'),
                ('Nurse'),
                ('Administrator'),
                ('Pharmacist')
        ");

        $this->model = new userModel($this->pdo);
    }

    /**
     * Nettoie l'environnement après chaque test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo = null;
        $this->model = null;
    }

    /**
     * @covers ::getByEmail
     * Vérifie que getByEmail renvoie l'utilisateur complet lorsqu'il existe.
     */
    public function testGetByEmailReturnsUserWhenExists(): void
    {
        $hashedPassword = password_hash('testpassword', PASSWORD_DEFAULT);
        $this->pdo->exec("
        INSERT INTO users (first_name, last_name, email, password, profession_id, admin_status)
        VALUES ('John', 'Doe', 'john.doe@example.com', '{$hashedPassword}', 1, 1)
    ");

        $user = $this->model->getByEmail('john.doe@example.com');

        // Vérifications
        $this->assertNotNull($user, 'L\'utilisateur devrait être trouvé');
        $this->assertIsArray($user);
        $this->assertEquals('John', $user['first_name']);
        $this->assertEquals('Doe', $user['last_name']);
        $this->assertEquals('john.doe@example.com', $user['email']);
        $this->assertEquals('Doctor', $user['profession_label']); // Changed from profession to profession_label
        $this->assertEquals(1, (int)$user['admin_status']);
        $this->assertArrayHasKey('password', $user);
        $this->assertTrue(password_verify('testpassword', $user['password']));
    }


    /**
     * Teste que getByEmail retourne null si l'utilisateur n'existe pas.
     *
     * @covers ::getByEmail
     *
     * @return void
     */
    public function testGetByEmailReturnsNullWhenUserDoesNotExist(): void
    {
        $user = $this->model->getByEmail('nonexistent@example.com');

        $this->assertNull($user);
    }

    /**
     * Teste getByEmail avec des caractères spéciaux dans l'email.
     *
     * @covers ::getByEmail
     *
     * @return void
     */
    public function testGetByEmailWithSpecialCharacters(): void
    {
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, admin_status)
            VALUES ('Paul', 'Léger', 'paul+test@example.com', 'hashedpass', 0)
        ");

        $user = $this->model->getByEmail('paul+test@example.com');

        $this->assertIsArray($user);
        $this->assertEquals('paul+test@example.com', $user['email']);
    }

    /**
     * Teste que create insère correctement un nouvel utilisateur.
     *
     * @covers ::create
     * @uses ::getByEmail
     *
     * @return void
     */
    public function testCreateInsertsNewUserSuccessfully(): void
    {
        $data = [
            'first_name' => 'Sophie',
            'last_name' => 'Bernard',
            'email' => 'sophie@example.com',
            'password' => 'SecurePass123',
            'profession_id' => 2, // Changed from profession to profession_id
            'admin_status' => 0
        ];

        $userId = $this->model->create($data);

        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);

        // Vérifier que l'utilisateur existe vraiment
        $user = $this->model->getByEmail('sophie@example.com');
        $this->assertIsArray($user);
        $this->assertEquals('Sophie', $user['first_name']);
        $this->assertEquals('Bernard', $user['last_name']);
        $this->assertEquals('Nurse', $user['profession_label']); // Changed to profession_label
    }

    /**
     * Teste que create hash correctement le mot de passe.
     *
     * @covers ::create
     * @uses ::getByEmail
     *
     * @return void
     */
    public function testCreateHashesPasswordCorrectly(): void
    {
        $plainPassword = 'MyPassword123';
        $data = [
            'first_name' => 'Luc',
            'last_name' => 'Moreau',
            'email' => 'luc@example.com',
            'password' => $plainPassword,
            'admin_status' => 0
        ];

        $this->model->create($data);

        $user = $this->model->getByEmail('luc@example.com');

        $this->assertNotEquals($plainPassword, $user['password']);

        $this->assertTrue(password_verify($plainPassword, $user['password']));
    }

    /**
     * Teste la création d'un utilisateur avec profession nulle.
     *
     * @covers ::create
     * @uses ::getByEmail
     *
     * @return void
     */
    public function testCreateWithNullProfession(): void
    {
        $data = [
            'first_name' => 'Emma',
            'last_name' => 'Petit',
            'email' => 'emma@example.com',
            'password' => 'Password123',
            'profession_id' => null, // Changed to profession_id
            'admin_status' => 0
        ];

        $userId = $this->model->create($data);

        $user = $this->model->getByEmail('emma@example.com');
        $this->assertNull($user['profession_id']); // Changed to profession_id
        $this->assertEquals(0, $user['admin_status']);
    }

    /**
     * Teste la création d'un utilisateur avec statut admin.
     *
     * @covers ::create
     * @uses ::getByEmail
     *
     * @return void
     */
    public function testCreateWithAdminStatus(): void
    {
        $data = [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => 'AdminPass123',
            'admin_status' => 1
        ];

        $this->model->create($data);

        $user = $this->model->getByEmail('admin@example.com');
        $this->assertEquals(1, $user['admin_status']);
    }

    /**
     * Teste que create lance une exception en cas de duplication d'email.
     *
     * @covers ::create
     *
     * @return void
     */
    public function testCreateThrowsExceptionOnDuplicateEmail(): void
    {
        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'duplicate@example.com',
            'password' => 'Pass123',
            'admin_status' => 0
        ];

        $this->model->create($data);

        $this->expectException(PDOException::class);
        $this->model->create($data);
    }

    /**
     * Teste que create retourne le bon ID utilisateur.
     *
     * @covers ::create
     *
     * @return void
     */
    public function testCreateReturnsCorrectUserId(): void
    {
        $data1 = [
            'first_name' => 'User',
            'last_name' => 'One',
            'email' => 'user1@example.com',
            'password' => 'Pass123',
            'admin_status' => 0
        ];

        $data2 = [
            'first_name' => 'User',
            'last_name' => 'Two',
            'email' => 'user2@example.com',
            'password' => 'Pass123',
            'admin_status' => 0
        ];

        $userId1 = $this->model->create($data1);
        $userId2 = $this->model->create($data2);

        $this->assertNotEquals($userId1, $userId2);
        $this->assertGreaterThan($userId1, $userId2);
    }

    /**
     * Teste l'insertion et la récupération de plusieurs utilisateurs.
     *
     * @covers ::create
     * @uses ::getByEmail
     *
     * @return void
     */
    public function testMultipleUsersInDatabase(): void
    {
        $users = [
            ['first_name' => 'Alice', 'last_name' => 'A', 'email' => 'alice@example.com', 'password' => 'Pass1', 'admin_status' => 0],
            ['first_name' => 'Bob', 'last_name' => 'B', 'email' => 'bob@example.com', 'password' => 'Pass2', 'admin_status' => 0],
            ['first_name' => 'Charlie', 'last_name' => 'C', 'email' => 'charlie@example.com', 'password' => 'Pass3', 'admin_status' => 1]
        ];

        foreach ($users as $userData) {
            $this->model->create($userData);
        }

        $alice = $this->model->getByEmail('alice@example.com');
        $bob = $this->model->getByEmail('bob@example.com');
        $charlie = $this->model->getByEmail('charlie@example.com');

        $this->assertEquals('Alice', $alice['first_name']);
        $this->assertEquals('Bob', $bob['first_name']);
        $this->assertEquals('Charlie', $charlie['first_name']);
        $this->assertEquals(1, $charlie['admin_status']);
    }

    /**
     * Teste le workflow complet de création d'un utilisateur.
     *
     * @covers ::create
     * @uses ::getByEmail
     *
     * @return void
     */
    public function testCompleteUserCreationWorkflow(): void
    {
        $password = 'SecurePassword123';
        $data = [
            'first_name' => 'Workflow',
            'last_name' => 'Test',
            'email' => 'workflow@example.com',
            'password' => $password,
            'profession_id' => 4, // Changed from profession to profession_id (Pharmacist)
            'admin_status' => 1
        ];

        $existingUser = $this->model->getByEmail('workflow@example.com');
        $this->assertNull($existingUser);

        $userId = $this->model->create($data);
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);

        $user = $this->model->getByEmail('workflow@example.com');

        $this->assertIsArray($user);
        $this->assertEquals($userId, $user['id_user']);
        $this->assertEquals('Workflow', $user['first_name']);
        $this->assertEquals('Test', $user['last_name']);
        $this->assertEquals('workflow@example.com', $user['email']);
        $this->assertEquals('Pharmacist', $user['profession_label']); // Changed to profession_label
        $this->assertEquals(1, $user['admin_status']);

        $this->assertNotEquals($password, $user['password']);
        $this->assertTrue(password_verify($password, $user['password']));
    }

    /**
     * Teste le modèle avec un nom de table personnalisé.
     *
     * @covers ::create
     * @covers ::getByEmail
     *
     * @return void
     */
    public function testModelWorksWithCustomTableName(): void
    {
        $this->pdo->exec("
            CREATE TABLE custom_users (
                id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                profession_id INTEGER,
                admin_status INTEGER DEFAULT 0,
                FOREIGN KEY (profession_id) REFERENCES professions(id_profession)
            )
        ");

        $customModel = new userModel($this->pdo, 'custom_users');

        $data = [
            'first_name' => 'Custom',
            'last_name' => 'User',
            'email' => 'custom@example.com',
            'password' => 'Pass123',
            'profession_id' => 1, // Changed from profession
            'admin_status' => 0
        ];

        $userId = $customModel->create($data);
        $user = $customModel->getByEmail('custom@example.com');

        $this->assertIsArray($user);
        $this->assertEquals('Custom', $user['first_name']);
        $this->assertEquals($userId, $user['id_user']);
    }
    /**
     * Test de base pour vérifier que le constructeur de userModel fonctionne.
     * On s'assure que l'objet est bien créé sans erreur.
     */
    public function testConstructor(): void
    {
        $model = new userModel($this->pdo);
        $this->assertInstanceOf(userModel::class, $model);
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
                profession_id INTEGER,
                admin_status INTEGER DEFAULT 0,
                birth_date TEXT,
                age INTEGER,
                created_at TEXT,
                FOREIGN KEY (profession_id) REFERENCES professions(id_profession)
            )
        ");

        $model = new userModel($this->pdo, 'custom_users');
        $this->assertInstanceOf(userModel::class, $model);
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
            INSERT INTO users (first_name, last_name, email, password, profession_id, admin_status)
            VALUES ('Jane', 'Smith', 'jane.smith@example.com', '{$hashedPassword}', 2, 0)
        ");

        $user = $this->model->verifyCredentials('jane.smith@example.com', $plainPassword);

        $this->assertNotNull($user);
        $this->assertIsArray($user);
        $this->assertEquals('Jane', $user['first_name']);
        $this->assertEquals('Smith', $user['last_name']);
        $this->assertEquals('jane.smith@example.com', $user['email']);
        $this->assertEquals('Nurse', $user['profession_label']); // Changed
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
            INSERT INTO users (first_name, last_name, email, password, profession_id, admin_status)
            VALUES ('Bob', 'Johnson', 'bob.johnson@example.com', '{$hashedPassword}', 3, 1)
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
        // First, create a test profession
        $this->pdo->exec("
            INSERT INTO professions (id_profession, label_profession)
            VALUES (999, 'Doctor')
        ");
        
        $hashedPassword = password_hash('testpassword', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, profession_id, admin_status)
            VALUES ('Test', 'User', 'test@example.com', '{$hashedPassword}', 999, 0)
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
            INSERT INTO users (first_name, last_name, email, password, profession_id, admin_status)
            VALUES 
                ('User', 'One', 'user1@example.com', '{$hashedPassword}', 1, 0),
                ('User', 'Two', 'user2@example.com', '{$hashedPassword}', 2, 1)
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
            INSERT INTO users (first_name, last_name, email, password, profession_id, admin_status)
            VALUES ('Test', 'User', 'test@example.com', '{$hashedPassword}', 1, 0)
        ");

        $user = $this->model->getByEmail('test@example.com');

        $this->assertIsArray($user);
        $this->assertArrayHasKey('first_name', $user);
        $this->assertArrayNotHasKey(0, $user);
    }

    /**
     * Teste que listUsersForLogin retourne un tableau vide quand aucun utilisateur n'existe.
     *
     * @covers ::listUsersForLogin
     *
     * @return void
     */
    public function testListUsersForLoginReturnsEmptyArrayWhenNoUsers(): void
    {
        $users = $this->model->listUsersForLogin();

        $this->assertIsArray($users);
        $this->assertEmpty($users);
    }

    /**
     * Teste que listUsersForLogin retourne tous les utilisateurs avec les bons champs.
     *
     * @covers ::listUsersForLogin
     *
     * @return void
     */
    public function testListUsersForLoginReturnsUsersWithCorrectFields(): void
    {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, profession_id, admin_status)
            VALUES 
                ('Alice', 'Anderson', 'alice@example.com', '{$hashedPassword}', 1, 0),
                ('Bob', 'Brown', 'bob@example.com', '{$hashedPassword}', 2, 1)
        ");

        $users = $this->model->listUsersForLogin();

        $this->assertIsArray($users);
        $this->assertCount(2, $users);

        // Vérifie que chaque utilisateur a les bons champs
        foreach ($users as $user) {
            $this->assertArrayHasKey('id_user', $user);
            $this->assertArrayHasKey('first_name', $user);
            $this->assertArrayHasKey('last_name', $user);
            $this->assertArrayHasKey('email', $user);
            
            // Vérifie que le mot de passe n'est PAS inclus
            $this->assertArrayNotHasKey('password', $user);
            $this->assertArrayNotHasKey('profession_id', $user); // Changed
            $this->assertArrayNotHasKey('admin_status', $user);
        }
    }

    /**
     * Teste que listUsersForLogin trie les utilisateurs par nom puis prénom.
     *
     * @covers ::listUsersForLogin
     *
     * @return void
     */
    public function testListUsersForLoginSortsByLastNameThenFirstName(): void
    {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, admin_status)
            VALUES 
                ('Charlie', 'Anderson', 'charlie@example.com', '{$hashedPassword}', 0),
                ('Alice', 'Anderson', 'alice@example.com', '{$hashedPassword}', 0),
                ('Bob', 'Zulu', 'bob@example.com', '{$hashedPassword}', 0),
                ('David', 'Brown', 'david@example.com', '{$hashedPassword}', 0)
        ");

        $users = $this->model->listUsersForLogin();

        $this->assertCount(4, $users);
        
        // Vérifie l'ordre : Anderson (Alice, Charlie), Brown (David), Zulu (Bob)
        $this->assertEquals('Anderson', $users[0]['last_name']);
        $this->assertEquals('Alice', $users[0]['first_name']);
        
        $this->assertEquals('Anderson', $users[1]['last_name']);
        $this->assertEquals('Charlie', $users[1]['first_name']);
        
        $this->assertEquals('Brown', $users[2]['last_name']);
        $this->assertEquals('David', $users[2]['first_name']);
        
        $this->assertEquals('Zulu', $users[3]['last_name']);
        $this->assertEquals('Bob', $users[3]['first_name']);
    }

    /**
     * Teste que listUsersForLogin respecte la limite par défaut de 500 utilisateurs.
     *
     * @covers ::listUsersForLogin
     *
     * @return void
     */
    public function testListUsersForLoginRespectsDefaultLimit(): void
    {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        
        // Insère 10 utilisateurs (on ne peut pas tester 500 en SQLite en mémoire facilement)
        for ($i = 1; $i <= 10; $i++) {
            $this->pdo->exec("
                INSERT INTO users (first_name, last_name, email, password, admin_status)
                VALUES ('User{$i}', 'Test{$i}', 'user{$i}@example.com', '{$hashedPassword}', 0)
            ");
        }

        $users = $this->model->listUsersForLogin();

        $this->assertCount(10, $users);
    }

    /**
     * Teste que listUsersForLogin respecte une limite personnalisée.
     *
     * @covers ::listUsersForLogin
     *
     * @return void
     */
    public function testListUsersForLoginRespectsCustomLimit(): void
    {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        
        // Insère 10 utilisateurs
        for ($i = 1; $i <= 10; $i++) {
            $this->pdo->exec("
                INSERT INTO users (first_name, last_name, email, password, admin_status)
                VALUES ('User{$i}', 'Test{$i}', 'user{$i}@example.com', '{$hashedPassword}', 0)
            ");
        }

        // Limite à 5 utilisateurs
        $users = $this->model->listUsersForLogin(5);

        $this->assertCount(5, $users);
    }

    /**
     * Teste que listUsersForLogin avec une limite de 1 retourne un seul utilisateur.
     *
     * @covers ::listUsersForLogin
     *
     * @return void
     */
    public function testListUsersForLoginWithLimitOne(): void
    {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, admin_status)
            VALUES 
                ('Alice', 'Anderson', 'alice@example.com', '{$hashedPassword}', 0),
                ('Bob', 'Brown', 'bob@example.com', '{$hashedPassword}', 0)
        ");

        $users = $this->model->listUsersForLogin(1);

        $this->assertCount(1, $users);
        $this->assertEquals('Anderson', $users[0]['last_name']);
        $this->assertEquals('Alice', $users[0]['first_name']);
    }

    /**
     * Teste que listUsersForLogin retourne des tableaux associatifs.
     *
     * @covers ::listUsersForLogin
     *
     * @return void
     */
    public function testListUsersForLoginReturnsAssociativeArrays(): void
    {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, admin_status)
            VALUES ('Test', 'User', 'test@example.com', '{$hashedPassword}', 0)
        ");

        $users = $this->model->listUsersForLogin();

        $this->assertCount(1, $users);
        $this->assertIsArray($users[0]);
        
        // Vérifie que c'est bien un tableau associatif
        $this->assertArrayHasKey('first_name', $users[0]);
        $this->assertArrayNotHasKey(0, $users[0]);
    }

    /**
     * Teste que listUsersForLogin retourne tous les utilisateurs indépendamment de leur statut admin.
     *
     * @covers ::listUsersForLogin
     *
     * @return void
     */
    public function testListUsersForLoginIncludesAllUserTypes(): void
    {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, profession_id, admin_status)
            VALUES 
                ('Admin', 'User', 'admin@example.com', '{$hashedPassword}', 3, 1),
                ('Regular', 'User', 'regular@example.com', '{$hashedPassword}', 1, 0),
                ('Another', 'Admin', 'another@example.com', '{$hashedPassword}', NULL, 1)
        ");

        $users = $this->model->listUsersForLogin();

        $this->assertCount(3, $users);
        
        // Vérifie qu'on a bien des admins et des utilisateurs réguliers
        $emails = array_column($users, 'email');
        $this->assertContains('admin@example.com', $emails);
        $this->assertContains('regular@example.com', $emails);
        $this->assertContains('another@example.com', $emails);
    }

    /**
     * Teste que listUsersForLogin fonctionne avec des noms contenant des caractères spéciaux.
     *
     * @covers ::listUsersForLogin
     *
     * @return void
     */
    public function testListUsersForLoginWithSpecialCharactersInNames(): void
    {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, admin_status)
            VALUES 
                ('François', 'Müller', 'francois@example.com', '{$hashedPassword}', 0),
                ('José', 'García', 'jose@example.com', '{$hashedPassword}', 0),
                ('Zoë', 'O''Connor', 'zoe@example.com', '{$hashedPassword}', 0)
        ");

        $users = $this->model->listUsersForLogin();

        $this->assertCount(3, $users);
        
        // Vérifie que les caractères spéciaux sont préservés
        $names = array_map(fn($u) => $u['first_name'] . ' ' . $u['last_name'], $users);
        $this->assertContains('José García', $names);
        $this->assertContains('François Müller', $names);
    }

    /**
     * Teste le comportement de listUsersForLogin avec une limite de 0.
     *
     * @covers ::listUsersForLogin
     *
     * @return void
     */
    public function testListUsersForLoginWithZeroLimit(): void
    {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password, admin_status)
            VALUES ('Test', 'User', 'test@example.com', '{$hashedPassword}', 0)
        ");

        $users = $this->model->listUsersForLogin(0);

        // Avec LIMIT 0, SQL ne retourne aucune ligne
        $this->assertIsArray($users);
        $this->assertEmpty($users);
    }
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO {$this->table}
                (first_name, last_name, email, password, admin_status, birth_date, profession_id, created_at)
            VALUES
                (:first_name, :last_name, :email, :password, :admin_status, :birth_date, :profession_id, :created_at)
        ";

        $hash = password_hash((string)$data['password'], PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':first_name'    => (string)$data['first_name'],
            ':last_name'     => (string)$data['last_name'],
            ':email'         => strtolower(trim((string)$data['email'])),
            ':password'      => $hash,
            ':admin_status'  => (int)($data['admin_status'] ?? 0),
            ':birth_date'    => $data['birth_date'] ?? null,
            ':profession_id' => isset($data['profession_id']) && $data['profession_id'] !== null 
                ? (int)$data['profession_id'] 
                : null,
            ':created_at'    => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ]);

        $id = (int)$this->pdo->lastInsertId();
        if ($id <= 0) {
            throw new PDOException('Insertion utilisateur échouée: lastInsertId=0');
        }
        return $id;
    }
}