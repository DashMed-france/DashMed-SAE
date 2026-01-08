<?php

namespace controllers\pages {

    use modules\controllers\pages\ProfileController;

    // Charge le contrôleur
    require_once __DIR__ . '/../../../app/controllers/pages/ProfileController.php';
}

namespace modules\views\pages {
    if (!class_exists('modules\views\pages\profileView')) {
        class profileView
        {
            public function show($u, $p, $m)
            {
                echo "ProfileView";
            }
        }
    }
}

namespace controllers\pages {

    use PHPUnit\Framework\TestCase;
    use PDO;

    /**
     * Class ProfileControllerTest | Tests du Contrôleur de Profil
     *
     * Unit tests for ProfileController.
     * Tests unitaires pour ProfileController.
     *
     * @package Tests\Controllers\Pages
     * @author DashMed Team
     */
    class ProfileControllerTest extends TestCase
    {
        private ?PDO $pdo = null;
        private $controller;

        /**
         * Setup test environment.
         * Configuration de l'environnement de test.
         */
        protected function setUp(): void
        {
            $this->pdo = new PDO('sqlite::memory:');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->pdo->exec("
                CREATE TABLE professions (
                    id_profession INTEGER PRIMARY KEY AUTOINCREMENT,
                    label_profession TEXT NOT NULL
                );

                CREATE TABLE users (
                    id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                    first_name TEXT NOT NULL,
                    last_name TEXT NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    id_profession INTEGER,
                    FOREIGN KEY (id_profession) REFERENCES professions(id_profession)
                );
            ");

            $this->pdo->exec("INSERT INTO professions (label_profession) VALUES ('Cardiologue'), ('Dermatologue')");
            $this->pdo->exec("
                INSERT INTO users (first_name, last_name, email, id_profession)
                VALUES ('AliceModif', 'DurandModif', 'alice@example.com', 1)
            ");

            // Init controller (start session if need be)
            $this->controller = new \modules\controllers\pages\ProfileController($this->pdo);
            $this->controller->setTestMode(true);

            // Populate session AFTER controller init
            $_SESSION = [
                'email' => 'alice@example.com',
                'csrf_profile' => 'test_csrf_token'
            ];
        }

        /**
         * Test profile update.
         * Teste la mise à jour du profil.
         */
        public function testProfileUpdate(): void
        {
            $_POST = [
                'csrf' => 'test_csrf_token',
                'action' => 'update',
                'first_name' => 'AliceUpdated',
                'last_name' => 'DurandUpdated',
                'id_profession' => '2'
            ];

            $this->controller->post();

            $stmt = $this->pdo->prepare("SELECT first_name, last_name, id_profession FROM users WHERE email = ?");
            $stmt->execute(['alice@example.com']);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check User State
            $this->assertEquals('AliceUpdated', $user['first_name']);
            $this->assertEquals('DurandUpdated', $user['last_name']);
            $this->assertEquals(2, $user['id_profession']);
        }

        /**
         * Test account deletion.
         * Teste la suppression du compte.
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
         * Teardown test environment.
         * Nettoyage de l'environnement de test.
         */
        protected function tearDown(): void
        {
            $this->pdo = null;
            $_SESSION = [];
            $_POST = [];
        }
    }
}
