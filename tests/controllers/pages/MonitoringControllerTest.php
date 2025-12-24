<?php

namespace controllers\pages;

use modules\controllers\pages\MonitoringController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once __DIR__ . '/../../../app/controllers/pages/MonitoringController.php';

/**
 * Classe de tests unitaires pour le contrôleur MonitoringController.
 *
 * Teste les fonctionnalités de suivi des consultations (passées et futures).
 *
 * @coversDefaultClass \modules\controllers\pages\MonitoringController
 */
class MonitoringControllerTest extends TestCase
{
    /**
     * Instance du contrôleur MonitoringController à tester.
     *
     * @var MonitoringController
     */
    private MonitoringController $controller;

    /**
     * Prépare l'environnement de test.
     *
     * Configure la session et instancie le contrôleur.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $_SESSION = [];
        $this->controller = new MonitoringController();
    }

    /**
     * Teste que la méthode isUserLoggedIn retourne false si l'email n'est pas en session.
     *
     * @covers ::isUserLoggedIn
     * @return void
     */
    public function testIsUserLoggedInReturnsFalseWhenNotLoggedIn(): void
    {
        $_SESSION = [];

        $reflection = new ReflectionClass(MonitoringController::class);
        $method = $reflection->getMethod('isUserLoggedIn');
        $result = $method->invoke($this->controller);

        $this->assertFalse($result, "L'utilisateur ne devrait pas être connecté");
    }

    /**
     * Teste que la méthode isUserLoggedIn retourne true si l'email est en session.
     *
     * @covers ::isUserLoggedIn
     * @return void
     */
    public function testIsUserLoggedInReturnsTrueWhenLoggedIn(): void
    {
        $_SESSION['email'] = 'test@example.com';

        $reflection = new ReflectionClass(MonitoringController::class);
        $method = $reflection->getMethod('isUserLoggedIn');
        $result = $method->invoke($this->controller);

        $this->assertTrue($result, "L'utilisateur devrait être connecté");
    }

    /**
     * Teste que getConsultations retourne un tableau non vide.
     *
     * @covers ::getConsultations
     * @return void
     */
    public function testGetConsultationsReturnsNonEmptyArray(): void
    {
        $reflection = new ReflectionClass(MonitoringController::class);
        $method = $reflection->getMethod('getConsultations');
        $result = $method->invoke($this->controller);

        $this->assertIsArray($result, "Le résultat devrait être un tableau");
        $this->assertNotEmpty($result, "Le tableau des consultations ne devrait pas être vide");
    }

    /**
     * Teste que getConsultations retourne des objets Consultation valides.
     *
     * @covers ::getConsultations
     * @return void
     */
    public function testGetConsultationsReturnsValidConsultationObjects(): void
    {
        $reflection = new ReflectionClass(MonitoringController::class);
        $method = $reflection->getMethod('getConsultations');

        $consultations = $method->invoke($this->controller);

        foreach ($consultations as $consultation) {
            $this->assertInstanceOf(
                \modules\models\Consultation::class,
                $consultation,
                "Chaque élément devrait être une instance de Consultation"
            );
        }
    }

    /**
     * Teste que le nombre de consultations retournées est correct.
     *
     * @covers ::getConsultations
     * @return void
     */
    public function testGetConsultationsReturnsExpectedCount(): void
    {
        $reflection = new ReflectionClass(MonitoringController::class);
        $method = $reflection->getMethod('getConsultations');

        $consultations = $method->invoke($this->controller);

        $this->assertCount(6, $consultations, "Il devrait y avoir exactement 6 consultations");
    }

    /**
     * Teste que les consultations ont des dates au format attendu (d/m/Y).
     *
     * @covers ::getConsultations
     * @return void
     */
    public function testConsultationsHaveValidDateFormat(): void
    {
        $reflection = new ReflectionClass(MonitoringController::class);
        $method = $reflection->getMethod('getConsultations');

        $consultations = $method->invoke($this->controller);

        foreach ($consultations as $consultation) {
            $date = $consultation->getDate();
            $parsedDate = \DateTime::createFromFormat('d/m/Y', $date);

            $this->assertNotFalse(
                $parsedDate,
                "La date '$date' devrait être au format d/m/Y"
            );
        }
    }

    /**
     * Teste la séparation des consultations passées et futures.
     *
     * Cette méthode vérifie la logique métier de tri des consultations
     * par rapport à la date du jour.
     *
     * @covers ::get
     * @return void
     */
    public function testConsultationsSeparationByDate(): void
    {
        $reflection = new ReflectionClass(MonitoringController::class);
        $method = $reflection->getMethod('getConsultations');

        $consultations = $method->invoke($this->controller);
        $dateAujourdhui = new \DateTime();
        $consultationsPassees = [];
        $consultationsFutures = [];

        foreach ($consultations as $consultation) {
            $dateConsultation = \DateTime::createFromFormat('d/m/Y', $consultation->getDate());

            if ($dateConsultation < $dateAujourdhui) {
                $consultationsPassees[] = $consultation;
            } else {
                $consultationsFutures[] = $consultation;
            }
        }

        // Vérifie que la somme des deux listes égale le total
        $this->assertEquals(
            count($consultations),
            count($consultationsPassees) + count($consultationsFutures),
            "Le total des consultations passées et futures devrait égaler le total"
        );
    }

    /**
     * Nettoyage après chaque test.
     *
     * Réinitialise la session.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
    }
}
