<?php

namespace controllers\pages;

use modules\controllers\pages\MonitoringController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once __DIR__ . '/../../../app/controllers/pages/MonitoringController.php';

/**
 * Unit test class for MonitoringController.
 *
 * Tests the tracking functionalities for consultations (past and future).
 *
 * @coversDefaultClass \modules\controllers\pages\MonitoringController
 */
class MonitoringControllerTest extends TestCase
{
    /**
     * Instance of the MonitoringController to be tested.
     *
     * @var MonitoringController
     */
    private MonitoringController $controller;

    /**
     * Prepares the test environment.
     *
     * Configures the session and instantiates the controller.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $_SESSION = [];
        $this->controller = new MonitoringController();
    }

    /**
     * Tests that the isUserLoggedIn method returns false if the email is not in the session.
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
     * Tests that the isUserLoggedIn method returns true if the email is in the session.
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
     * Tests that getConsultations returns a non-empty array.
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
     * Tests that getConsultations returns valid Consultation objects.
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
     * Tests that the number of returned consultations is correct.
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
     * Tests that consultations have dates in the expected format (d/m/Y).
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
     * Tests the separation of past and future consultations.
     *
     * This method verifies the business logic for sorting consultations
     * relative to the current date.
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

        $this->assertEquals(
            count($consultations),
            count($consultationsPassees) + count($consultationsFutures),
            "Le total des consultations passées et futures devrait égaler le total"
        );
    }

    /**
     * Cleanup after each test.
     *
     * Resets the session.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
    }
}