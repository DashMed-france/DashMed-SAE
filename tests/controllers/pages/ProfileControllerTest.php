<?php

namespace controllers\pages;

use modules\controllers\pages\ProfileController;
use modules\views\pages\profileView;
use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionClass;

require_once __DIR__ . '/../../../app/controllers/pages/ProfileController.php';

/**
 * Classe de tests unitaires pour le contrôleur profileController.
 *
 * Teste les fonctionnalités de mise à jour de profil et suppression de compte.
 *
 * @coversDefaultClass \modules\controllers\pages\ProfileController
 */
class ProfileControllerTest extends TestCase
{
    /**
     * Instance PDO pour la base SQLite en mémoire.
     *
     * @var ?PDO
     */
    private ?PDO $pdo = null;
    /**
     * Instance du contrôleur profileController à tester.
     *
     * @var profileController
     */
    private profileController $controller;

    /**
     * Prépare l'environnement de test.
     *
     * Initialise la base SQLite en mémoire, crée les tables et données de test,
     * configure la session et instancie le contrôleur en mode test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                profession_id INTEGER,
                FOREIGN KEY (profession_id) REFERENCES medical_specialties(id)
            );

            CREATE TABLE medical_specialties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL
            );
        ");

        $this->pdo->exec("INSERT INTO medical_specialties (name) VALUES ('Cardiologue'), ('Dermatologue')");
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, profession_id)
            VALUES ('AliceModif', 'DurandModif', 'alice@example.com', 1)
        ");

        $_SESSION = [
            'email' => 'alice@example.com',
            'csrf_profile' => 'test_csrf_token'
        ];

        $this->controller = $this->getMockBuilder(ProfileController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $ref = new ReflectionClass(profileController::class);
        $pdoProp = $ref->getProperty('pdo');
        $pdoProp->setAccessible(true);
        $pdoProp->setValue($this->controller, $this->pdo);

        $testModeProp = $ref->getProperty('testMode');
        $testModeProp->setAccessible(true);
        $testModeProp->setValue($this->controller, true);
    }

    /**
     * Teste la mise à jour du profil utilisateur.
     *
     * Vérifie que les champs first_name, last_name et profession_id
     * sont correctement modifiés en base après un POST.
     *
     * @covers ::post
     * @return void
     */
    public function testProfileUpdate(): void
    {
        $_POST = [
            'csrf' => 'test_csrf_token',
            'action' => 'update',
            'first_name' => 'AliceModif',
            'last_name' => 'DurandModif',
            'profession_id' => '1'
        ];

        $this->controller->post();

        $stmt = $this->pdo->prepare("SELECT first_name, last_name, profession_id FROM users WHERE email = ?");
        $stmt->execute(['alice@example.com']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('AliceModif', $user['first_name']);
        $this->assertEquals('DurandModif', $user['last_name']);
        $this->assertEquals(1, $user['profession_id']);
    }

    /**
     * Teste la suppression du compte utilisateur.
     *
     * Vérifie que l'utilisateur est bien supprimé de la base de données
     * après un POST avec action 'delete_account'.
     *
     * @covers ::post
     * @return void
     */
    public function testDeleteAccount(): void
    {
        $_POST = [
            'csrf' => 'test_csrf_token',
            'action' => 'delete_account'
        ];

        $this->controller->post();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute(['alice@example.com']);
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count, 'Le compte utilisateur aurait dû être supprimé');
    }

    /**
     * Nettoyage après chaque test.
     *
     * Réinitialise la base de données et la session.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->pdo = null;
        $_SESSION = [];
        $_POST = [];
    }
}