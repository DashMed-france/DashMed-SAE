<?php

namespace Tests\Repositories;

use PHPUnit\Framework\TestCase;
use modules\models\repositories\SearchRepository;
use PDO;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Class SearchRepositoryTest | Tests du Repository Recherche
 *
 * Tests for global cross-entity search functionalities.
 * Tests pour les fonctionnalités de recherche globale multi-entités.
 *
 * @package Tests\Repositories
 * @author DashMed Team
 */
class SearchRepositoryTest extends TestCase
{
    private PDO $pdo;
    private SearchRepository $searchRepo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("CREATE TABLE patients (
            id_patient INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT,
            last_name TEXT,
            birth_date TEXT
        )");

        $this->pdo->exec("CREATE TABLE users (
            id_user INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT,
            last_name TEXT,
            id_profession INTEGER
        )");

        $this->pdo->exec("CREATE TABLE professions (
            id_profession INTEGER PRIMARY KEY AUTOINCREMENT,
            label_profession TEXT
        )");

        $this->pdo->exec("CREATE TABLE consultations (
            id_consultations INTEGER PRIMARY KEY AUTOINCREMENT,
            id_patient INTEGER,
            id_user INTEGER,
            title TEXT,
            type TEXT,
            date TEXT
        )");

        $this->searchRepo = new SearchRepository($this->pdo);

        $this->pdo->exec("INSERT INTO patients (first_name, last_name) VALUES ('Jean', 'Dupont')");
        $this->pdo->exec("INSERT INTO professions (id_profession, label_profession) VALUES (1, 'Cardio')");
        $this->pdo->exec("INSERT INTO users (first_name, last_name, id_profession) VALUES ('Gregory', 'House', 1)");
        $this->pdo->exec("INSERT INTO consultations (id_patient, id_user, title, date) VALUES (1, 1, 'Consultation cardiaque', '2023-01-01')");
    }

    public function testSearchGlobalPatients()
    {
        $results = $this->searchRepo->searchGlobal('Dupont');
        $this->assertCount(1, $results['patients']);
        $this->assertEquals('Dupont', $results['patients'][0]['last_name']);
    }

    public function testSearchGlobalDoctors()
    {
        $results = $this->searchRepo->searchGlobal('House');
        $this->assertCount(1, $results['doctors']);
        $this->assertEquals('House', $results['doctors'][0]['last_name']);
    }

    public function testSearchGlobalConsultations()
    {
        $results = $this->searchRepo->searchGlobal('cardiaque');
        $this->assertCount(1, $results['consultations']);
    }
}
