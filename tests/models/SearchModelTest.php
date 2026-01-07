<?php

use PHPUnit\Framework\TestCase;
use modules\models\SearchModel;

class SearchModelTest extends TestCase
{
    private PDO $pdo;
    private SearchModel $searchModel;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create tables
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

        $this->searchModel = new SearchModel($this->pdo);

        // Seed data
        $this->pdo->exec("INSERT INTO patients (first_name, last_name) VALUES ('Jean', 'Dupont')");
        $this->pdo->exec("INSERT INTO patients (first_name, last_name) VALUES ('Marie', 'Curie')");

        $this->pdo->exec("INSERT INTO professions (id_profession, label_profession) VALUES (1, 'Cardio')");
        $this->pdo->exec("INSERT INTO users (first_name, last_name, id_profession) VALUES ('Gregory', 'House', 1)");
        
        $this->pdo->exec("INSERT INTO consultations (id_patient, id_user, title, date) 
            VALUES (1, 1, 'Consultation cardiaque', '2023-01-01')");
    }

    public function testSearchGlobalReturnsEmptyForShortQuery()
    {
        $results = $this->searchModel->searchGlobal('a');
        $this->assertEmpty($results);
    }

    public function testSearchGlobalPatients()
    {
        $results = $this->searchModel->searchGlobal('Dupont');
        $this->assertCount(1, $results['patients']);
        $this->assertEquals('Dupont', $results['patients'][0]['last_name']);
        
        $this->assertCount(0, $results['doctors']);
        $this->assertCount(0, $results['consultations']);
    }

    public function testSearchGlobalDoctors()
    {
        $results = $this->searchModel->searchGlobal('House');
        $this->assertCount(1, $results['doctors']);
        $this->assertEquals('House', $results['doctors'][0]['last_name']);
    }

    public function testSearchGlobalConsultations()
    {
        $results = $this->searchModel->searchGlobal('cardiaque');
        
        // Note: SQLite LIKE is case insensitive for ASCII by default usually, but let's check
        // The implementation uses LOWER(col) LIKE :term.
        
        $this->assertCount(1, $results['consultations']);
        $this->assertEquals('Consultation cardiaque', $results['consultations'][0]['title']);
    }

    public function testSearchContextualWithPatientId()
    {
        // Add another doctor not linked to patient 1
        $this->pdo->exec("INSERT INTO users (first_name, last_name, id_profession) VALUES ('John', 'Dorian', 1)");

        // Search for 'o'
        // Global search should find both House and Dorian (both contain 'o') or similar logic?
        // Let's refine the search query to match both if no context
        // House has 'o', Dorian has 'o'.
        
        // Contextual search for Patient 1 (Linked to House only)
        // Should only return House if we search for something they both match, or if we search specifically only House.
        
        // Let's search 'House' with patient 1
        $results = $this->searchModel->searchGlobal('House', 5, 1);
        $this->assertCount(1, $results['doctors']); 
        
        // Let's search 'Dorian' with patient 1. Dorian is NOT linked to patient 1.
        $results2 = $this->searchModel->searchGlobal('Dorian', 5, 1);
        $this->assertCount(0, $results2['doctors']);
        
        // Global search 'Dorian' without patient
        $results3 = $this->searchModel->searchGlobal('Dorian');
        $this->assertCount(1, $results3['doctors']);
    }
}
